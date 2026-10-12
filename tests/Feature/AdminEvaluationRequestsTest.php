<?php

namespace Tests\Feature;

use App\Models\EvaluationSubmission;
use App\Models\V4InAppPurchase;
use App\Models\V4Marketplace;
use App\Models\V4PaymentRequest;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Sentry PHP-LARAVEL-1R: one submission whose marketplace item was soft-deleted
// (or with no payment transaction yet) 500'd the whole admin requests list.
class AdminEvaluationRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): V4User
    {
        return V4User::forceCreate(['first_name' => 'U' . Str::random(4), 'email' => Str::random(8) . '@t.io', 'role' => $role]);
    }

    private function submission(V4User $player): array
    {
        $iap = V4InAppPurchase::create([
            'sku' => 'eval_' . Str::random(6), 'title' => 'Eval', 'product_type' => 'consumable',
            'amount_cents' => 4999, 'currency' => 'CAD', 'active' => true,
        ]);
        $item = V4Marketplace::create([
            'in_app_purchase_id' => $iap->id, 'title' => 'Portfolio', 'type' => 'professional_hockey_portfolio',
            'price_cents' => 4999, 'currency' => 'CAD',
        ]);
        $pr = V4PaymentRequest::create([
            'payer_id' => $player->id, 'player_id' => $player->id, 'in_app_purchase_id' => $iap->id,
            'amount_cents' => 4999, 'currency' => 'CAD', 'status' => 'pending',
        ]);
        $sub = EvaluationSubmission::forceCreate(['player_id' => $player->id, 'payment_request_id' => $pr->id]);

        return [$sub, $item];
    }

    private function list()
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . auth('v4api')->login($this->user('admin'))])
            ->getJson('/api/v4/admin/evaluations/requests');
    }

    public function test_list_survives_soft_deleted_marketplace_item_and_missing_transaction(): void
    {
        [$sub, $item] = $this->submission($this->user('player'));
        $item->delete();

        $this->list()
            ->assertOk()
            ->assertJsonPath('data.0.id', $sub->id)
            ->assertJsonPath('data.0.type', 'professional_hockey_portfolio')
            ->assertJsonPath('data.0.purchaseDate', null);
    }

    public function test_orphan_fee_submission_does_not_break_lists(): void
    {
        // Older app builds recorded event/listing fees through process-payment,
        // leaving submissions whose IAP has no marketplace item at all.
        $player = $this->user('player');
        [$sub, $item] = $this->submission($player);
        $item->forceDelete();

        $this->list()->assertOk()->assertJsonPath('data.0.type', null);

        $this->withHeaders(['Authorization' => 'Bearer ' . auth('v4api')->login($player)])
            ->getJson("/api/v4/orders/{$player->id}")
            ->assertOk();
    }

    public function test_list_survives_deleted_player(): void
    {
        $player = $this->user('player');
        [$sub] = $this->submission($player);
        $player->delete();

        $this->list()->assertOk()->assertJsonPath('data.0.playerId', null);
    }

    public function test_detail_survives_soft_deleted_marketplace_item(): void
    {
        [$sub, $item] = $this->submission($this->user('player'));
        $item->delete();

        $this->withHeaders(['Authorization' => 'Bearer ' . auth('v4api')->login($this->user('admin'))])
            ->getJson("/api/v4/admin/evaluations/requests/{$sub->id}")
            ->assertOk();
    }
}
