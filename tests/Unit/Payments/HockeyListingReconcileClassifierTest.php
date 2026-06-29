<?php

namespace Tests\Unit\Payments;

use App\Services\Payments\HockeyListingPaymentDecider;
use PHPUnit\Framework\TestCase;

class HockeyListingReconcileClassifierTest extends TestCase
{
    private HockeyListingPaymentDecider $d;

    protected function setUp(): void
    {
        parent::setUp();
        $this->d = new HockeyListingPaymentDecider();
    }

    private function base(array $over = []): array
    {
        return array_merge([
            'listing_published' => false,
            'success_txn_exists' => false,
            'request_status' => null,
            'any_txn_exists' => false,
        ], $over);
    }

    public function test_publish_when_success_txn_but_not_published(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_PUBLISH,
            $this->d->reconcile($this->base(['success_txn_exists' => true]))
        );
    }

    public function test_release_when_stuck_request_and_no_txn(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_RELEASE,
            $this->d->reconcile($this->base(['request_status' => 'payment_initiated']))
        );
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_RELEASE,
            $this->d->reconcile($this->base(['request_status' => 'pending']))
        );
    }

    public function test_skip_when_published(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_SKIP,
            $this->d->reconcile($this->base(['listing_published' => true, 'success_txn_exists' => true]))
        );
    }

    public function test_skip_when_nothing_to_do(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_SKIP,
            $this->d->reconcile($this->base(['request_status' => 'paid']))
        );
    }
}
