<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupApiController extends Controller
{
    /** GET /api/groups */
    public function index(Request $request): JsonResponse
    {
        $groups = Group::withCount('memberships')->latest()->get()
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

    private function shape(Group $g, int $userId): array
    {
        $membership = GroupMembership::where('user_id', $userId)
            ->where('group_id', $g->group_id)->first();
        return [
            'group_id'    => (int) $g->group_id,
            'name'        => $g->name,
            'description' => $g->description,
            'admin_id'    => (int) $g->admin_id,
            'member_count'=> (int) ($g->memberships_count ?? 0),
            'my_status'   => $membership?->status,
            'my_role'     => $membership?->role,
        ];
    }
}