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
                'product_type' => V4InAppPurchase::PRODUCT_TYPE_CONSUMABLE,
                'amount_cents' => 25900,
                'product_type' => 'service',
                'meta' => null,
                'active' => true,
                'currency' => 'CAD'
            ],
            [
                'sku' => 'one_on_one_consultation_video_call',
                'title' => "1 on 1 Consultation Video Call",
                'product_type' => V4InAppPurchase::PRODUCT_TYPE_CONSUMABLE,
                'amount_cents' => 25900,
                'meta' => null,
                'active' => true,
                'currency' => 'CAD'
            ],
            [
                'sku' => '12_week_mentorship_program',
                'title' => "12-Week Mentorship Program",
                'product_type' => V4InAppPurchase::PRODUCT_TYPE_CONSUMABLE,
                'amount_cents' => 25900,
                'meta' => null,
                'active' => true,
                'currency' => 'CAD'
            ],
            [
                'sku' => 'professional_hockey_portfolio',
                'title' => "Professional Hockey Portfolio",
                'product_type' => V4InAppPurchase::PRODUCT_TYPE_CONSUMABLE,
                'amount_cents' => 25900,
                'meta' => null,
                'active' => true,
                'currency' => 'CAD'
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
