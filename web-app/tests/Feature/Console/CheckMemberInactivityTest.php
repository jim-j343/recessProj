<?php

namespace Tests\Feature\Console;

use App\Models\Blacklist;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckMemberInactivityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'student'): User
    {
        return User::create([
            'username'      => 'user_'.uniqid(),
            'email'         => uniqid().'@test.com',
            'password_hash' => bcrypt('password'),
            'system_role'   => $role,
            'status'        => 'active',
            'last_active_at' => now(),
        ]);
    }

    private function joinGroup(User $user, Group $group): void
    {
        GroupMembership::create([
            'user_id'   => $user->user_id,
            'group_id'  => $group->group_id,
            'role'      => 'member',
            'status'    => 'active',
            'joined_at' => now(),
        ]);
    }

    public function test_command_escalates_inactivity_from_warning_to_blacklisting(): void
    {
        $admin = $this->makeUser('system_admin');
        $group = Group::create([
            'admin_id'               => $admin->user_id,
            'name'                   => 'Data Structures',
            'inactivity_warning_days' => 5,
            'blacklist_duration_days' => 14,
        ]);

        $warnOnce = $this->makeUser();
        $warnTwice = $this->makeUser();
        $blacklist = $this->makeUser();

        foreach ([$warnOnce, $warnTwice, $blacklist] as $member) {
            $this->joinGroup($member, $group);
        }

        $warnOnce->forceFill(['last_active_at' => now()->subDays(6)])->save();
        $warnTwice->forceFill(['last_active_at' => now()->subDays(11)])->save();
        $blacklist->forceFill(['last_active_at' => now()->subDays(16)])->save();

        Warning::create([
            'user_id'        => $warnTwice->user_id,
            'group_id'       => $group->group_id,
            'warning_number' => 1,
            'issued_at'      => now()->subDays(1),
            'deadline'       => now()->addDays(3),
            'is_heeded'      => false,
        ]);

        Warning::create([
            'user_id'        => $blacklist->user_id,
            'group_id'       => $group->group_id,
            'warning_number' => 1,
            'issued_at'      => now()->subDays(10),
            'deadline'       => now()->subDays(5),
            'is_heeded'      => false,
        ]);

        Warning::create([
            'user_id'        => $blacklist->user_id,
            'group_id'       => $group->group_id,
            'warning_number' => 2,
            'issued_at'      => now()->subDays(5),
            'deadline'       => now()->subDays(1),
            'is_heeded'      => false,
        ]);

        $this->artisan('members:check-inactivity')->assertExitCode(0);

        $this->assertDatabaseHas('warnings', [
            'user_id'        => $warnOnce->user_id,
            'group_id'       => $group->group_id,
            'warning_number' => 1,
        ]);

        $this->assertDatabaseHas('warnings', [
            'user_id'        => $warnTwice->user_id,
            'group_id'       => $group->group_id,
            'warning_number' => 2,
        ]);

        $this->assertDatabaseHas('blacklist', [
            'user_id'  => $blacklist->user_id,
            'group_id' => $group->group_id,
        ]);

        $this->assertSame('blacklisted', $blacklist->refresh()->status);
        $this->assertSame('blacklisted', GroupMembership::where('user_id', $blacklist->user_id)->where('group_id', $group->group_id)->first()->status);
    }
}
