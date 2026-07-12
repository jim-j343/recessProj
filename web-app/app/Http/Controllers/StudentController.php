<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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

        // ---- Quizzes eligible for this student's groups ----
        $quizzes = Quiz::whereIn('group_id', $groupIds)
            ->where('is_published', true)
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

        // ---- Grades ----
        $gradedSubmissions = $submissions->filter(fn ($s) => $s->score !== null)
            ->map(function ($submission) use ($quizzes) {
                $submission->setRelation('quiz', $quizzes->firstWhere('quiz_id', $submission->quiz_id));
                return $submission;
            })
            ->sortByDesc('submitted_at')
            ->values();

        $averageGrade = $gradedSubmissions->count() ? round($gradedSubmissions->avg('score'), 1) : null;
        $quizzesCompleted = $submissions->filter(fn ($s) => $s->submitted_at !== null)->count();
        $quizzesTotal = $quizzes->count();
        $quizProgress = $quizzesTotal > 0 ? (int) round(($quizzesCompleted / $quizzesTotal) * 100) : 0;

        // ---- Participation (feeds "General Assessment") ----
        $participationScores = ParticipationScore::where('user_id', $user->user_id)
            ->whereIn('group_id', $groupIds)
            ->get();

        $participationTotal = round($participationScores->sum('score'), 1);
        $participationAvg = $participationScores->count() ? round($participationScores->avg('score'), 1) : null;

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

        // ---- Blended overall assessment figure ----
        $assessmentInputs = collect([$averageGrade, $participationAvg])->filter(fn ($v) => $v !== null);
        $overallScore = $assessmentInputs->count() ? (int) round($assessmentInputs->avg()) : null;

        // ---- Latest topic in the student's groups (replaces hardcoded card) ----
        $latestTopic = Topic::whereIn('group_id', $groupIds)
            ->with(['posts' => fn ($q) => $q->with('author')->latest('created_at')->take(3)])
            ->latest('created_at')
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
            'latestWarning',
            'recentActivity',
            'overallScore',
            'latestTopic'
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
        // average (mean score of everyone who's completed that same quiz) ----
        $submissions = Submission::where('user_id', $user->user_id)
            ->whereNotNull('submitted_at')
            ->whereHas('quiz', fn ($q) => $q->whereIn('group_id', $groupIds))
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
