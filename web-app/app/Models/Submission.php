<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'submission_id';

    protected $fillable = [
        'quiz_id', 'user_id', 'started_at',
        'submitted_at', 'score', 'auto_submitted',
    ];

    protected $casts = [
        'started_at'    => 'datetime',
        'submitted_at'  => 'datetime',
        'auto_submitted'=> 'boolean',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id', 'quiz_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function answers()
    {
        return $this->hasMany(SubmissionAnswer::class, 'submission_id', 'submission_id');
    }
}
