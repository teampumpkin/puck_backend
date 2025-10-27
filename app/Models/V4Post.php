<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'caption',
        'likes_count',
        'comments_count',
        'shares_count'
    ];


    protected $appends = ['is_liked'];


    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }

    public function media()
    {
        return $this->hasMany(V4PostMedia::class, 'post_id');
    }

    public function likes()
    {
        return $this->hasMany(V4PostLike::class, 'post_id');
    }

    public function comments()
    {
        return $this->hasMany(V4PostComment::class, 'post_id')->whereNull('parent_id');
    }

    public function shares()
    {
        return $this->hasMany(V4PostShare::class, 'post_id');
    }

    // 👇 Relationship for current user's like (we’ll eager-load this in controller)
    public function likedByAuthUser()
    {
        $userId = auth('v4api')->id();
        return $this->hasOne(V4PostLike::class, 'post_id')->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getIsLikedAttribute(): bool
    {
        // Uses the eager-loaded relationship (no query)
        return $this->relationLoaded('likedByAuthUser') && $this->likedByAuthUser !== null;
    }
}
