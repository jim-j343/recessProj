<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // Your migration uses 'notification_id' as the primary key
    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'post_id',
        'topic_id',
        'type',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    // The user this notification was sent to
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // The post this notification references, if any (reply/mention)
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'post_id');
    }

    // The topic this notification references, if any (reply/mention)
    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }

    /**
     * Icon name for the shared <x-icon> component.
     */

    public function icon(): string
    {
        return match ($this->type) {
            'reply', 'mention' => 'chat',
            'warning'          => 'alert-triangle',
            'blacklisted'      => 'shield-check',
            'quiz_announced'   => 'quiz',
            default            => 'bell',
        };
    }

    /**
     * Human-readable message.
     *
     * 'reply' and 'mention' link to a real topic/post via this table's
     * columns, so we can build a specific message. 'warning', 'blacklisted'
     * and 'quiz_announced' have no linked row in this schema, so they fall
     * back to a generic message for their type.
     */
    public function message(): string
    {
        return match ($this->type) {
            'reply'   => $this->topic
                ? "New reply in \"{$this->topic->title}\""
                : 'Someone replied to your post',
            'mention' => $this->topic
                ? "You were mentioned in \"{$this->topic->title}\""
                : 'You were mentioned in a post',
            'warning'        => 'You have received an inactivity warning',
            'blacklisted'    => 'Your account has been blacklisted',
            'quiz_announced' => 'A new quiz has been announced in one of your groups',
            default          => 'You have a new notification',
        };
    }

    /**
     * Where clicking the notification should take the user.
     */
    public function link(): string
    {
        if ($this->topic_id) {
            return route('topics.show', $this->topic_id);
        }

        return route('dashboard');
    }
    public static function notify(int $userId, string $type, ?int $postId = null, ?int $topicId = null): self
    {
        return static::create([
            'user_id'  => $userId,
            'post_id'  => $postId,
            'topic_id' => $topicId,
            'type'     => $type,
            'is_read'  => false,
        ]);
    }
}
