<?php

namespace Tests\Feature;

use App\Models\EvaluationSubmission;
use App\Models\V4InAppPurchase;
use App\Models\V4Marketplace;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Root cause of Sentry PHP-LARAVEL-1R: the app's generic purchase listener posts
// every store purchase (incl. event / listing fees) to evaluation/process-payment,
// which recorded an EvaluationSubmission for a SKU with no marketplace item.
class ProcessPaymentNonEvaluationSkuTest extends TestCase
{
    use RefreshDatabase;

    private function pay(V4User $u, string $sku)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . auth('v4api')->login($u)])
            ->postJson('/api/v4/evaluation/process-payment', [
                'sku' => $sku, 'source' => 'android', 'purchase_id' => 'GPA.' . Str::random(8),
            ]);
    }

    private function iap(string $sku): V4InAppPurchase
    {
        return V4InAppPurchase::create([
            'sku' => $sku, 'title' => $sku, 'product_type' => 'consumable',
            'amount_cents' => 999, 'currency' => 'CAD', 'active' => true,
        ]);
    }

    private function player(): V4User
    {
        return V4User::forceCreate(['first_name' => 'P', 'email' => Str::random(8) . '@t.io', 'role' => 'player']);
    }

    public function test_fee_sku_records_payment_but_no_evaluation_submission(): void
    {
        $this->iap('event_platform_fee');

        // Response unchanged for old app builds that still post fees here.
        $this->pay($this->player(), 'event_platform_fee')
            ->assertCreated()
            ->assertJsonPath('data.submission_id', null);

        $this->assertSame(1, V4PaymentTransaction::count());
        $this->assertSame(0, EvaluationSubmission::count());
    }

    public function test_evaluation_sku_still_creates_a_submission(): void
    {
        $iap = $this->iap('personalized_video_evaluation');
        V4Marketplace::create([
            'in_app_purchase_id' => $iap->id, 'title' => 'PVE', 'type' => 'personalized_video_evaluation',
            'price_cents' => 999, 'currency' => 'CAD',
        ]);

        $this->pay($this->player(), 'personalized_video_evaluation')->assertCreated();

        $this->assertSame(1, EvaluationSubmission::count());
    }
}
