<?php
// app/Models/GroupMembership.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMembership extends Model
{
    public $timestamps = false;
    protected $table = 'group_membership';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = ['user_id', 'group_id', 'role', 'status', 'joined_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }
}