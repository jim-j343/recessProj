<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    public $timestamps = false;
    protected $table = 'blacklist';
    protected $primaryKey = 'blacklist_id';

    protected $fillable = [
        'user_id', 'group_id', 'reason',
        'blacklisted_at', 'expires_at', 'lifted_by',
    ];

    protected $casts = [
        'blacklisted_at' => 'datetime',
        'expires_at'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
