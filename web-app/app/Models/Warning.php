<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warning extends Model
{
    use HasFactory;

    protected $primaryKey = 'warning_id';

    // warnings table only tracks issued_at/deadline, no created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'group_id',
        'warning_number',
        'issued_at',
        'deadline',
        'is_heeded',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'deadline'  => 'datetime',
        'is_heeded' => 'boolean',
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
