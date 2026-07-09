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
}
