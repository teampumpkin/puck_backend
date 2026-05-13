<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4HockeyListing extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_PAYMENT_REQUESTED = 'payment_requested';
    const STATUS_PAYMENT_FAILED = 'payment_failed';
    const STATUS_PAYMENT_REJECTED = 'payment_rejected';
    const STATUS_PUBLISHED = 'published';

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
        'status' => self::STATUS_DRAFT,
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

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_cents / 100, 2) . ' ' . strtoupper($this->currency);
    }

    public function markDraft(): void
    {
        $this->status = self::STATUS_DRAFT;
        $this->save();
    }

    public function markPaymentRequested(): void
    {
        $this->status = self::STATUS_PAYMENT_REQUESTED;
        $this->save();
    }

    public function markPaymentFailed(): void
    {
        $this->status = self::STATUS_PAYMENT_FAILED;
        $this->save();
    }

    public function markPaymentRejected(): void
    {
        $this->status = self::STATUS_PAYMENT_REJECTED;
        $this->save();
    }

    public function markPublished(): void
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->listed_at = now();
        $this->save();
    }
}
