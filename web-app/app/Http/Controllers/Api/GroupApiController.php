<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\GroupRemoval;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupApiController extends Controller
{
    /** GET /api/groups */
    public function index(Request $request): JsonResponse
    {
        $groups = Group::withCount(['memberships', 'topics'])->with('admin')->latest()->get()
            ->map(fn($g) => $this->shape($g, $request->user()->user_id));
        return response()->json($groups);
    }

    /** POST /api/groups */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120', 'unique:groups,name'],
            'description' => ['nullable', 'string'],
        ]);

        $group = Group::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'admin_id'    => $request->user()->user_id,
        ]);

        GroupMembership::create([
            'user_id'   => $request->user()->user_id,
            'group_id'  => $group->group_id,
            'role'      => 'admin',
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        return response()->json($this->shape($group, $request->user()->user_id), 201);
    }

    /** PUT /api/groups/{group} */
    public function update(Request $request, Group $group): JsonResponse
    {
        if ($group->admin_id !== $request->user()->user_id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:120', 'unique:groups,name,' . $group->group_id . ',group_id'],
            'course_name'             => ['required', 'string', 'max:150'],
            'description'             => ['nullable', 'string', 'max:500'],
            'inactivity_warning_days' => ['required', 'integer', 'min:1'],
            'blacklist_duration_days' => ['required', 'integer', 'min:1'],
        ]);

        $group->update($validated);

        return response()->json($this->shape($group, $request->user()->user_id));
    }

    /** DELETE /api/groups/{group} */
    public function destroy(Request $request, Group $group): JsonResponse
    {
        if ($group->admin_id !== $request->user()->user_id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        
        $group->delete();
        return response()->json(['message' => 'Group deleted.']);
    }

    /** POST /api/groups/{group}/join */
    public function join(Request $request, Group $group): JsonResponse
    {
        $userId = $request->user()->user_id;
        $existing = GroupMembership::where('user_id', $userId)
            ->where('group_id', $group->group_id)->first();

        if ($existing) {
            return response()->json(['message' => 'Already a member or request pending.'], 409);
        }

        GroupMembership::create([
            'user_id'   => $userId,
            'group_id'  => $group->group_id,
            'role'      => 'member',
            'status'    => 'pending',
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'Join request sent.']);
    }

    /** GET /api/groups/{group}/members  (admin only) */
    public function members(Request $request, Group $group): JsonResponse
    {
        if ($group->admin_id !== $request->user()->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $pending = $group->members()->wherePivot('status', 'pending')
            ->get()->map(fn($u) => ['user_id' => $u->user_id, 'username' => $u->username, 'status' => 'pending']);

        $active = $group->members()->wherePivot('status', 'active')
            ->get()->map(fn($u) => ['user_id' => $u->user_id, 'username' => $u->username,
                'role' => $u->pivot->role, 'status' => 'active']);

        return response()->json(['pending' => $pending, 'active' => $active]);
    }

    /** PATCH /api/groups/{group}/members/{userId}/approve */
    public function approve(Request $request, Group $group, $userId): JsonResponse
    {
        if ($group->admin_id !== $request->user()->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        GroupMembership::where('group_id', $group->group_id)
            ->where('user_id', $userId)
            ->update(['status' => 'active']);

        return response()->json(['message' => 'Member approved.']);
    }

    /** POST /api/groups/{group}/add-member */
    public function addMember(Request $request, Group $group): JsonResponse
    {
        if ($group->admin_id !== $request->user()->user_id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ]);

        $newMember = User::where('username', $validated['username'])->first();

        if ($newMember->user_id === $request->user()->user_id) {
            return response()->json(['message' => 'You are already the group admin.'], 400);
        }

        if ($group->isMember($newMember->user_id)) {
            return response()->json(['message' => "{$newMember->username} is already a member of this group."], 400);
        }

        GroupMembership::create([
            'user_id'   => $newMember->user_id,
            'group_id'  => $group->group_id,
            'role'      => 'member',
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        Notification::notify($newMember->user_id, 'added_to_group', null, null, $group->group_id);

        ActivityLog::create([
            'user_id'     => $request->user()->user_id,
            'group_id'    => $group->group_id,
            'action_type' => 'member_added',
            'meta'        => [
                'added_user_id'  => $newMember->user_id,
                'added_username' => $newMember->username,
            ],
            'logged_at'   => now(),
        ]);

        return response()->json(['message' => "{$newMember->username} was added to the group."]);
    }

    /** DELETE /api/groups/{group}/members/{userId} */
    public function removeMember(Request $request, Group $group, $userId): JsonResponse
    {
        if ($group->admin_id !== $request->user()->user_id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $user = User::findOrFail($userId);

        if ($user->user_id === $group->admin_id) {
            return response()->json(['message' => 'The group admin cannot be removed.'], 400);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $wasMember = GroupMembership::where('user_id', $user->user_id)
            ->where('group_id', $group->group_id)
            ->exists();

        if (! $wasMember) {
            return response()->json(['message' => 'That user is not a member of this group.'], 400);
        }

        GroupMembership::where('user_id', $user->user_id)
            ->where('group_id', $group->group_id)
            ->delete();

        GroupRemoval::create([
            'group_id'        => $group->group_id,
            'removed_user_id' => $user->user_id,
            'removed_by'      => $request->user()->user_id,
            'reason'          => $validated['reason'] ?? null,
            'reviewed'        => false,
        ]);

        ActivityLog::create([
            'user_id'     => $request->user()->user_id,
            'group_id'    => $group->group_id,
            'action_type' => 'member_removed',
            'meta'        => [
                'removed_user_id'  => $user->user_id,
                'removed_username' => $user->username,
            ],
            'logged_at'   => now(),
        ]);

        return response()->json(['message' => "{$user->username} was removed from the group."]);
    }

    private function shape(Group $g, int $userId): array
    {
        $membership = GroupMembership::where('user_id', $userId)
            ->where('group_id', $g->group_id)->first();
        return [
            'group_id'    => (int) $g->group_id,
            'name'        => $g->name,
            'description' => $g->description,
            'admin_id'    => (int) $g->admin_id,
            'admin_name'  => $g->admin?->username ?? 'Unknown',
            'member_count'=> (int) ($g->memberships_count ?? 0),
            'topics_count'=> (int) ($g->topics_count ?? 0),
            'course_name' => $g->course_name,
            'warning_days'=> (int) $g->inactivity_warning_days,
            'blacklist_days'=> (int) $g->blacklist_duration_days,
            'my_status'   => $membership?->status,
            'my_role'     => $membership?->role,
        ];
    }
}