<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'category',
        'is_pinned',
        'is_locked',
        'views',
    ];

    protected $casts = [
        'is_pinned'  => 'boolean',
        'is_locked'  => 'boolean',
    ];

    // A topic belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A topic has many posts (replies)
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Count of replies
    public function replyCount()
    {
        return $this->posts()->count();
    }
    // Check if topic has no solution marked yet
    public function hasUnanswered(): bool
    {
         return !$this->posts()->where('is_solution', true)->exists();
    }
}