<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Completed payment record.
 *
 * Fields: id, payment_request_id, payer_id, amount_cents, currency, gateway, gateway_reference, status, meta
 *
 * status: success / failed / refunded etc.
 */
class V4PaymentTransaction extends Model
{
    use HasFactory;

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PENDING = 'pending';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payment_request_id',
        'payer_id',
        'amount_cents',
        'currency',
        'gateway',
        'gateway_reference',
        'status',
        'purchase_id',
        'source',
        'verification_data',
        'store_status',
        'transaction_date',
        'payload',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'verification_data' => 'array',
        'payload' => 'array',
        'transaction_date' => 'datetime',
        'amount_cents' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'currency' => 'USD',
    ];

    /* --------------------
     | Relationships
     --------------------*/
    public function paymentRequest()
    {
        return $this->belongsTo(V4PaymentRequest::class, 'payment_request_id');
    }

    public function payer()
    {
        return $this->belongsTo(V4User::class, 'payer_id');
    }

    /* --------------------
     | Scopes
     --------------------*/
    public function scopeSuccessful($q)
    {
        return $q->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($q)
    {
        return $q->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded($q)
    {
        return $q->where('status', self::STATUS_REFUNDED);
    }

    public function scopeByGateway($q, $gateway)
    {
        return $q->where('gateway', $gateway);
    }

    public function scopeForUser($q, $userId)
    {
        return $q->where('payer_id', $userId);
    }

    /* --------------------
     | Helper Methods
     --------------------*/
    public function markSuccess(string $gatewayReference = null)
    {
        $this->status = self::STATUS_SUCCESS;
        if ($gatewayReference) {
            $this->gateway_reference = $gatewayReference;
        }
        $this->save();

        // Reflect in payment request
        if ($this->paymentRequest) {
            $this->paymentRequest->markPaid();
        }

        return $this;
    }

    public function markFailed(string $reason = null)
    {
        $this->status = self::STATUS_FAILED;
        if ($reason) {
            $meta = $this->meta ?? [];
            $meta['failure_reason'] = $reason;
            $meta['failed_at'] = now()->toISOString();
            $this->meta = $meta;
        }
        $this->save();

        // Reflect in payment request
        if ($this->paymentRequest) {
            $this->paymentRequest->markFailed($reason);
        }

        return $this;
    }

    public function markRefunded(string $reason = null, int $refundAmountCents = null)
    {
        $this->status = self::STATUS_REFUNDED;

        $meta = $this->meta ?? [];
        $meta['refunded_at'] = now()->toISOString();
        $meta['refund_amount_cents'] = $refundAmountCents ?? $this->amount_cents;

        if ($reason) {
            $meta['refund_reason'] = $reason;
        }

        $this->meta = $meta;
        $this->save();

        return $this;
    }

    public function markCancelled(string $reason = null)
    {
        $this->status = self::STATUS_CANCELLED;
        if ($reason) {
            $meta = $this->meta ?? [];
            $meta['cancellation_reason'] = $reason;
            $meta['cancelled_at'] = now()->toISOString();
            $this->meta = $meta;
        }
        $this->save();

        return $this;
    }

    /* --------------------
     | Accessors
     --------------------*/
    public function getAmountAttribute()
    {
        return $this->amount_cents / 100;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount_cents / 100, 2) . ' ' . strtoupper($this->currency);
    }

    /* --------------------
     | Status Checkers
     --------------------*/
    public function isSuccessful()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded()
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
