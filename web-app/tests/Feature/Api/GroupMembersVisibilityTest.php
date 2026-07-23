<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroupMembersVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'username' => 'user_'.uniqid(),
            'email' => uniqid().'@test.com',
            'password_hash' => bcrypt('password'),
            'system_role' => 'student',
            'status' => 'active',
        ]);
    }

    private function addMembership(User $user, Group $group, string $role, string $status): void
    {
        GroupMembership::create([
            'user_id' => $user->user_id,
            'group_id' => $group->group_id,
            'role' => $role,
            'status' => $status,
            'joined_at' => now(),
        ]);
    }

    public function test_active_member_can_view_active_roster_but_not_roles_or_pending_requests(): void
    {
        $admin = $this->makeUser();
        $member = $this->makeUser();
        $pendingUser = $this->makeUser();
        $group = Group::create(['admin_id' => $admin->user_id, 'name' => 'Algorithms']);

        $this->addMembership($admin, $group, 'admin', 'active');
        $this->addMembership($member, $group, 'member', 'active');
        $this->addMembership($pendingUser, $group, 'member', 'pending');

        Sanctum::actingAs($member);

        $response = $this->getJson("/api/groups/{$group->group_id}/members");

        $response->assertOk()->assertJsonCount(2, 'active')->assertJsonCount(0, 'pending');
        foreach ($response->json('active') as $activeMember) {
            $this->assertArrayNotHasKey('role', $activeMember);
        }
    }

    public function test_group_admin_can_view_roles_and_pending_requests(): void
    {
        $admin = $this->makeUser();
        $member = $this->makeUser();
        $pendingUser = $this->makeUser();
        $group = Group::create(['admin_id' => $admin->user_id, 'name' => 'Databases']);

        $this->addMembership($admin, $group, 'admin', 'active');
        $this->addMembership($member, $group, 'member', 'active');
        $this->addMembership($pendingUser, $group, 'member', 'pending');

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/groups/{$group->group_id}/members");

        $response->assertOk()->assertJsonCount(2, 'active')->assertJsonCount(1, 'pending');
        $this->assertSame('admin', $response->json('active.0.role'));
    }
}
