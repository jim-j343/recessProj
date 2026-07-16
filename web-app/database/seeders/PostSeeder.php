<?php

namespace Database\Seeders;

use App\Models\GroupMembership;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $lecturersByGroup = [
            'BSSE Year 1 - Stream A'   => 'dr_namukasa',
            'BSSE Year 1 - Stream B'   => 'dr_namukasa',
            'Web Development Cohort'   => 'dr_namukasa',
            'OOP Study Group'          => 'prof_opio',
            'Data Management Group'    => 'prof_opio',
            'Numerical Methods Group'  => 'dr_ssali',
        ];

        $messages = [
            "Great question! I think the key concept here is understanding the relationship between the tables.",
            "I struggled with this too. After reading the documentation, it became clearer.",
            "Here is what I found: you need to define the constraint explicitly in the migration file.",
            "Has anyone tried the approach suggested in the textbook? It worked for me.",
            "I disagree slightly — I think the better approach is to use an index here.",
            "Can someone clarify what happens when the parent record is deleted?",
            "According to what we covered in class, the answer relates to referential integrity.",
            "I tested this locally and it works. Let me know if you need the exact code.",
            "This is covered in chapter 5 of the recommended textbook.",
            "Good point! I would also add that you should always test your constraints with sample data.",
            "That makes sense. Thank you for the clear explanation!",
            "I found a useful resource on this topic — highly recommend reading it.",
        ];

        $topics = Topic::with('group')->get();

        foreach ($topics as $topic) {
            $groupName = $topic->group->name ?? null;
            $lecturer  = User::where('username', $lecturersByGroup[$groupName] ?? 'dr_namukasa')->first();

            $members = GroupMembership::where('group_id', $topic->group_id)
                ->whereHas('user', fn ($q) => $q->where('system_role', 'student'))
                ->with('user')->get()->pluck('user');

            if ($members->isEmpty()) {
                continue;
            }

            // First post is the topic body — from the creator
            $firstPost = Post::create([
                'topic_id'       => $topic->topic_id,
                'author_id'      => $topic->creator_id,
                'parent_post_id' => null,
                'content'        => "I am posting this question because I need clarity on: {$topic->title}. Any help would be appreciated.",
                'is_flagged'     => false,
                'is_synced'      => true,
            ]);

            // 3-7 replies per topic — enough range to show varied
            // participation percentages once the reply-based score is applied
            $replyCount = rand(3, 7);
            $previousPost = $firstPost;

            for ($i = 0; $i < $replyCount; $i++) {
                $author = $i === 0 ? $lecturer : $members->random();
                $isReply = $i > 0 && rand(0, 1);

                $post = Post::create([
                    'topic_id'       => $topic->topic_id,
                    'author_id'      => $author->user_id,
                    'parent_post_id' => $isReply ? $previousPost->post_id : null,
                    'content'        => $messages[array_rand($messages)],
                    'is_flagged'     => false,
                    'is_synced'      => true,
                ]);

                $previousPost = $post;
            }
        }

        // One flagged post, for admin/moderation testing
        $anyTopic = $topics->first();
        $anyStudent = User::where('system_role', 'student')->first();
        Post::create([
            'topic_id'       => $anyTopic->topic_id,
            'author_id'      => $anyStudent->user_id,
            'parent_post_id' => null,
            'content'        => 'Check out this amazing deal! Buy now at cheap prices!!!',
            'is_flagged'     => true,
            'is_synced'      => true,
        ]);

        // One post that demonstrates the "hide from specific members"
        // exclusion feature — kayongo_moses posts in Stream A, hiding it
        // from one specific classmate
        $moses    = User::where('username', 'kayongo_moses')->first();
        $streamATopic = Topic::whereHas('group', fn ($q) => $q->where('name', 'BSSE Year 1 - Stream A'))->first();
        $excludedTarget = User::where('username', 'nakato_alice')->first();

        if ($moses && $streamATopic && $excludedTarget) {
            $privatePost = Post::create([
                'topic_id'       => $streamATopic->topic_id,
                'author_id'      => $moses->user_id,
                'parent_post_id' => null,
                'content'        => "Can we sync up before the group presentation? I don't want to derail the main thread.",
                'is_flagged'     => false,
                'is_synced'      => true,
            ]);

            $privatePost->excludedUsers()->attach($excludedTarget->user_id);
        }
    }
}
