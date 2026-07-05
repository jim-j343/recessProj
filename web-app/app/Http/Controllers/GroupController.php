<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    // List all groups
    public function index()
    {
        $groups = Group::withCount('memberships')->latest()->get();
        $myGroups = GroupMembership::where('user_id', Auth::id())
                        ->where('status', 'active')
                        ->pluck('group_id');
        return view('groups.index', compact('groups', 'myGroups'));
    }

    // Show create form
    public function create()
    {
        return view('groups.create');
    }

    // Store new group
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:120|unique:groups,name',
            'description' => 'nullable|string',
        ]);

        $group = Group::create([
            'name'        => $request->name,
            'description' => $request->description,
            'admin_id'    => Auth::user()->user_id,
        ]);

        // Creator auto-joins as admin
        GroupMembership::create([
            'user_id'   => Auth::user()->user_id,
            'group_id'  => $group->group_id,
            'role'      => 'admin',
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        return redirect()->route('groups.show', $group->group_id)
                         ->with('success', 'Group created successfully.');
    }

    // Show single group
    public function show($id)
    {
        $group = Group::with(['members' => function ($q) {
            $q->wherePivot('status', 'active');
        }])->findOrFail($id);

        $membership = GroupMembership::where('user_id', Auth::user()->user_id)
                          ->where('group_id', $id)
                          ->first();

        return view('groups.show', compact('group', 'membership'));
    }

    // Request to join a group
    public function join($id)
    {
        $existing = GroupMembership::where('user_id', Auth::user()->user_id)
                        ->where('group_id', $id)->first();

        if ($existing) {
            return back()->with('info', 'You already have a membership record for this group.');
        }

        GroupMembership::create([
            'user_id'   => Auth::user()->user_id,
            'group_id'  => $id,
            'role'      => 'member',
            'status'    => 'pending',
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Join request sent. Awaiting approval.');
    }

    // Leave a group
    public function leave($id)
    {
        GroupMembership::where('user_id', Auth::user()->user_id)
            ->where('group_id', $id)
            ->delete();

        return redirect()->route('groups.index')->with('success', 'You have left the group.');
    }

    // Show pending members (group admin only)
    public function members($id)
    {
        $group = Group::findOrFail($id);
        $this->authorizeGroupAdmin($group);

        $pending = $group->members()->wherePivot('status', 'pending')->get();
        $active  = $group->members()->wherePivot('status', 'active')->get();

        return view('groups.members', compact('group', 'pending', 'active'));
    }

    // Approve a pending member
    public function approve($groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        $this->authorizeGroupAdmin($group);

        GroupMembership::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update(['status' => 'active']);

        return back()->with('success', 'Member approved.');
    }

    // Remove/reject a member
    public function removeMember($groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        $this->authorizeGroupAdmin($group);

        GroupMembership::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();

        return back()->with('success', 'Member removed.');
    }

    // Promote a member to moderator
    public function promote($groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        $this->authorizeGroupAdmin($group);

        GroupMembership::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update(['role' => 'moderator']);

        return back()->with('success', 'Member promoted to moderator.');
    }

    // Helper: abort if current user is not group admin
    private function authorizeGroupAdmin(Group $group)
    {
        $membership = GroupMembership::where('user_id', Auth::user()->user_id)
                          ->where('group_id', $group->group_id)
                          ->where('role', 'admin')
                          ->first();

        if (!$membership && Auth::user()->system_role !== 'system_admin') {
            abort(403, 'Only the group admin can do this.');
        }
    }
}