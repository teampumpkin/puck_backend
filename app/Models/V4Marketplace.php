<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Marketplace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'price_cents',
        'price_breakdown',
        'in_app_purchase_id',
        'header_url',
        'icon',
        'tutorial_url',
        'currency',
        'type',
        'active',
    ];

    protected $casts = [
        'price_breakdown' => 'array',
        'active' => 'boolean',
    ];

    // ✅ Automatically include payment_summary in JSON
    protected $appends = ['payment_summary', 'payment_notice'];

    // Relationships
    public function inAppPurchase()
    {
        return $this->belongsTo(V4InAppPurchase::class, 'in_app_purchase_id');
    }

    public function getPaymentSummaryAttribute(): ?string
    {
        if (empty($this->price_breakdown) || !is_array($this->price_breakdown)) {
            return null;
        }

        $summary = "💳 Payment Plan Summary:\n\n";
        $total = 0;
        $startDate = now();

        foreach ($this->price_breakdown as $index => $item) {
            $label = $item['label'] ?? "Payment " . ($index + 1);
            $amount = isset($item['amount_cents']) ? number_format($item['amount_cents'] / 100, 2) : '0.00';
            $total += $item['amount_cents'] ?? 0;

            // Example: add month progression for demo
            $dueDate = $index === 0
                ? 'Due Today'
                : $startDate->copy()->addMonths($index)->format('d - M - Y');

            $summary .= "{$label}: \${$amount} ({$dueDate})\n";
        }

        $summary .= str_repeat('-', 41) . "\n";
        $summary .= "Total: $" . number_format($total / 100, 2);

        return $summary;
    }

    public function getPaymentNoticeAttribute(): ?string
    {
        if (empty($this->price_breakdown) || !is_array($this->price_breakdown)) {
            return null;
        }
        return "Your next payments will be automatically charged to this method. "
            . "You’ll receive reminders 2 days before each deduction.";
    }
}
