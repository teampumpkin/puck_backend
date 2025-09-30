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

    public function scopeBetweenUsers($query, $user1Id, $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where(function ($inner) use ($user1Id, $user2Id) {
                $inner->where('blocker_id', $user1Id)
                      ->where('blocked_id', $user2Id);
            })->orWhere(function ($inner) use ($user1Id, $user2Id) {
                $inner->where('blocker_id', $user2Id)
                      ->where('blocked_id', $user1Id);
            });
        });
    }

    public function scopeBlockedBy($query, $blockerId)
    {
        return $query->where('blocker_id', $blockerId);
    }

    public function scopeBlockedUser($query, $blockedId)
    {
        return $query->where('blocked_id', $blockedId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('blocked_at', [$startDate, $endDate]);
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
