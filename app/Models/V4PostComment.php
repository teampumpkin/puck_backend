<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4PostComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'body',
    ];


    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }

    public function post()
    {
        return $this->belongsTo(V4Post::class, 'post_id');
    }

    public function parent()
    {
        return $this->belongsTo(V4PostComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(V4PostComment::class, 'parent_id')->with('user');
    }
}
