<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMembership;
use App\Models\GroupRemoval;
use App\Models\Notification;
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

        $pendingInvitations = GroupInvitation::where('invited_user_id', Auth::id())
            ->where('status', 'pending')
            ->with(['group', 'invitedBy'])
            ->latest()
            ->get();

        return view('groups.index', compact('groups', 'pendingInvitations'));
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

    public function invite(Request $request, Group $group)
    {
        if ($group->admin_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ]);

        $invitedUser = User::where('username', $validated['username'])->first();

        if ($invitedUser->user_id === Auth::id()) {
            return back()->with('error', 'You cannot invite yourself.');
        }

        if ($group->isMember($invitedUser->user_id)) {
            return back()->with('error', "{$invitedUser->username} is already a member of this group.");
        }

        $alreadyPending = GroupInvitation::where('group_id', $group->group_id)
            ->where('invited_user_id', $invitedUser->user_id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()->with('error', "{$invitedUser->username} already has a pending invitation to this group.");
        }

        GroupInvitation::create([
            'group_id'        => $group->group_id,
            'invited_user_id' => $invitedUser->user_id,
            'invited_by'      => Auth::id(),
            'status'          => 'pending',
        ]);

        Notification::notify($invitedUser->user_id, 'group_invite');

        return back()->with('success', "Invitation sent to {$invitedUser->username}.");
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
