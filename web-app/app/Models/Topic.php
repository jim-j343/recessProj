<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    // Your migration uses 'topic_id' as the primary key
    protected $primaryKey = 'topic_id';

    protected $fillable = [
        'group_id',
        'creator_id',
        'title',
        'category',
        'is_flagged',
    ];

    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
        ];
    }

    // The user who created this topic
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'user_id');
    }

    // The group this topic belongs to
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    // All posts (replies) under this topic
    public function posts()
    {
        return $this->hasMany(Post::class, 'topic_id', 'topic_id');
    }
}
