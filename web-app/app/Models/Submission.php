<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $primaryKey = 'submission_id';

    // submissions table only has started_at/submitted_at, no created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'started_at',
        'submitted_at',
        'score',
        'auto_submitted',
    ];

    protected $casts = [
        'started_at'     => 'datetime',
        'submitted_at'   => 'datetime',
        'score'          => 'float',
        'auto_submitted' => 'boolean',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id', 'quiz_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
