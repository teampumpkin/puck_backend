<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Otp extends Model
{
    use SoftDeletes;

    protected $table = 'v4_otps';

    protected $fillable = [
        'user_id',
        'otp',
        'type',
        'provider',
        'requested_at',
        'expire_at'
    ];

    protected $dates = [
        'requested_at',
        'expire_at',
        'deleted_at',
    ];

    // Relationship: OTP belongs to a user
    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }
}
