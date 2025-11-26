<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4NotificationPreference extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'pause_all',
        'messages',
        'followers',
        'following',
    ];

    protected $casts = [
        'pause_all' => 'boolean',
        'messages' => 'boolean',
        'followers' => 'boolean',
        'following' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }
}
