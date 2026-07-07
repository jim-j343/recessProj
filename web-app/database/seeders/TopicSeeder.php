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
        $groupA   = Group::where('name', 'Computer Science Year 2')->first();
        $groupB   = Group::where('name', 'Software Engineering Year 3')->first();
        $students = User::where('system_role', 'student')->get();
        $lecturer = User::where('username', 'dr_namukasa')->first();

        $topicsGroupA = [
            ['How do foreign keys work in MySQL?',         'Database'],
            ['Difference between GET and POST in HTTP?',   'Networking'],
            ['Explain normalisation with examples',        'Database'],
            ['What is a REST API?',                        'Web Development'],
            ['Git branching strategies for teams',         'Tools'],
        ];

        $topicsGroupB = [
            ['What is the difference between OOP and FP?', 'Programming'],
            ['How does Laravel handle authentication?',    'Web Development'],
            ['Explain the MVC design pattern',             'Architecture'],
            ['When should I use an interface vs abstract?','Programming'],
            ['How to write unit tests in Java?',           'Testing'],
        ];

        foreach ($topicsGroupA as [$title, $category]) {
            Topic::create([
                'group_id'   => $groupA->group_id,
                'creator_id' => $students->random()->user_id,
                'title'      => $title,
                'category'   => $category,
                'is_flagged' => false,
            ]);
        }

        // Lecturer creates one topic in Group A
        Topic::create([
            'group_id'   => $groupA->group_id,
            'creator_id' => $lecturer->user_id,
            'title'      => 'Assignment: Design a database schema for a library system',
            'category'   => 'Assignment',
            'is_flagged' => false,
        ]);

        foreach ($topicsGroupB as [$title, $category]) {
            Topic::create([
                'group_id'   => $groupB->group_id,
                'creator_id' => $students->random()->user_id,
                'title'      => $title,
                'category'   => $category,
                'is_flagged' => false,
            ]);
        }
    }
}
