<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
        'blocked_at',
        'unblocked_at'
    ];

    protected $dates = [
        'blocked_at',
        'unblocked_at',
        'created_at',
        'updated_at'
    ];

    public function blocker()
    {
        return $this->belongsTo(V4User::class, 'blocker_id');
    }

    public function blocked()
    {
        return $this->belongsTo(V4User::class, 'blocked_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('unblocked_at');
    }

    public function scopeHistory($query, $userId)
    {
        return $query->where('blocker_id', $userId)
            ->orWhere('blocked_id', $userId);
    }

    // Traditional accessor for status
    public function getStatusAttribute()
    {
        return is_null($this->unblocked_at) ? 'blocked' : 'unblocked';
    }

    // Traditional accessor for duration
    public function getDurationAttribute()
    {
        if ($this->unblocked_at) {
            return $this->blocked_at->diffInDays($this->unblocked_at);
        }

        return $this->blocked_at->diffInDays(now());
    }
}
