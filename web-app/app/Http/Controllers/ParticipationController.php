<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
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
use App\Models\Group;

class ParticipationController extends Controller
{
    // How many replies earn full participation marks (10/10).
    // Public so the student-facing pages use the identical figure — if this
    // ever changes, the lecturer's grading table and the student's own views
    // must move together.
    public const REPLIES_FOR_FULL_MARKS = 10;

    // Lecturer: grading table, scoped to ONE course/group at a time.
    // Participation and quiz average must describe the same course — a mark
    // belongs to the group it was earned in, not to an average blended across
    // every group the lecturer happens to share with the student.
    public function grade(Request $request)
    {
        // The lecturer's own groups, each carrying its course name
        $groups = Group::whereIn('group_id',
                GroupMembership::where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->pluck('group_id')
            )
            ->orderBy('course_name')
            ->orderBy('name')
            ->get();

        $search = $request->query('search');

        // Selected group — defaults to the first, so the page is never empty
        $group = $groups->firstWhere('group_id', (int) $request->query('group'))
            ?? $groups->first();

        if (!$group) {
            return view('participation.grade', [
                'rows'   => collect(),
                'groups' => $groups,
                'group'  => null,
                'search' => $search,
            ]);
        }

        // Students in THIS group only
        $studentIds = GroupMembership::where('group_id', $group->group_id)
            ->where('status', 'active')
            ->pluck('user_id');

        $studentsQuery = User::whereIn('user_id', $studentIds)
            ->where('system_role', 'student');

        if ($search) {
            $studentsQuery->where('username', 'like', "%{$search}%");
        }

        $students = $studentsQuery->orderBy('username')->get();

        // parent_post_id is never set by any current UI flow, so the opening
        // post of each topic is the lowest post_id for that topic —
        // everything else in this group counts as a reply.
        $openingPostIds = Post::select('topic_id', DB::raw('MIN(post_id) as post_id'))
            ->whereHas('topic', fn ($q) => $q->where('group_id', $group->group_id))
            ->groupBy('topic_id')
            ->pluck('post_id');

        // Quizzes for THIS course: set directly on the group, or targeted at
        // its course name by any lecturer teaching the same unit.
        $quizIds = Quiz::where(function ($q) use ($group) {
                $q->where('group_id', $group->group_id);
                if ($group->course_name) {
                    $q->orWhere('course_name', $group->course_name);
                }
            })
            ->pluck('quiz_id');

        $quizTotalMarks = Question::whereIn('quiz_id', $quizIds)
            ->select('quiz_id', DB::raw('SUM(marks) as total'))
            ->groupBy('quiz_id')
            ->pluck('total', 'quiz_id');

        $rows = $students->map(function ($student) use ($group, $openingPostIds, $quizIds, $quizTotalMarks) {
            $postsQuery = Post::where('author_id', $student->user_id)
                ->whereHas('topic', fn ($q) => $q->where('group_id', $group->group_id));

            $postCount  = (clone $postsQuery)->count();
            $replyCount = (clone $postsQuery)->whereNotIn('post_id', $openingPostIds)->count();

            // Same formula, same scope, as the student's own dashboard card
            $participationScore = min($replyCount, self::REPLIES_FOR_FULL_MARKS);
            $participationPct   = $participationScore * (100 / self::REPLIES_FOR_FULL_MARKS);

            // Each quiz converted to a % of its own total marks, so quizzes of
            // different sizes average together fairly
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

            // Blend participation and test average. No quizzes taken yet →
            // fall back to participation alone rather than averaging against
            // a phantom zero.
            $suggestedScore = $quizAvgPct !== null
                ? round(($participationPct + $quizAvgPct) / 2, 1)
                : round($participationPct, 1);

            // Last score saved for this student IN THIS GROUP
            $existing = ParticipationScore::where('user_id', $student->user_id)
                ->where('group_id', $group->group_id)
                ->latest('created_at')->first();

            return (object) [
                'student'          => $student,
                'postCount'        => $postCount,
                'replyCount'       => $replyCount,
                'participationPct' => round($participationPct, 1),
                'quizAvgPct'       => $quizAvgPct,
                'quizCount'        => $quizCount,
                'suggestedScore'   => $suggestedScore,
                'existing'         => $existing,
            ];
        });

        return view('participation.grade', [
            'rows'   => $rows,
            'groups' => $groups,
            'group'  => $group,
            'search' => $search,
        ]);
    }

