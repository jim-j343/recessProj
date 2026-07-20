<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\ParticipationScore;
use App\Models\Post;
use App\Models\Topic;
use App\Models\Submission;
use App\Models\Warning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentApiController extends Controller
{
    // GET /api/student/dashboard — participation-by-group + community
    // standing extras that StudentDashboard.fxml needs and /quizzes doesn't
    // cover (quiz alert/progress/results are already fetched via /quizzes).
    public function dashboard(Request $request): JsonResponse
    {
        $user = Auth::user();

        $groupIds = GroupMembership::where('user_id', $user->user_id)
            ->where('status', 'active')
            ->pluck('group_id');

        // ---- Participation, per group — same live formula as web ----
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
        })->values();

        $participationAvg = $participationByGroup->count()
            ? round($participationByGroup->avg('pct'), 1)
            : 0;

        // ---- Compliance / community standing ----
        $latestWarning = Warning::where('user_id', $user->user_id)
            ->whereIn('group_id', $groupIds)
            ->orderByDesc('issued_at')
            ->first();

        $hasActiveWarning = $latestWarning && ! $latestWarning->is_heeded;

        $standing = $hasActiveWarning ? [
            'status'         => 'warning',
            'warning_number' => $latestWarning->warning_number,
            'label'          => 'Warning #' . $latestWarning->warning_number,
            'sub'            => 'Comply before ' . (optional($latestWarning->deadline)->format('d M Y') ?? 'the deadline'),
        ] : [
            'status'         => 'good',
            'warning_number' => null,
            'label'          => 'Good Standing',
            'sub'            => 'No active warnings on your account',
        ];

        // ---- Latest topic in the student's groups ----
        $latestTopic = Topic::whereIn('group_id', $groupIds)
            ->withCount('posts')
            ->with('group')
            ->latest('created_at')
            ->first();

        // ---- Recommended: most-replied-to topic the student hasn't posted in ----
        $postedTopicIds = Post::where('author_id', $user->user_id)->pluck('topic_id');

        $recommendedTopic = Topic::whereIn('group_id', $groupIds)
            ->whereNotIn('topic_id', $postedTopicIds)
            ->withCount('posts')
            ->with('group')
            ->orderByDesc('posts_count')
            ->first();

        return response()->json([
            'participation_avg'      => $participationAvg,
            'participation_by_group' => $participationByGroup,
            'standing'               => $standing,
            'latest_topic'           => $latestTopic ? $this->topicPayload($latestTopic) : null,
            'recommended_topic'      => $recommendedTopic ? $this->topicPayload($recommendedTopic) : null,
        ]);
    }

    private function topicPayload(Topic $topic): array
    {
        return [
            'topic_id'         => $topic->topic_id,
            'title'            => $topic->title,
            'group_name'       => $topic->group?->name,
            'posts_count'      => $topic->posts_count ?? 0,
            'created_at_human' => optional($topic->created_at)->diffForHumans(),
        ];
    }

    // GET /api/student/progress — full assessment history + real
    // participation breakdown for "My Progress" (mirrors StudentController::progress())
    public function progress(Request $request): JsonResponse
    {
        $user = Auth::user();

        $groupIds = GroupMembership::where('user_id', $user->user_id)
            ->where('status', 'active')
            ->pluck('group_id');

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

        $activityByDay = collect(range(6, 0))->map(function ($daysAgo) use ($user, $groupIds) {
            $date = now()->subDays($daysAgo);
            $count = Post::where('author_id', $user->user_id)
                ->whereHas('topic', fn ($q) => $q->whereIn('group_id', $groupIds))
                ->whereDate('created_at', $date->toDateString())
                ->count();

            return ['label' => $date->format('D'), 'count' => $count];
        })->values();

        // Quizzes are matched by group_id OR by course_name (a course-targeted
        // quiz has no group_id at all) — same pattern as QuizApiController::index().
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

            return [
                'title'              => $submission->quiz->title,
                'submitted_at_human' => optional($submission->submitted_at)->format('d M Y'),
                'score_pct'          => $scorePct,
                'vs_peer_pct'        => $peerAvgPct !== null ? round($scorePct - $peerAvgPct, 1) : null,
            ];
        })->values();

        $latestRemark = ParticipationScore::where('user_id', $user->user_id)
            ->whereIn('group_id', $groupIds)
            ->latest('created_at')
            ->first();

        return response()->json([
            'post_count'         => $postCount,
            'reply_count'        => $replyCount,
            'participation_pct'  => $participationPct,
            'activity_by_day'    => $activityByDay,
            'assessment_history' => $assessmentHistory,
            'latest_remark'      => $latestRemark ? [
                'criteria'         => $latestRemark->criteria,
                'score'            => $latestRemark->score,
                'created_at_human' => optional($latestRemark->created_at)->diffForHumans(),
            ] : null,
        ]);
    }
}