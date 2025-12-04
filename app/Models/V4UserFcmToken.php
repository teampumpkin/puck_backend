<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4UserFcmToken extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'fcm_token',
        'device_type',
        'device_id',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }
}
