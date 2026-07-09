<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipationScore extends Model
{
    protected $primaryKey = 'score_id';

    protected $fillable = [
        'user_id', 'group_id', 'criteria', 'score', 'awarded_by',
    ];
}
