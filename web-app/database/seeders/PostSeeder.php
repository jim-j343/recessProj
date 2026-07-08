<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('system_role', 'student')->get();
        $lecturer = User::where('username', 'dr_namukasa')->first();
        $topics   = Topic::all();

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

        foreach ($topics as $topic) {
            // First post is the topic body — from the creator
            $firstPost = Post::create([
                'topic_id'       => $topic->topic_id,
                'author_id'      => $topic->creator_id,
                'parent_post_id' => null,
                'content'        => "I am posting this question because I need clarity on: {$topic->title}. Any help would be appreciated.",
                'is_flagged'     => false,
                'is_synced'      => true,
            ]);

            // Add 2-4 replies per topic
            $replyCount = rand(2, 4);
            $previousPost = $firstPost;

            for ($i = 0; $i < $replyCount; $i++) {
                $author = $i === 0 ? $lecturer : $students->random();
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

        // Add one flagged post for admin testing
        $anyTopic = $topics->first();
        Post::create([
            'topic_id'       => $anyTopic->topic_id,
            'author_id'      => $students->first()->user_id,
            'parent_post_id' => null,
            'content'        => 'Check out this amazing deal! Buy now at cheap prices!!!',
            'is_flagged'     => true,
            'is_synced'      => true,
        ]);
    }
}
