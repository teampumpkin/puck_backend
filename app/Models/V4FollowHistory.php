<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class V4FollowHistory extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'follower_id',
        'following_id',
        'action',
        'meta',
        'conversation_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function follower()
    {
        return $this->belongsTo(V4User::class, 'follower_id');
    }

    public function following()
    {
        return $this->belongsTo(V4User::class, 'following_id');
    }
}
