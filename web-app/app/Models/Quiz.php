<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $primaryKey = 'quiz_id';

    protected $fillable = [
        'lecturer_id',
        'group_id',
        'title',
        'target_category',
        'start_time',
        'duration_minutes',
        'is_published',
    ];

    protected $casts = [
        'start_time'   => 'datetime',
        'is_published' => 'boolean',
    ];

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id', 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'quiz_id', 'quiz_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'quiz_id', 'quiz_id');
    }
}
