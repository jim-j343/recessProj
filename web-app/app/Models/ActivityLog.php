<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $table   = 'activity_log';
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'user_id', 'group_id', 'action_type', 'meta', 'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'meta'      => 'array',
    ];

    // Who performed this activity
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Which group it happened in
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }
}
