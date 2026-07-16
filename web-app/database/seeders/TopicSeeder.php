<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $streamA   = Group::where('name', 'BSSE Year 1 - Stream A')->first();
        $streamB   = Group::where('name', 'BSSE Year 1 - Stream B')->first();
        $webDev    = Group::where('name', 'Web Development Cohort')->first();
        $oop       = Group::where('name', 'OOP Study Group')->first();
        $dataMgmt  = Group::where('name', 'Data Management Group')->first();
        $numerical = Group::where('name', 'Numerical Methods Group')->first();

        $namukasa = User::where('username', 'dr_namukasa')->first();

        $byGroup = [
            $streamA->group_id => [
                ['What is the software development lifecycle?', 'SDLC'],
                ['Agile vs Waterfall — which is better for small teams?', 'Methodology'],
                ['How do we write good user stories?', 'Requirements'],
            ],
            $streamB->group_id => [
                ['Difference between verification and validation', 'Testing'],
                ['What makes a good code review?', 'Best Practices'],
                ['Version control branching strategies for teams', 'Tools'],
            ],
            $webDev->group_id => [
                ['What is a REST API?', 'Web Development'],
                ['Difference between GET and POST in HTTP?', 'Networking'],
                ['How does Laravel handle authentication?', 'Web Development'],
            ],
            $oop->group_id => [
                ['What is the difference between OOP and FP?', 'Programming'],
                ['When should I use an interface vs abstract class?', 'Programming'],
                ['Explain the MVC design pattern', 'Architecture'],
            ],
            $dataMgmt->group_id => [
                ['How do foreign keys work in MySQL?', 'Database'],
                ['Explain normalisation with examples', 'Database'],
                ['Denormalization — when is it actually a good idea?', 'Database'],
            ],
            $numerical->group_id => [
                ['Newton-Raphson method explained simply', 'Root Finding'],
                ['When does the bisection method fail to converge?', 'Root Finding'],
                ['Trapezoidal vs Simpson\'s rule for integration', 'Numerical Integration'],
            ],
        ];

        foreach ($byGroup as $groupId => $topics) {
            $members = \App\Models\GroupMembership::where('group_id', $groupId)
                ->whereHas('user', fn ($q) => $q->where('system_role', 'student'))
                ->with('user')
                ->get()
                ->pluck('user');

            foreach ($topics as [$title, $category]) {
                Topic::create([
                    'group_id'   => $groupId,
                    'creator_id' => $members->isNotEmpty() ? $members->random()->user_id : $namukasa->user_id,
                    'title'      => $title,
                    'category'   => $category,
                    'is_flagged' => false,
                ]);
            }
        }

        // Lecturer posts one assignment-style topic in Stream A
        Topic::create([
            'group_id'   => $streamA->group_id,
            'creator_id' => $namukasa->user_id,
            'title'      => 'Assignment: Document the SDLC phases for a mobile banking app',
            'category'   => 'Assignment',
            'is_flagged' => false,
        ]);
    }
}
