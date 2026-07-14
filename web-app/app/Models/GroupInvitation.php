<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupInvitation extends Model
{
    protected $primaryKey = 'invitation_id';

    protected $fillable = [
        'group_id',
        'invited_user_id',
        'invited_by',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    // The person being invited
    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id', 'user_id');
    }

    // The group admin who sent the invite
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by', 'user_id');
    }
}
