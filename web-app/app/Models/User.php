<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is blacklisted', 'last_active_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at'    => 'datetime',
            'is_blacklisted'    => 'boolean',
            'password' => 'hashed',
        ];
    }
    public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function isLecturer(): bool
{
    return $this->role === 'lecturer';
}

public function isBlacklisted(): bool
{
    return (bool) $this->is_blacklisted;
}
// A user has many topics
public function topics()
{
    return $this->hasMany(Topic::class);
}

// A user has many posts
public function posts()
{
    return $this->hasMany(Post::class);
}
}
