<?php

namespace Database\Seeders;

use App\Models\V4InAppPurchase;
use Illuminate\Database\Seeder;

class HockeyListingFeeSeeder extends Seeder
{
    public function run()
    {
        V4InAppPurchase::updateOrCreate(
            ['sku' => 'hockey_listing_fee'],
            [
                'sku' => 'hockey_listing_fee',
                'title' => 'Hockey Marketplace Listing',
                'product_type' => V4InAppPurchase::PRODUCT_TYPE_CONSUMABLE,
                'amount_cents' => 99,
                'currency' => 'CAD',
                'meta' => null,
                'active' => true,
            ]
        );
    }
}
