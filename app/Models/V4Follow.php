<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Follow extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'follower_id',
        'following_id',
        'status',
        'accepted_at',
        'rejected_at',
        'conversation_id',
    ];

    protected $dates = [
        'accepted_at',
        'rejected_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    /**
     * User who sent the follow request.
     */
    public function follower()
    {
        return $this->belongsTo(V4User::class, 'follower_id');
    }

    /**
     * User who is being following.
     */
    public function following()
    {
        return $this->belongsTo(V4User::class, 'following_id');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'reference');
    }
}
