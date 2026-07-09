<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipationScore extends Model
{
    protected $primaryKey = 'score_id';

    protected $fillable = [
        'user_id', 'group_id', 'criteria', 'score', 'awarded_by',
    ];

    // Added for the participation grading screens — not part of the
    // simplified base, kept additive rather than touching the fields above.
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
