<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEngagement extends Model
{
    // This table only has an engaged_at column, not created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'topic_id',
        'engagement_type',
        'engaged_at',
    ];
}

