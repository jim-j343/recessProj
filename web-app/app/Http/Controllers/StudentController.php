<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\ParticipationScore;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\Warning;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    // Student dashboard — progress, grades, quiz notices, and general assessment
    public function dashboard()
    {
        $user = Auth::user();

        // Groups the student is an active member of
        $groupIds = GroupMembership::where('user_id', $user->user_id)
            ->where('status', 'active')
            ->pluck('group_id');

        // ---- Pre-existing sidebar stats (kept for compatibility) ----
        $topicCount = Topic::whereIn('group_id', $groupIds)->count();
        $postCount = Post::where('author_id', $user->user_id)->count();
        $groupCount = $groupIds->count();

        // ---- Quizzes eligible for this student's groups — matches both
        // legacy quizzes pinned to one group_id and newer course-targeted
        // quizzes (visible to every group sharing that course unit) ----
        $studentCourseNames = Group::whereIn('group_id', $groupIds)->pluck('course_name')->filter();

        $quizzes = Quiz::where(function ($q) use ($groupIds, $studentCourseNames) {
                $q->whereIn('group_id', $groupIds);
                if ($studentCourseNames->isNotEmpty()) {
                    $q->orWhereIn('course_name', $studentCourseNames);
                }
            })
            ->where('is_published', true)
            ->with('questions')
            ->orderByDesc('start_time')
            ->get();

        // Note: QuizController::show() creates a Submission row the moment a
        // student *opens* a quiz, before they've answered anything — so "has a
        // submission" is not the same as "finished it". Only submitted_at being
        // set means the attempt is actually complete.
        $submissions = Submission::where('user_id', $user->user_id)
            ->whereIn('quiz_id', $quizzes->pluck('quiz_id'))
            ->get()
            ->keyBy('quiz_id');

        $now = now();
        $activeQuiz = null;
        $upcomingQuiz = null;

        foreach ($quizzes as $quiz) {
            $submission = $submissions->get($quiz->quiz_id);
            $alreadyCompleted = $submission && $submission->submitted_at !== null;
            if ($alreadyCompleted) {
                continue;
            }

            $endsAt = (clone $quiz->start_time)->addMinutes($quiz->duration_minutes);

            if ($quiz->start_time->lte($now) && $endsAt->gte($now)) {
                $activeQuiz = $quiz;
            } elseif ($quiz->start_time->gt($now) && (!$upcomingQuiz || $quiz->start_time->lt($upcomingQuiz->start_time))) {
                $upcomingQuiz = $quiz;
            }
        }

        // ---- Grades — each score is converted to a % of that quiz's own
        // total marks before displaying or averaging, so a 3/4 quiz and a
        // 5/5 quiz compare fairly instead of averaging raw mark counts ----
        $gradedSubmissions = $submissions->filter(fn ($s) => $s->score !== null)
            ->map(function ($submission) use ($quizzes) {
                $quiz = $quizzes->firstWhere('quiz_id', $submission->quiz_id);
                $submission->setRelation('quiz', $quiz);

                $totalMarks = $quiz?->questions->sum('marks') ?? 0;
                $submission->scorePct = $totalMarks > 0
                    ? round(($submission->score / $totalMarks) * 100, 1)
                    : 0.0;

                return $submission;
            })
            ->sortByDesc('submitted_at')
            ->values();

        $averageGrade = $gradedSubmissions->count() ? round($gradedSubmissions->avg('scorePct'), 1) : null;
        $quizzesCompleted = $submissions->filter(fn ($s) => $s->submitted_at !== null)->count();
        $quizzesTotal = $quizzes->count();
        $quizProgress = $quizzesTotal > 0 ? (int) round(($quizzesCompleted / $quizzesTotal) * 100) : 0;

        // ---- Participation — computed live, per group. A student's
        // activity in one lecturer's course shouldn't get blended away
        // by another course they're also taking, so this is a real
        // breakdown rather than one averaged number. ----
        $groups = Group::whereIn('group_id', $groupIds)->get();

        $participationByGroup = $groups->map(function ($group) use ($user) {
            $groupOpeningPostIds = Post::select('topic_id', DB::raw('MIN(post_id) as post_id'))
                ->whereHas('topic', fn ($q) => $q->where('group_id', $group->group_id))
                ->groupBy('topic_id')
                ->pluck('post_id');

            $groupReplyCount = Post::where('author_id', $user->user_id)
                ->whereHas('topic', fn ($q) => $q->where('group_id', $group->group_id))
                ->whereNotIn('post_id', $groupOpeningPostIds)
                ->count();

            return [
                'group_name' => $group->name,
                'pct'        => min($groupReplyCount, 10) * 10,
            ];
        });

        $participationAvg = $participationByGroup->count()
            ? round($participationByGroup->avg('pct'), 1)
            : 0;
        $participationTotal = $participationAvg;

        // ---- Compliance / community standing ----
        $latestWarning = Warning::where('user_id', $user->user_id)
            ->whereIn('group_id', $groupIds)
            ->orderByDesc('issued_at')
            ->first();

        // ---- Recent activity feed ----
        $recentActivity = ActivityLog::where('user_id', $user->user_id)
            ->orderByDesc('logged_at')
            ->take(5)
            ->get();

        // ---- Latest topic in the student's groups (replaces hardcoded card) ----
        $latestTopic = Topic::whereIn('group_id', $groupIds)
            ->with(['posts' => fn ($q) => $q->with('author')->latest('created_at')->take(3)])
            ->latest('created_at')
            ->first();

        // ---- "Recommended for You" — replaces the fixed, dead-linked
        // placeholder card with a real pick: the most-replied-to topic in the
        // student's groups that they haven't posted in themselves ----
        $postedTopicIds = Post::where('author_id', $user->user_id)->pluck('topic_id');

        $recommendedTopic = Topic::whereIn('group_id', $groupIds)
            ->whereNotIn('topic_id', $postedTopicIds)
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->first();

        return view('student.dashboard', compact(
            'topicCount',
            'postCount',
            'groupCount',
            'activeQuiz',
            'upcomingQuiz',
            'gradedSubmissions',
            'averageGrade',
            'quizzesCompleted',
            'quizzesTotal',
            'quizProgress',
            'participationTotal',
            'participationAvg',
            'participationByGroup',
            'latestWarning',
            'recentActivity',
            'latestTopic',
            'recommendedTopic'
        ));
    }

    // "My Progress" — a deeper-dive companion to the dashboard: full
    // assessment history with real quiz scores and a real participation
    // breakdown, using the same formula the lecturer's grading table uses.
    public function progress()
    {
        $user = Auth::user();

        $groupIds = GroupMembership::where('user_id', $user->user_id)
            ->where('status', 'active')
            ->pluck('group_id');

        // ---- Participation: same definition as ParticipationController —
        // a topic's opening post doesn't count, everything after it does ----
        $openingPostIds = Post::select('topic_id', DB::raw('MIN(post_id) as post_id'))
            ->whereHas('topic', fn ($q) => $q->whereIn('group_id', $groupIds))
            ->groupBy('topic_id')
            ->pluck('post_id');

        $postCount = Post::where('author_id', $user->user_id)
            ->whereHas('topic', fn ($q) => $q->whereIn('group_id', $groupIds))
            ->count();

        $replyCount = Post::where('author_id', $user->user_id)
            ->whereHas('topic', fn ($q) => $q->whereIn('group_id', $groupIds))
            ->whereNotIn('post_id', $openingPostIds)
            ->count();

        $participationPct = min($replyCount, 10) * 10;

        // ---- Real activity for the last 7 days, replacing the fake chart ----
        $activityByDay = collect(range(6, 0))->map(function ($daysAgo) use ($user, $groupIds) {
            $date = now()->subDays($daysAgo);
            $count = Post::where('author_id', $user->user_id)
                ->whereHas('topic', fn ($q) => $q->whereIn('group_id', $groupIds))
                ->whereDate('created_at', $date->toDateString())
                ->count();

            return ['label' => $date->format('D'), 'count' => $count];
        });

        // ---- Assessment history: every completed quiz, with a real peer
        // average (mean score of everyone who's completed that same quiz).
        // Quizzes are matched by group_id OR course_name — a course-targeted
        // quiz has no group_id at all, so group-only matching missed them. ----
        $courseNames = Group::whereIn('group_id', $groupIds)->pluck('course_name')->filter();

        $submissions = Submission::where('user_id', $user->user_id)
            ->whereNotNull('submitted_at')
            ->whereHas('quiz', function ($q) use ($groupIds, $courseNames) {
                $q->whereIn('group_id', $groupIds);
                if ($courseNames->isNotEmpty()) {
                    $q->orWhereIn('course_name', $courseNames);
                }
            })
            ->with('quiz.questions')
            ->latest('submitted_at')
            ->get();

        $assessmentHistory = $submissions->map(function ($submission) {
            $totalMarks = $submission->quiz->questions->sum('marks');
            $scorePct = $totalMarks > 0 ? round(($submission->score / $totalMarks) * 100, 1) : 0.0;

            $peerAvgPct = Submission::where('quiz_id', $submission->quiz_id)
                ->whereNotNull('submitted_at')
                ->get()
                ->avg(fn ($s) => $totalMarks > 0 ? ($s->score / $totalMarks) * 100 : 0);

            return (object) [
                'title'       => $submission->quiz->title,
                'submittedAt' => $submission->submitted_at,
                'scorePct'    => $scorePct,
                'vsPeerPct'   => $peerAvgPct !== null ? round($scorePct - $peerAvgPct, 1) : null,
            ];
        });

        // ---- Most recent remark a lecturer actually left when grading —
        // real, rather than a fabricated "professor's insight" ----
        $latestRemark = ParticipationScore::where('user_id', $user->user_id)
            ->whereIn('group_id', $groupIds)
            ->latest('created_at')
            ->first();

        return view('participation.index', compact(
            'user',
            'postCount',
            'replyCount',
            'participationPct',
            'activityByDay',
            'assessmentHistory',
            'latestRemark'
        ));
    }
}