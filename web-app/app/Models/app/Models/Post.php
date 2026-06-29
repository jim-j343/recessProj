<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'user_id',
        'body',
        'is_solution',
    ];

    protected $casts = [
        'is_solution' => 'boolean',
    ];

    // A post belongs to a topic
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    // A post belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}