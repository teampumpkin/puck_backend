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

    public function test_confirm_no_active_request_without_purchase(): void
    {
        // No request and no store purchase to recover from -> hard client error.
        $r = $this->d->confirm($this->confirmBase(['has_request' => false, 'purchase_id_provided' => false]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_NO_ACTIVE_REQUEST, $r);
    }

    public function test_confirm_recovers_when_purchase_present_but_no_request(): void
    {
        // Real store purchase but request missing (never initiated / released mid-flight):
        // recover rather than dropping the money.
        $r = $this->d->confirm($this->confirmBase(['has_request' => false, 'purchase_id_provided' => true]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_RECOVER_PUBLISH, $r);
    }

    public function test_confirm_no_recover_when_listing_not_confirmable(): void
    {
        // A purchase against a non-draft/non-requested listing is not recoverable here.
        $r = $this->d->confirm($this->confirmBase([
            'has_request' => false,
            'purchase_id_provided' => true,
            'listing_status' => 'payment_rejected',
        ]));
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

    private function initBase(array $over = []): array
    {
        return array_merge([
            'listing_status' => 'draft',
            'is_child' => false,
            'has_parent' => false,
            'existing_request_status' => null,
        ], $over);
    }

    public function test_initiate_blocks_when_already_published(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_ALREADY_PUBLISHED,
            $this->d->initiate($this->initBase(['listing_status' => 'published']))
        );
    }

    public function test_initiate_blocks_child_without_parent(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_CHILD_NO_PARENT,
            $this->d->initiate($this->initBase(['is_child' => true, 'has_parent' => false]))
        );
    }

    public function test_initiate_returns_existing_pending_for_child(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_RETURN_EXISTING_PENDING,
            $this->d->initiate($this->initBase([
                'is_child' => true, 'has_parent' => true, 'existing_request_status' => 'pending',
            ]))
        );
    }

    public function test_initiate_returns_existing_initiated(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_RETURN_EXISTING_INITIATED,
            $this->d->initiate($this->initBase(['existing_request_status' => 'payment_initiated']))
        );
    }

    public function test_initiate_creates_new_for_fresh_or_dead_request(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_CREATE_NEW,
            $this->d->initiate($this->initBase(['existing_request_status' => null]))
        );
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_CREATE_NEW,
            $this->d->initiate($this->initBase(['existing_request_status' => 'failed']))
        );
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_CREATE_NEW,
            $this->d->initiate($this->initBase(['existing_request_status' => 'parent_rejected']))
        );
    }
}
