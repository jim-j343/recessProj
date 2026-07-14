<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupRemoval extends Model
{
    protected $primaryKey = 'removal_id';

    protected $fillable = [
        'group_id',
        'removed_user_id',
        'removed_by',
        'reason',
        'reviewed',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed'    => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    // The member who was removed
    public function removedUser()
    {
        return $this->belongsTo(User::class, 'removed_user_id', 'user_id');
    }

    // The group admin who removed them
    public function removedBy()
    {
        return $this->belongsTo(User::class, 'removed_by', 'user_id');
    }

    // The system admin who reviewed the report, if any
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
