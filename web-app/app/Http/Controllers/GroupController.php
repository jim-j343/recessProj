<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GroupRemoval;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::with('admin')
            ->withCount('memberships')
            ->withCount('topics')
            ->latest()
            ->get();

        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        return view('groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:120', 'unique:groups,name'],
            'course_name'             => ['required', 'string', 'max:150'],
            'description'             => ['nullable', 'string', 'max:500'],
            'inactivity_warning_days' => ['required', 'integer', 'min:1'],
            'blacklist_duration_days' => ['required', 'integer', 'min:1'],
        ]);

        $group = Group::create([
            'admin_id'                => Auth::id(),
            'name'                    => $validated['name'],
            'course_name'             => $validated['course_name'],
            'description'             => $validated['description'] ?? null,
            'inactivity_warning_days' => $validated['inactivity_warning_days'],
            'blacklist_duration_days' => $validated['blacklist_duration_days'],
        ]);

        GroupMembership::create([
            'user_id'   => Auth::id(),
            'group_id'  => $group->group_id,
            'role'      => 'admin',
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        return redirect()->route('groups.show', $group->group_id)
            ->with('success', 'Group created successfully!');
    }

    public function show(Group $group)
    {
        $group->load(['admin', 'topics.creator', 'memberships.user']);
        $isMember = $group->isMember(Auth::id());

        // Recent "X was removed" announcements, WhatsApp-style, for everyone
        // still in the group to see
        $removalAnnouncements = ActivityLog::where('group_id', $group->group_id)
            ->where('action_type', 'member_removed')
            ->with('user')
            ->latest('logged_at')
            ->take(10)
            ->get();

        return view('groups.show', compact('group', 'isMember', 'removalAnnouncements'));
    }

    public function join(Group $group)
    {
        if ($group->isMember(Auth::id())) {
            return back()->with('info', 'You are already a member.');
        }

        GroupMembership::create([
            'user_id'   => Auth::id(),
            'group_id'  => $group->group_id,
            'role'      => 'member',
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        return back()->with('success', 'You joined ' . $group->name . '!');
    }

    public function leave(Group $group)
    {
        if ($group->admin_id === Auth::id()) {
            return back()->with('error', 'Group admin cannot leave.');
        }

        GroupMembership::where('user_id', Auth::id())
            ->where('group_id', $group->group_id)
            ->delete();

        return redirect()->route('groups.index')
            ->with('success', 'You left ' . $group->name . '.');
    }

    public function edit(Group $group)
    {
        if ($group->admin_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        return view('groups.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        if ($group->admin_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:120', 'unique:groups,name,' . $group->group_id . ',group_id'],
            'course_name'             => ['required', 'string', 'max:150'],
            'description'             => ['nullable', 'string', 'max:500'],
            'inactivity_warning_days' => ['required', 'integer', 'min:1'],
            'blacklist_duration_days' => ['required', 'integer', 'min:1'],
        ]);

        $group->update($validated);

        return redirect()->route('groups.show', $group->group_id)
            ->with('success', 'Group settings updated.');
    }

    public function removeMember(Request $request, Group $group, User $user)
    {
        if ($group->admin_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($user->user_id === $group->admin_id) {
            return back()->with('error', 'The group admin cannot be removed.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $wasMember = GroupMembership::where('user_id', $user->user_id)
            ->where('group_id', $group->group_id)
            ->exists();

        if (! $wasMember) {
            return back()->with('error', 'That user is not a member of this group.');
        }

        GroupMembership::where('user_id', $user->user_id)
            ->where('group_id', $group->group_id)
            ->delete();

        // Removing a member is NOT blacklisting them — it just files a
        // report for the system admin to review, per the design decision
        // that group admins don't have blacklist power.
        GroupRemoval::create([
            'group_id'        => $group->group_id,
            'removed_user_id' => $user->user_id,
            'removed_by'      => Auth::id(),
            'reason'          => $validated['reason'] ?? null,
            'reviewed'        => false,
        ]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'group_id'    => $group->group_id,
            'action_type' => 'member_removed',
            'meta'        => [
                'removed_user_id'  => $user->user_id,
                'removed_username' => $user->username,
            ],
            'logged_at'   => now(),
        ]);

        return back()->with('success', "{$user->username} was removed from the group.");
    }

    public function destroy(Group $group)
    {
        if ($group->admin_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }
        $group->delete();
        return redirect()->route('groups.index')
            ->with('success', 'Group deleted.');
    }
}
