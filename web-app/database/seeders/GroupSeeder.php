<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::where('username', 'admin')->first();
        $students = User::where('system_role', 'student')->get();
        $lecturer = User::where('username', 'dr_namukasa')->first();

        // Create two groups
        $groupA = Group::create([
            'admin_id'                => $admin->user_id,
            'name'                    => 'Computer Science Year 2',
            'course_name'             => 'CS201: Database Systems',
            'description'             => 'Discussion group for Year 2 CS students.',
            'inactivity_warning_days' => 7,
            'blacklist_duration_days' => 30,
        ]);

        $groupB = Group::create([
            'admin_id'                => $admin->user_id,
            'name'                    => 'Software Engineering Year 3',
            'course_name'             => 'SE301: Software Architecture',
            'description'             => 'Discussion group for Year 3 SE students.',
            'inactivity_warning_days' => 5,
            'blacklist_duration_days' => 14,
        ]);

        // Admin joins both as admin
        foreach ([$groupA, $groupB] as $group) {
            GroupMembership::create([
                'user_id'   => $admin->user_id,
                'group_id'  => $group->group_id,
                'role'      => 'admin',
                'status'    => 'active',
                'joined_at' => now()->subDays(30),
            ]);
        }

        // Lecturer joins Group A as moderator
        GroupMembership::create([
            'user_id'   => $lecturer->user_id,
            'group_id'  => $groupA->group_id,
            'role'      => 'moderator',
            'status'    => 'active',
            'joined_at' => now()->subDays(25),
        ]);

        // Split students between groups
        foreach ($students->take(5) as $student) {
            GroupMembership::create([
                'user_id'   => $student->user_id,
                'group_id'  => $groupA->group_id,
                'role'      => 'member',
                'status'    => 'active',
                'joined_at' => now()->subDays(rand(5, 20)),
            ]);
        }

        foreach ($students->skip(5) as $student) {
            GroupMembership::create([
                'user_id'   => $student->user_id,
                'group_id'  => $groupB->group_id,
                'role'      => 'member',
                'status'    => 'active',
                'joined_at' => now()->subDays(rand(5, 20)),
            ]);
        }
    }
}
