<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $primaryKey = 'log_id';

    // activity_log only tracks logged_at, no created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'group_id',
        'action_type',
        'meta',
        'logged_at',
    ];

    protected $casts = [
        'meta'      => 'array',
        'logged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }
}
