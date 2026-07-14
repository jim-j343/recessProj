<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'submission_id', 'question_id', 'answer_id',
        'text_response', 'is_correct', 'marks_awarded',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];
}