    // Lecturer: save all grades at once
    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id'         => ['required', 'integer'],
            'grades'           => ['required', 'array'],
            'grades.*.score'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            // criteria column is varchar(120) — validate to match, or long
            // remarks fail to insert
            'grades.*.remark'  => ['nullable', 'string', 'max:120'],
        ]);

        $groupId = (int) $validated['group_id'];

        // The lecturer can only grade a group they actually belong to
        $isLecturersGroup = GroupMembership::where('user_id', Auth::id())
            ->where('group_id', $groupId)
            ->where('status', 'active')
            ->exists();

        if (!$isLecturersGroup) {
            return back()->with('error', 'You can only grade groups you belong to.');
        }

        $group = Group::find($groupId);
        $saved = 0;

        foreach ($validated['grades'] as $userId => $data) {
            if (!isset($data['score']) || $data['score'] === '') {
                continue; // skip rows the lecturer left blank
            }

            // Only grade students who are actually members of this group
            $isMember = GroupMembership::where('user_id', $userId)
                ->where('group_id', $groupId)
                ->where('status', 'active')
                ->exists();

            if (!$isMember) {
                continue;
            }

            ParticipationScore::create([
                'user_id'    => $userId,
                'group_id'   => $groupId,
                'criteria'   => $data['remark']
                    ?: Str::limit('Participation + quiz average — '.($group->course_name ?: $group->name), 115),
                'score'      => round((float) $data['score'], 2),
                'awarded_by' => Auth::id(),
            ]);
            $saved++;
        }

        return back()->with('success', "Saved grades for {$saved} student(s) in {$group->name}.");
    }

    // JSON version of grade(), used by the desktop client. Scoped to one
    // course/group exactly like the web page, so both interfaces show the
    // same numbers for the same student.
    public function gradeJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $groups = Group::whereIn('group_id',
                GroupMembership::where('user_id', $request->user()->user_id)
                    ->where('status', 'active')
                    ->pluck('group_id')
            )
            ->orderBy('course_name')
            ->orderBy('name')
            ->get();

        $group = $groups->firstWhere('group_id', (int) $request->query('group'))
            ?? $groups->first();

        if (!$group) {
            return response()->json(['rows' => [], 'topics' => [], 'groups' => [], 'selected_group' => null]);
        }

        $search = $request->query('search');

        // Topics are still returned so the desktop's existing dropdown
        // populates, but they no longer scope the calculation — participation
        // is per course, matching the web grading page.
        $topics = Topic::where('group_id', $group->group_id)
            ->orderBy('title')->get();

        $studentIds = GroupMembership::where('group_id', $group->group_id)
            ->where('status', 'active')
            ->pluck('user_id');

        $studentsQuery = User::whereIn('user_id', $studentIds)
            ->where('system_role', 'student');

        if ($search) {
            $studentsQuery->where('username', 'like', "%{$search}%");
        }

        $students = $studentsQuery->orderBy('username')->get();

        $openingPostIds = Post::select('topic_id', DB::raw('MIN(post_id) as post_id'))
            ->whereHas('topic', fn ($q) => $q->where('group_id', $group->group_id))
            ->groupBy('topic_id')
            ->pluck('post_id');

        $quizIds = Quiz::where(function ($q) use ($group) {
                $q->where('group_id', $group->group_id);
                if ($group->course_name) {
                    $q->orWhere('course_name', $group->course_name);
                }
            })
            ->pluck('quiz_id');

        $quizTotalMarks = Question::whereIn('quiz_id', $quizIds)
            ->select('quiz_id', DB::raw('SUM(marks) as total'))
            ->groupBy('quiz_id')
            ->pluck('total', 'quiz_id');

        $rows = $students->map(function ($student) use ($group, $openingPostIds, $quizIds, $quizTotalMarks) {
            $postsQuery = Post::where('author_id', $student->user_id)
                ->whereHas('topic', fn ($q) => $q->where('group_id', $group->group_id));

            $postCount  = (clone $postsQuery)->count();
            $replyCount = (clone $postsQuery)->whereNotIn('post_id', $openingPostIds)->count();

            $participationScore = min($replyCount, self::REPLIES_FOR_FULL_MARKS);
            $participationPct   = $participationScore * (100 / self::REPLIES_FOR_FULL_MARKS);

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

            $suggestedScore = $quizAvgPct !== null
                ? round(($participationPct + $quizAvgPct) / 2, 1)
                : round($participationPct, 1);

            // Last score saved for this student IN THIS GROUP
            $existing = ParticipationScore::where('user_id', $student->user_id)
                ->where('group_id', $group->group_id)
                ->latest('created_at')->first();

            return [
                'user_id'            => $student->user_id,
                'username'           => $student->username,
                'post_count'         => $postCount,
                'reply_count'        => $replyCount,
                'participation_pct'  => round($participationPct, 1),
                'quiz_avg_pct'       => $quizAvgPct,
                'quiz_count'         => $quizCount,
                'suggested_score'    => $suggestedScore,
                'existing_score'     => $existing ? $existing->score : null,
                'existing_remark'    => $existing ? $existing->criteria : null,
            ];
        });

        return response()->json([
            'rows'   => $rows->values(),
            'topics' => $topics,
            // New — lets a future desktop build offer the course selector
            'groups' => $groups->map(fn ($g) => [
                'group_id'    => $g->group_id,
                'name'        => $g->name,
                'course_name' => $g->course_name,
            ])->values(),
            'selected_group' => [
                'group_id'    => $group->group_id,
                'name'        => $group->name,
                'course_name' => $group->course_name,
            ],
        ]);
    }

    public function saveGrades(Request $request): \Illuminate\Http\JsonResponse
    {
        $grades = $request->input('grades', []);

        $groups = Group::whereIn('group_id',
                GroupMembership::where('user_id', $request->user()->user_id)
                    ->where('status', 'active')
                    ->pluck('group_id')
            )->orderBy('course_name')->orderBy('name')->get();

        // Same default as gradeJson(), so a mark is filed against the course
        // whose numbers the lecturer was actually looking at
        $group = $groups->firstWhere('group_id', (int) $request->input('group_id'))
            ?? $groups->first();

        if (!$group) {
            return response()->json(['message' => 'You are not a member of any group.'], 422);
        }

        $saved = 0;

        foreach ($grades as $userId => $data) {
            if (!isset($data['score']) || $data['score'] === '') {
                continue;
            }

            // Only grade students actually in this group
            $isMember = GroupMembership::where('user_id', $userId)
                ->where('group_id', $group->group_id)
                ->where('status', 'active')
                ->exists();

            if (!$isMember) {
                continue;
            }

            \App\Models\ParticipationScore::create([
                'user_id'    => (int) $userId,
                'group_id'   => $group->group_id,
                'criteria'   => !empty($data['remark'])
                    ? \Illuminate\Support\Str::limit($data['remark'], 115)
                    : \Illuminate\Support\Str::limit('Participation + quiz average — '.($group->course_name ?: $group->name), 115),
                'score'      => round((float) $data['score'], 2),
                'awarded_by' => $request->user()->user_id,
            ]);
            $saved++;
        }

        return response()->json(['message' => "Saved grades for {$saved} student(s) in {$group->name}."]);
    }
}
