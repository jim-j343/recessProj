<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Your migration uses 'user_id' as the primary key, not the default 'id'
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'system_role',
        'status',
        'agreed_to_rules',
        'last_active_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at'  => 'datetime',
            'agreed_to_rules' => 'boolean',
        ];
    }

    // Laravel looks for getAuthPassword() during login since the
    // column is named password_hash instead of the default 'password'
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function isAdmin(): bool
    {
        return $this->system_role === 'system_admin';
    }

    public function isLecturer(): bool
    {
        return $this->system_role === 'lecturer';
    }

    public function isBlacklisted(): bool
    {
        return $this->status === 'blacklisted';
    }

    // A user creates many topics (creator_id, not user_id)
    public function topics()
    {
        return $this->hasMany(Topic::class, 'creator_id', 'user_id');
    }

    // A user authors many posts (author_id, not user_id)
    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id', 'user_id');
    }
}
