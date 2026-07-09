<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    protected $table = 'blacklist';

    protected $primaryKey = 'blacklist_id';

    // blacklist table only tracks blacklisted_at/expires_at, no created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'group_id', 'reason', 'blacklisted_at', 'expires_at', 'lifted_by',
    ];

    protected $casts = [
        'blacklisted_at' => 'datetime',
        'expires_at'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    public function liftedBy()
    {
        return $this->belongsTo(User::class, 'lifted_by', 'user_id');
    }
}
