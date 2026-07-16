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
        $namukasa = User::where('username', 'dr_namukasa')->first();
        $opio     = User::where('username', 'prof_opio')->first();
        $ssali    = User::where('username', 'dr_ssali')->first();
        $students = User::where('system_role', 'student')->get();

        // BSE1206 is deliberately split across TWO groups (two parallel
        // streams of the same course) — this is what lets a single
        // course-targeted quiz demonstrably reach both at once.
        $streamA = Group::create([
            'admin_id'                => $admin->user_id,
            'name'                    => 'BSSE Year 1 - Stream A',
            'course_name'             => 'BSE1206: Software Development Principles',
            'description'             => 'Stream A discussion group for Software Development Principles.',
            'inactivity_warning_days' => 7,
            'blacklist_duration_days' => 30,
        ]);

        $streamB = Group::create([
            'admin_id'                => $admin->user_id,
            'name'                    => 'BSSE Year 1 - Stream B',
            'course_name'             => 'BSE1206: Software Development Principles',
            'description'             => 'Stream B discussion group for Software Development Principles.',
            'inactivity_warning_days' => 7,
            'blacklist_duration_days' => 30,
        ]);

        $webDev = Group::create([
            'admin_id'                => $admin->user_id,
            'name'                    => 'Web Development Cohort',
            'course_name'             => 'BSE1208: Introduction to Web Development',
            'description'             => 'Discussion group for Introduction to Web Development.',
            'inactivity_warning_days' => 7,
            'blacklist_duration_days' => 30,
        ]);

        $oop = Group::create([
            'admin_id'                => $admin->user_id,
            'name'                    => 'OOP Study Group',
            'course_name'             => 'BSE1209: Object Oriented Programming I',
            'description'             => 'Discussion group for Object Oriented Programming I.',
            'inactivity_warning_days' => 5,
            'blacklist_duration_days' => 14,
        ]);

        $dataMgmt = Group::create([
            'admin_id'                => $admin->user_id,
            'name'                    => 'Data Management Group',
            'course_name'             => 'IST1203: Data and Information Management I',
            'description'             => 'Discussion group for Data and Information Management I.',
            'inactivity_warning_days' => 5,
            'blacklist_duration_days' => 14,
        ]);

        $numerical = Group::create([
            'admin_id'                => $admin->user_id,
            'name'                    => 'Numerical Methods Group',
            'course_name'             => 'MTH2203: Numerical Analysis I',
            'description'             => 'Discussion group for Numerical Analysis I.',
            'inactivity_warning_days' => 7,
            'blacklist_duration_days' => 21,
        ]);

        $allGroups = [$streamA, $streamB, $webDev, $oop, $dataMgmt, $numerical];

        // Admin joins every group
        foreach ($allGroups as $group) {
            GroupMembership::create([
                'user_id'   => $admin->user_id,
                'group_id'  => $group->group_id,
                'role'      => 'admin',
                'status'    => 'active',
                'joined_at' => now()->subDays(30),
            ]);
        }

        // Lecturers join the groups for the course(s) they teach
        foreach ([$streamA, $streamB, $webDev] as $group) {
            GroupMembership::create([
                'user_id'   => $namukasa->user_id,
                'group_id'  => $group->group_id,
                'role'      => 'moderator',
                'status'    => 'active',
                'joined_at' => now()->subDays(25),
            ]);
        }

        foreach ([$oop, $dataMgmt] as $group) {
            GroupMembership::create([
                'user_id'   => $opio->user_id,
                'group_id'  => $group->group_id,
                'role'      => 'moderator',
                'status'    => 'active',
                'joined_at' => now()->subDays(25),
            ]);
        }

        GroupMembership::create([
            'user_id'   => $ssali->user_id,
            'group_id'  => $numerical->group_id,
            'role'      => 'moderator',
            'status'    => 'active',
            'joined_at' => now()->subDays(25),
        ]);

        // Students take the same real semester course load: split across
        // the two BSE1206 streams, then everyone joins the other four
        // shared courses — matching an actual BSSE Year 1 timetable
        $streamAStudents = $students->slice(0, 5);
        $streamBStudents = $students->slice(5);

        foreach ($streamAStudents as $student) {
            GroupMembership::create([
                'user_id' => $student->user_id, 'group_id' => $streamA->group_id,
                'role' => 'member', 'status' => 'active', 'joined_at' => now()->subDays(rand(15, 25)),
            ]);
        }

        foreach ($streamBStudents as $student) {
            GroupMembership::create([
                'user_id' => $student->user_id, 'group_id' => $streamB->group_id,
                'role' => 'member', 'status' => 'active', 'joined_at' => now()->subDays(rand(15, 25)),
            ]);
        }

        foreach ([$webDev, $oop, $dataMgmt, $numerical] as $group) {
            foreach ($students as $student) {
                GroupMembership::create([
                    'user_id' => $student->user_id, 'group_id' => $group->group_id,
                    'role' => 'member', 'status' => 'active', 'joined_at' => now()->subDays(rand(10, 20)),
                ]);
            }
        }
    }
}
