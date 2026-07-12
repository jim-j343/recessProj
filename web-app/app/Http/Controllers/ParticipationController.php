<?php

namespace App\Http\Controllers;

use App\Models\GroupMembership;
use App\Models\ParticipationScore;
use App\Models\Post;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParticipationController extends Controller
{
    // How many replies earn full participation marks (10/10)
    private const REPLIES_FOR_FULL_MARKS = 10;

    // Lecturer: grading table with real students and real activity
    public function grade(Request $request)
    {
        $lecturerGroupIds = GroupMembership::where('user_id', Auth::id())
            ->where('status', 'active')
            ->pluck('group_id');

        // Topics in the lecturer's groups (for the filter dropdown)
        $topics = Topic::whereIn('group_id', $lecturerGroupIds)
            ->orderBy('title')->get();

        $topicFilter = $request->query('topic');
        $search      = $request->query('search');

        // Students who share a group with the lecturer
        $studentIds = GroupMembership::whereIn('group_id', $lecturerGroupIds)
            ->where('status', 'active')
            ->pluck('user_id');

        $studentsQuery = User::whereIn('user_id', $studentIds)
            ->where('system_role', 'student');

        if ($search) {
            $studentsQuery->where('username', 'like', "%{$search}%");
        }

        $students = $studentsQuery->orderBy('username')->get();

        // parent_post_id is never set by any current UI flow (the reply bar
        // only ever sends 'content'), so it can't be used to identify
        // replies. Instead: the opening post of each topic (the one created
        // alongside the topic itself) is the lowest post_id for that
        // topic_id — everything else in scope counts as a reply.
        $openingPostIds = Post::select('topic_id', DB::raw('MIN(post_id) as post_id'))
            ->whereHas('topic', function ($q) use ($lecturerGroupIds, $topicFilter) {
                $q->whereIn('group_id', $lecturerGroupIds);
                if ($topicFilter) {
                    $q->where('topic_id', $topicFilter);
                }
            })
            ->groupBy('topic_id')
            ->pluck('post_id');

        // Quizzes that belong to the lecturer's groups, and how many total
        // marks each one is worth — needed to turn a raw quiz score into a
        // percentage so multiple quizzes can be averaged together fairly.
        $quizIds = Quiz::whereIn('group_id', $lecturerGroupIds)->pluck('quiz_id');

        $quizTotalMarks = Question::whereIn('quiz_id', $quizIds)
            ->select('quiz_id', DB::raw('SUM(marks) as total'))
            ->groupBy('quiz_id')
            ->pluck('total', 'quiz_id');

        // Build per-student activity rows
        $rows = $students->map(function ($student) use ($lecturerGroupIds, $topicFilter, $openingPostIds, $quizIds, $quizTotalMarks) {
            $postsQuery = Post::where('author_id', $student->user_id)
                ->whereHas('topic', function ($q) use ($lecturerGroupIds, $topicFilter) {
                    $q->whereIn('group_id', $lecturerGroupIds);
                    if ($topicFilter) {
                        $q->where('topic_id', $topicFilter);
                    }
                });

            $postCount  = (clone $postsQuery)->count();
            $replyCount = (clone $postsQuery)->whereNotIn('post_id', $openingPostIds)->count();

            // Most recent topic they posted in (for display)
            $latestPost = (clone $postsQuery)->with('topic')->latest('created_at')->first();

            // Participation: replies scaled to a mark out of 10, then to a %
            $participationScore = min($replyCount, self::REPLIES_FOR_FULL_MARKS);
            $participationPct   = $participationScore * (100 / self::REPLIES_FOR_FULL_MARKS);

            // Test average: mean of every completed quiz in these groups,
            // each converted to a % of that quiz's own total marks so
            // multiple quizzes of different sizes average fairly
            $completedSubmissions = Submission::where('user_id', $student->user_id)
                ->whereIn('quiz_id', $quizIds)
                ->whereNotNull('submitted_at')
                ->get();

            $quizPercentages = $completedSubmissions
                ->map(function ($submission) use ($quizTotalMarks) {
                    $total = $quizTotalMarks[$submission->quiz_id] ?? 0;
                    return $total > 0 ? ($submission->score / $total) * 100 : null;
                })
                ->filter(fn ($pct) => $pct !== null);

            $quizCount  = $quizPercentages->count();
            $quizAvgPct = $quizCount ? round($quizPercentages->avg(), 1) : null;

            // Suggested final score: blend participation and test average.
            // If they haven't taken any quiz yet, fall back to participation
            // alone rather than averaging against a phantom zero.
            $suggestedScore = $quizAvgPct !== null
                ? round(($participationPct + $quizAvgPct) / 2, 1)
                : round($participationPct, 1);

            // Latest saved score, if any
            $existing = ParticipationScore::where('user_id', $student->user_id)
                ->latest('created_at')->first();

            return (object) [
                'student'           => $student,
                'postCount'         => $postCount,
                'replyCount'        => $replyCount,
                'latestTopic'       => $latestPost?->topic?->title,
                'participationPct'  => round($participationPct, 1),
                'quizAvgPct'        => $quizAvgPct,
                'quizCount'         => $quizCount,
                'suggestedScore'    => $suggestedScore,
                'existing'          => $existing,
            ];
        });

        // Hide students with zero activity when filtering by a specific topic
        if ($topicFilter) {
            $rows = $rows->filter(fn ($r) => $r->postCount > 0)->values();
        }

        return view('participation.grade', compact('rows', 'topics', 'topicFilter', 'search'));
    }

    // Lecturer: save all grades at once
    public function store(Request $request)
    {
        $validated = $request->validate([
            'grades'           => ['required', 'array'],
            'grades.*.score'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.remark'  => ['nullable', 'string', 'max:255'],
        ]);

        $lecturerGroupIds = GroupMembership::where('user_id', Auth::id())
            ->where('status', 'active')
            ->pluck('group_id');

        $saved = 0;

        foreach ($validated['grades'] as $userId => $data) {
            if (!isset($data['score']) || $data['score'] === '') {
                continue; // skip rows the lecturer left blank
            }

            // Score is stored against the group both lecturer and student share
            $groupId = GroupMembership::where('user_id', $userId)
                ->whereIn('group_id', $lecturerGroupIds)
                ->value('group_id');

            if (!$groupId) {
                continue;
            }

            ParticipationScore::create([
                'user_id'    => $userId,
                'group_id'   => $groupId,
                'criteria'   => $data['remark'] ?: 'Forum participation + quiz average (auto-calculated)',
                'score'      => round((float) $data['score'], 2),
                'awarded_by' => Auth::id(),
            ]);
            $saved++;
        }

        return back()->with('success', "Saved grades for {$saved} student(s).");
    }
}
