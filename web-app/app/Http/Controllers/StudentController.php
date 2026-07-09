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

        $submissions = Submission::where('user_id', $user->user_id)
            ->whereIn('quiz_id', $quizzes->pluck('quiz_id'))
            ->get()
            ->keyBy('quiz_id');

        $now = now();
        $activeQuiz = null;
        $upcomingQuiz = null;

        foreach ($quizzes as $quiz) {
            $alreadySubmitted = $submissions->has($quiz->quiz_id);
            if ($alreadySubmitted) {
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
        $quizzesCompleted = $submissions->count();
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
            'overallScore'
        ));
    }
}
