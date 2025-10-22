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
}
