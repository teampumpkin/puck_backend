<?php

namespace Tests\Feature\HockeyListing;

use App\Models\V4HockeyListing;
use App\Models\V4InAppPurchase;
use App\Models\V4User;
use App\Services\Payments\HockeyListingPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HockeyListingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $a = []): V4User
    {
        return V4User::forceCreate(array_merge(['first_name' => 'U'.Str::random(4), 'email' => Str::random(8).'@t.io', 'role' => 'player'], $a));
    }

    private function authAs(V4User $u): array
    {
        return ['Authorization' => 'Bearer '.auth('v4api')->login($u)];
    }

    private function draftListing(V4User $o): V4HockeyListing
    {
        return V4HockeyListing::create([
            'user_id' => $o->id, 'name' => 'Stick', 'description' => 'd',
            'category' => 'sticks', 'condition' => 'new', 'price_cents' => 5000, 'currency' => 'CAD',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        V4InAppPurchase::create([
            'sku' => 'hockey_listing_fee', 'title' => 'Fee', 'product_type' => 'consumable',
            'amount_cents' => 999, 'currency' => 'CAD', 'active' => true,
        ]);
        config(['services.hockey_listing.fee_sku' => 'hockey_listing_fee']);
    }

    private function initiate(V4User $actor, V4HockeyListing $listing)
    {
        return $this->withHeaders($this->authAs($actor))
            ->postJson('/api/v4/hockey-listings/initiate-payment', ['listing_id' => $listing->id]);
    }

    // ---- Fee ON (default) ----

    public function test_adult_initiate_returns_sku_and_amount(): void
    {
        $owner = $this->makeUser();
        $listing = $this->draftListing($owner);

        $this->initiate($owner, $listing)
            ->assertStatus(201)
            ->assertJsonPath('data.sku', 'hockey_listing_fee')
            ->assertJsonPath('data.awaiting_parent', false);
    }

    public function test_child_initiate_creates_parent_request(): void
    {
        $parent = $this->makeUser();
        $child = $this->makeUser(['is_child' => true, 'parent_id' => $parent->id]);
        $listing = $this->draftListing($child);

        $this->initiate($child, $listing)
            ->assertStatus(201)
            ->assertJsonPath('data.awaiting_parent', true);

        $this->assertSame(V4HockeyListing::STATUS_PAYMENT_REQUESTED, $listing->fresh()->status);
        $this->assertDatabaseHas('v4_payment_requests', ['parent_id' => $parent->id, 'status' => 'pending']);
    }

    public function test_adult_confirm_publishes_listing_and_is_idempotent(): void
    {
        $owner = $this->makeUser();
        $listing = $this->draftListing($owner);
        $h = $this->authAs($owner);

        $this->initiate($owner, $listing)->assertStatus(201);

        $body = ['source' => 'android', 'purchase_id' => 'gpa.'.Str::random(8)];
        $this->withHeaders($h)->postJson("/api/v4/hockey-listings/{$listing->id}/confirm-payment", $body)->assertStatus(200);
        $this->assertSame(V4HockeyListing::STATUS_PUBLISHED, $listing->fresh()->status);
        $this->assertDatabaseHas('v4_payment_transactions', ['status' => 'success']);

        // replay same receipt -> idempotent, still exactly one success txn
        $this->withHeaders($h)->postJson("/api/v4/hockey-listings/{$listing->id}/confirm-payment", $body)->assertStatus(200);
        $this->assertSame(1, \App\Models\V4PaymentTransaction::where('status', 'success')->count());
    }

    public function test_only_parent_can_confirm_child_request(): void
    {
        $parent = $this->makeUser();
        $child = $this->makeUser(['is_child' => true, 'parent_id' => $parent->id]);
        $listing = $this->draftListing($child);
        $this->initiate($child, $listing)->assertStatus(201);

        $body = ['source' => 'android', 'purchase_id' => 'gpa.'.Str::random(8)];
        // a random non-parent cannot confirm
        $this->withHeaders($this->authAs($this->makeUser()))
            ->postJson("/api/v4/hockey-listings/{$listing->id}/confirm-payment", $body)->assertStatus(403);

        // parent confirms -> published
        $this->withHeaders($this->authAs($parent))
            ->postJson("/api/v4/hockey-listings/{$listing->id}/confirm-payment", $body)->assertStatus(200);
        $this->assertSame(V4HockeyListing::STATUS_PUBLISHED, $listing->fresh()->status);
    }

    // ---- Fee OFF (waived, all roles) ----

    public function test_fee_disabled_publishes_adult_listing_without_payment_request(): void
    {
        HockeyListingPaymentService::setFeeEnabled(false);
        $owner = $this->makeUser();
        $listing = $this->draftListing($owner);

        $this->initiate($owner, $listing)
            ->assertStatus(200)
            ->assertJsonPath('data.fee_waived', true)
            ->assertJsonPath('data.payment_request_id', null)
            ->assertJsonPath('data.sku', null);

        $this->assertSame(V4HockeyListing::STATUS_PUBLISHED, $listing->fresh()->status);
        $this->assertNull($listing->fresh()->payment_request_id);
        $this->assertDatabaseCount('v4_payment_requests', 0);
    }

    public function test_fee_disabled_publishes_child_listing_without_parent_request(): void
    {
        HockeyListingPaymentService::setFeeEnabled(false);
        $parent = $this->makeUser();
        $child = $this->makeUser(['is_child' => true, 'parent_id' => $parent->id]);
        $listing = $this->draftListing($child);

        $this->initiate($child, $listing)
            ->assertStatus(200)
            ->assertJsonPath('data.awaiting_parent', false)
            ->assertJsonPath('data.fee_waived', true);

        $this->assertSame(V4HockeyListing::STATUS_PUBLISHED, $listing->fresh()->status);
        $this->assertDatabaseCount('v4_payment_requests', 0);
    }
}
