<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicRecommendation extends Model
{
    // This table only has a generated_at column, not created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'topic_id',
        'score',
        'generated_at',
        'is_dismissed',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }
}
