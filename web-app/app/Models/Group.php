<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $primaryKey = 'group_id';

    protected $fillable = [
        'admin_id', 'name', 'description',
        'inactivity_warning_days', 'blacklist_duration_days',
    ];

    public function topics()
    {
        return $this->hasMany(Topic::class, 'group_id', 'group_id');
    }

    public function memberships()
    {
        return $this->hasMany(GroupMembership::class, 'group_id', 'group_id');
    }

    public function members()
    {
        return $this->belongsToMany(
            User::class, 'group_membership',
            'group_id', 'user_id', 'group_id', 'user_id'
        )->withPivot('role', 'status', 'joined_at');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }

    public function isMember(int $userId): bool
    {
        return $this->memberships()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }
}
