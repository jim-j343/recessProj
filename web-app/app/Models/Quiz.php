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
        'course_name',
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

    // Every group this quiz should be visible to. A course-targeted quiz
    // (course_name set) applies to every group sharing that course unit,
    // even ones the lecturer isn't personally a member of. Older quizzes
    // created before course_name existed fall back to their single group_id.
    public function eligibleGroupIds()
    {
        if ($this->course_name) {
            return Group::where('course_name', $this->course_name)->pluck('group_id');
        }

        return $this->group_id ? collect([$this->group_id]) : collect();
    }
}
