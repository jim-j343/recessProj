<?php

namespace App\Http\Controllers;

use App\Models\GroupMembership;
use App\Models\ParticipationScore;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParticipationController extends Controller
{
    // Map letter grades to numeric scores stored in participation_scores
    private const GRADE_SCORES = [
        'A' => 90, 'B' => 75, 'C' => 60, 'D' => 45, 'F' => 30,
    ];

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

        // Build per-student activity rows
        $rows = $students->map(function ($student) use ($lecturerGroupIds, $topicFilter) {
            $postsQuery = Post::where('author_id', $student->user_id)
                ->whereHas('topic', function ($q) use ($lecturerGroupIds, $topicFilter) {
                    $q->whereIn('group_id', $lecturerGroupIds);
                    if ($topicFilter) {
                        $q->where('topic_id', $topicFilter);
                    }
                });

            $postCount  = (clone $postsQuery)->count();
            $replyCount = (clone $postsQuery)->whereNotNull('parent_post_id')->count();

            // Most recent topic they posted in (for display)
            $latestPost = (clone $postsQuery)->with('topic')->latest('created_at')->first();

            $quality = $postCount >= 5 ? 'High' : ($postCount >= 2 ? 'Medium' : 'Low');

            // Latest saved score, if any
            $existing = ParticipationScore::where('user_id', $student->user_id)
                ->latest('created_at')->first();

            return (object) [
                'student'     => $student,
                'postCount'   => $postCount,
                'replyCount'  => $replyCount,
                'latestTopic' => $latestPost?->topic?->title,
                'quality'     => $quality,
                'existing'    => $existing,
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
            'grades'             => ['required', 'array'],
            'grades.*.grade'     => ['nullable', 'in:A,B,C,D,F'],
            'grades.*.remark'    => ['nullable', 'string', 'max:255'],
        ]);

        $lecturerGroupIds = GroupMembership::where('user_id', Auth::id())
            ->where('status', 'active')
            ->pluck('group_id');

        $saved = 0;

        foreach ($validated['grades'] as $userId => $data) {
            if (empty($data['grade'])) {
                continue; // skip rows the lecturer left ungraded
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
                'criteria'   => $data['remark'] ?: 'Forum participation — grade ' . $data['grade'],
                'score'      => self::GRADE_SCORES[$data['grade']],
                'awarded_by' => Auth::id(),
            ]);
            $saved++;
        }

        return back()->with('success', "Saved grades for {$saved} student(s).");
    }
}
