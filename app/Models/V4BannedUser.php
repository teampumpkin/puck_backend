<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class V4BannedUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'reason_id',
        'message',
        'banned_at',
        'unbanned_at'
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id'); // correct column
    }

    public function reason()
    {
        return $this->belongsTo(V4BanReason::class, 'reason_id');
    }
}
