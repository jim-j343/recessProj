<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


// 1. Updated with your NEW migration columns
#[Fillable(['username', 'email', 'avatar', 'password_hash', 'system_role', 'status', 'agreed_to_rules', 'last_active_at'])]
#[Hidden(['password_hash', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // 2. REQUIRED: Tell Laravel your primary key is user_id, not id
    protected $primaryKey = 'user_id';

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at'    => 'datetime',
            'agreed_to_rules'   => 'boolean',
        ];
    }

    // 3. REQUIRED: Tell Laravel to check 'password_hash' during login
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // 4. Updated helper methods to use your new 'system_role' and 'status' columns
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

    // 5. Preserved your relationships perfectly
    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id', 'user_id');
    }

    public function warnings()
    {
        return $this->hasMany(Warning::class, 'user_id', 'user_id');
    }

    public function blacklists()
    {
        return $this->hasMany(Blacklist::class, 'user_id', 'user_id');
    }
}
