<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4HockeyListing extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_ACTIVE = 'active';
    const STATUS_SOLD = 'sold';

    protected $fillable = [
        'user_id',
        'payment_request_id',
        'name',
        'price_cents',
        'currency',
        'description',
        'category',
        'condition',
        'latitude',
        'longitude',
        'address',
        'city',
        'state',
        'country',
        'sell_radius',
        'listed_at',
        'status',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'sell_radius' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'listed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'status' => self::STATUS_PENDING_PAYMENT,
        'sell_radius' => 50,
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }

    public function paymentRequest()
    {
        return $this->belongsTo(V4PaymentRequest::class, 'payment_request_id');
    }

    public function images()
    {
        return $this->hasMany(V4HockeyListingImage::class, 'listing_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_cents / 100, 2) . ' ' . strtoupper($this->currency);
    }

    public function markPendingPayment(): void
    {
        $this->status = self::STATUS_PENDING_PAYMENT;
        $this->save();
    }

    public function markActive(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->listed_at = now();
        $this->save();
    }

    public function markSold(): void
    {
        $this->status = self::STATUS_SOLD;
        $this->save();
    }
}
