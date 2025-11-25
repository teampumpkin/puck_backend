<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4UserReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reported_user_id',
        'reported_by',
        'reason_id',
        'status',
        'message',
        'status',
    ];

    public function reportedUser()
    {
        // Change from User::class to V4User::class
        return $this->belongsTo(V4User::class, 'reported_user_id');
    }

    public function reportingUser()
    {
        // Change from User::class to V4User::class
        return $this->belongsTo(V4User::class, 'reported_by');
    }

    public function reason()
    {
        return $this->belongsTo(V4UserReportReason::class);
    }
}
