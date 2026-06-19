<?php

namespace Tests\Unit\Payments;

use App\Services\Payments\HockeyListingPaymentDecider;
use PHPUnit\Framework\TestCase;

class HockeyListingPaymentDeciderTest extends TestCase
{
    private HockeyListingPaymentDecider $d;

    protected function setUp(): void
    {
        parent::setUp();
        $this->d = new HockeyListingPaymentDecider();
    }

    public function test_gateway_for_source_maps_store(): void
    {
        $this->assertSame('app_store', $this->d->gatewayForSource('ios'));
        $this->assertSame('play_store', $this->d->gatewayForSource('android'));
        $this->assertSame('web', $this->d->gatewayForSource('web'));
        $this->assertSame('web', $this->d->gatewayForSource('macos'));
    }

    private function confirmBase(array $over = []): array
    {
        return array_merge([
            'listing_status' => 'draft',
            'has_request' => true,
            'request_status' => 'payment_initiated',
            'is_owner' => true,
            'is_parent_payer' => false,
            'purchase_id_provided' => true,
            'duplicate_txn_exists' => false,
            'success_txn_exists' => false,
        ], $over);
    }

    public function test_confirm_unauthorized_when_neither_owner_nor_parent(): void
    {
        $r = $this->d->confirm($this->confirmBase(['is_owner' => false, 'is_parent_payer' => false]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_UNAUTHORIZED, $r);
    }

    public function test_confirm_duplicate_when_purchase_replayed(): void
    {
        $r = $this->d->confirm($this->confirmBase(['duplicate_txn_exists' => true]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_DUPLICATE, $r);
    }

    public function test_confirm_already_published_is_idempotent(): void
    {
        $r = $this->d->confirm($this->confirmBase(['listing_status' => 'published']));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_ALREADY_PUBLISHED, $r);
    }

    public function test_confirm_self_heal_when_success_txn_exists_but_not_published(): void
    {
        $r = $this->d->confirm($this->confirmBase(['success_txn_exists' => true]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_SELF_HEAL_PUBLISH, $r);
    }

    public function test_confirm_no_active_request(): void
    {
        $r = $this->d->confirm($this->confirmBase(['has_request' => false]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_NO_ACTIVE_REQUEST, $r);
    }

    public function test_confirm_not_confirmable_request_status(): void
    {
        $r = $this->d->confirm($this->confirmBase(['request_status' => 'paid']));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_NOT_CONFIRMABLE, $r);
    }

    public function test_confirm_pending_requires_parent(): void
    {
        $r = $this->d->confirm($this->confirmBase([
            'listing_status' => 'payment_requested',
            'request_status' => 'pending',
            'is_owner' => true,
            'is_parent_payer' => false,
        ]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_PARENT_ONLY, $r);
    }

    public function test_confirm_proceeds_for_valid_adult_payment(): void
    {
        $r = $this->d->confirm($this->confirmBase());
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_PROCEED, $r);
    }

    /** The regression: two independent listings both proceed; no cross-listing state exists. */
    public function test_confirm_is_listing_independent_no_user_sku_state(): void
    {
        $listingA = $this->d->confirm($this->confirmBase());
        $listingB = $this->d->confirm($this->confirmBase()); // different listing, same SKU/user
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_PROCEED, $listingA);
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_PROCEED, $listingB);
    }
}
