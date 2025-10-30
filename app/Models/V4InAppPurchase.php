<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents an in-app product configuration (x amount).
 *
 * Fields: id, sku, title, amount_cents, currency, meta, active
 */
class V4InAppPurchase extends Model
{
    use HasFactory, SoftDeletes;

    public const PRODUCT_TYPE_CONSUMABLE = 'consumable';
    public const PRODUCT_TYPE_NON_CONSUMABLE = 'non_consumable';
    public const PRODUCT_TYPE_SUBSCRIPTION = 'subscription';

    // 🔹 List of all valid product types
    public const PRODUCT_TYPES = [
        self::PRODUCT_TYPE_CONSUMABLE,
        self::PRODUCT_TYPE_NON_CONSUMABLE,
        self::PRODUCT_TYPE_SUBSCRIPTION,
    ];

    protected $fillable = [
        'sku',
        'title',
        'product_type',
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

    public function marketplaceItem()
    {
        return $this->hasOne(V4Marketplace::class, 'in_app_purchase_id');
    }

    // 🔹 Helper for validation
    public static function isValidProductType(string $type): bool
    {
        return in_array($type, self::PRODUCT_TYPES, true);
    }
}
