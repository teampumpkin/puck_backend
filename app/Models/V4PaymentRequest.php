<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tracks a payment request for a player.
 *
 * Fields: id, payer_id (player), parent_id (nullable), player_id (nullable), in_app_purchase_id, amount_cents, currency,
 * status, retry_count, notes, meta, created_at, updated_at
 *
 * Status flow:
 *  - pending (default) // if player is minor, waiting for parent approval
 *  - parent_approved
 *  - parent_rejected
 *  - payment_initiated
 *  - paid
 *  - failed
 */
class V4PaymentRequest extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_PARENT_REJECTED = 'parent_rejected';
    const STATUS_PAYMENT_INITIATED = 'payment_initiated';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'payer_id',
        'parent_id',
        'player_id',
        'in_app_purchase_id',
        'amount_cents',
        'currency',
        'status',
        'retry_count',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'amount_cents' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'retry_count' => 0,
        'status' => self::STATUS_PENDING,
        'currency' => 'USD',
    ];

    /* --------------------
     | Relationships
     --------------------*/
    public function payer()
    {
        return $this->belongsTo(V4User::class, 'payer_id');
    }

    public function parent()
    {
        return $this->belongsTo(V4User::class, 'parent_id');
    }

    public function player()
    {
        return $this->belongsTo(V4User::class, 'player_id');
    }

    public function inAppPurchase()
    {
        return $this->belongsTo(V4InAppPurchase::class, 'in_app_purchase_id');
    }

    public function paymentTransaction()
    {
        return $this->hasOne(V4PaymentTransaction::class, 'payment_request_id');
    }

    /* --------------------
     | Scopes
     --------------------*/
    public function scopePending($q)
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeAwaitingParent($q)
    {
        return $q->where('status', self::STATUS_PENDING)->whereNotNull('parent_id');
    }

    public function scopeByStatus($q, $status)
    {
        return $q->where('status', $status);
    }

    public function scopeForUser($q, $userId)
    {
        return $q->where('payer_id', $userId);
    }

    public function scopeForParent($q, $parentId)
    {
        return $q->where('parent_id', $parentId);
    }

    public function scopeForPlayer($q, $playerId)
    {
        return $q->where('player_id', $playerId);
    }

    /* --------------------
     | Helpers
     --------------------*/

    public function markParentRejected(string $reason = null)
    {
        $this->status = self::STATUS_PARENT_REJECTED;
        if ($reason) {
            $this->notes = trim(($this->notes ?? '') . "\nParent rejection reason: {$reason}");
        }
        $this->retry_count = $this->retry_count + 1;
        $this->save();
    }

    public function markPaymentInitiated()
    {
        $this->status = self::STATUS_PAYMENT_INITIATED;
        $this->save();
    }

    public function markPaid()
    {
        $this->status = self::STATUS_PAID;
        $this->save();
    }

    public function markFailed(string $reason = null)
    {
        $this->status = self::STATUS_FAILED;
        if ($reason) {
            $this->notes = trim(($this->notes ?? '') . "\nFailure reason: {$reason}");
        }
        $this->incrementRetry();
    }

    public function incrementRetry()
    {
        $this->increment('retry_count');
    }

    public function getAmountAttribute()
    {
        return $this->amount_cents / 100;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount_cents / 100, 2) . ' ' . strtoupper($this->currency);
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function requiresParentApproval()
    {
        return !is_null($this->parent_id) && $this->status === self::STATUS_PENDING;
    }

    public function notification()
    {
        return $this->morphOne(Notification::class, 'reference');
    }
}
