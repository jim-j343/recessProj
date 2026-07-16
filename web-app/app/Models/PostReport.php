<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostReport extends Model
{
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'post_id',
        'reported_by',
        'reason',
        'reviewed',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed'    => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'post_id');
    }

    // Who filed the report
    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    // The system admin who reviewed it, if any
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
