<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $moses = User::where('username', 'kayongo_moses')->first();
        if (!$moses) {
            return;
        }

        $topic = Topic::inRandomOrder()->first();
        $post  = $topic ? Post::where('topic_id', $topic->topic_id)->inRandomOrder()->first() : null;

        $notifications = [
            // [type, topic?, post?, is_read, hours/days ago]
            ['reply',           $topic, $post, false, 'hours', 2],
            ['quiz_announced',  null,   null,  false, 'days',  1],
            ['warning',         null,   null,  true,  'days',  4],
            ['mention',         $topic, $post, true,  'days',  6],
        ];

        foreach ($notifications as [$type, $notifTopic, $notifPost, $isRead, $unit, $amount]) {
            $notification = Notification::create([
                'user_id'  => $moses->user_id,
                'post_id'  => $notifPost?->post_id,
                'topic_id' => $notifTopic?->topic_id,
                'type'     => $type,
                'is_read'  => $isRead,
            ]);

            $when = $unit === 'hours' ? now()->subHours($amount) : now()->subDays($amount);
            $notification->created_at = $when;
            $notification->updated_at = $when;
            $notification->save();
        }
    }
}
