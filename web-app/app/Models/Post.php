<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    // Your migration uses 'post_id' as the primary key
    protected $primaryKey = 'post_id';

    protected $fillable = [
        'topic_id',
        'author_id',
        'parent_post_id',
        'content',
        'is_flagged',
        'is_synced',
    ];

    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
            'is_synced'  => 'boolean',
        ];
    }

    // The topic this post belongs to
    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }

    // The user who wrote this post
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'user_id');
    }

    // The post this is replying to (nullable, self-referencing)
    public function parent()
    {
        return $this->belongsTo(Post::class, 'parent_post_id', 'post_id');
    }

    // Replies to this post
    public function replies()
    {
        return $this->hasMany(Post::class, 'parent_post_id', 'post_id');
    }
}
