<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents an in-app product configuration (x amount).
 *
 * Fields: id, sku, title, amount_cents, currency, meta, active
 */
class V4InAppPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'title',
        'amount_cents',
        'currency',
        'meta',
        'active',
    ];

    protected $casts = [
        'meta' => 'array',
        'active' => 'boolean',
    ];

    public function paymentRequests()
    {
        return $this->hasMany(V4PaymentRequest::class, 'in_app_purchase_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getAmountAttribute()
    {
        return $this->amount_cents / 100;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount_cents / 100, 2) . ' ' . strtoupper($this->currency);
    }

    public function marketplaceItems()
    {
        return $this->hasMany(V4Marketplace::class, 'in_app_purchase_id');
    }
}
