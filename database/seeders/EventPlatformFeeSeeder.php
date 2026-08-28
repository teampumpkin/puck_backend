<?php

namespace Database\Seeders;

use App\Models\V4InAppPurchase;
use Illuminate\Database\Seeder;

class EventPlatformFeeSeeder extends Seeder
{
    public function run(): void
    {
        V4InAppPurchase::updateOrCreate(
            ['sku' => config('services.event.fee_sku', 'event_platform_fee')],
            [
                'title' => 'Event Platform Fee',
                'product_type' => 'consumable',
                'amount_cents' => 99,
                'currency' => 'CAD',
                'active' => true,
            ]
        );
    }
}
