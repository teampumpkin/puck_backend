<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;

class PaymentValidator
{
    public function validate(Request $request): array
    {
        return $request->validate([
            'sku' => 'required|string|exists:v4_in_app_purchases,sku',
            'player_id' => 'nullable|integer|exists:v4_users,id',

            // new fields
            'purchase_id' => 'nullable|string',
            'source' => 'required|in:ios,android,web,window,linux,macos',
            'verification_data' => 'nullable|array',
            'store_status' => 'nullable|string',
            'transaction_date' => 'nullable|date',
            'payload' => 'nullable|array',
        ]);
    }
}
