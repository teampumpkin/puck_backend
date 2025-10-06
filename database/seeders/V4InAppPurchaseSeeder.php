<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\V4InAppPurchase;

class V4InAppPurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $inAppPurchases = [
            [
                'sku' => 'personalized_video_evaluation',
                'title' => 'Personalized Video Evaluation',
                'amount_cents' => 25900,
                'meta' => null,
                'active' => true,
                'currency' => 'CDN'
            ],
        ];

        foreach ($inAppPurchases as $purchase) {
            V4InAppPurchase::updateOrCreate(
                ['sku' => $purchase['sku']], // Find by SKU
                $purchase // Update or create with this data
            );
        }
    }
}