<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\GroupMembership;
use App\Models\ParticipationScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParticipationController extends Controller
{
    // Lecturer: pick a group, then grade its active student members
    public function grade(Request $request)
    {
        // Groups this lecturer actually belongs to (same pattern as QuizController::create)
        $groups = GroupMembership::where('user_id', Auth::id())
            ->where('status', 'active')
            ->with('group')
            ->get()
            ->pluck('group');

        $selectedGroupId = $request->query('group_id') ?? $groups->first()?->group_id;
        $selectedGroup = $groups->firstWhere('group_id', (int) $selectedGroupId);

        $students = collect();
        if ($selectedGroup) {
            $students = GroupMembership::where('group_id', $selectedGroup->group_id)
                ->where('status', 'active')
                ->with(['user' => fn ($q) => $q->where('system_role', 'student')])
                ->get()
                ->pluck('user')
                ->filter()
                ->values();
        }

        return view('participation.grade', compact('groups', 'selectedGroup', 'students'));
    }

    // Lecturer: persist a batch of participation scores for one group/criteria
    public function save(Request $request)
    {
        $validated = $request->validate([
            'group_id'        => ['required', 'exists:groups,group_id'],
            'criteria'        => ['required', 'string', 'max:120'],
            'scores'          => ['required', 'array'],
            'scores.*'        => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $awarded = 0;

        foreach ($validated['scores'] as $userId => $score) {
            if ($score === null || $score === '') {
                continue;
            }

            ParticipationScore::create([
                'user_id'    => $userId,
                'group_id'   => $validated['group_id'],
                'criteria'   => $validated['criteria'],
                'score'      => $score,
                'awarded_by' => Auth::id(),
            ]);

            ActivityLog::create([
                'user_id'     => $userId,
                'group_id'    => $validated['group_id'],
                'action_type' => 'participation_graded',
                'meta'        => ['criteria' => $validated['criteria'], 'score' => $score],
                'logged_at'   => now(),
            ]);

            $awarded++;
        }

        return back()->with('success', "Saved participation scores for {$awarded} student(s).");
    }
}
