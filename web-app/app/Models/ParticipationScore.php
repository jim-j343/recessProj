<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipationScore extends Model
{
    use HasFactory;

    protected $primaryKey = 'score_id';

    protected $fillable = [
        'user_id',
        'group_id',
        'criteria',
        'score',
        'awarded_by',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    public function awardedBy()
    {
        return $this->belongsTo(User::class, 'awarded_by', 'user_id');
    }
}
