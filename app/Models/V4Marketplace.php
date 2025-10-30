<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Marketplace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'in_app_purchase_id',
        'icon',
        'header_url',
        'tutorial_url',
        'type',
        'title',
        'description',
        'price_cents',
        'currency',
        'price_breakdown',
        'active',
    ];

    protected $casts = [
        'price_breakdown' => 'array',
        'active' => 'boolean',
    ];

    // Relationships
    public function inAppPurchase()
    {
        return $this->belongsTo(V4InAppPurchase::class, 'in_app_purchase_id');
    }
}
