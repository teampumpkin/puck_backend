<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4SuspendedUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'reason_id',
        'message',
        'suspended_at',
        'unsuspended_at'
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id'); // specify the correct column
    }

    public function reason()
    {
        return $this->belongsTo(V4SuspendReason::class, 'reason_id');
    }
}
