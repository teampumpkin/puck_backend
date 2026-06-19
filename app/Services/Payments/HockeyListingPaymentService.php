<?php

namespace App\Services\Payments;

use App\Models\V4HockeyListing;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use Illuminate\Support\Facades\DB;
use LogicException;

class HockeyListingPaymentService
{
    public function __construct(private HockeyListingPaymentDecider $decider)
    {
    }

    public function feeProduct(): ?V4InAppPurchase
    {
        return V4InAppPurchase::where('sku', config('services.hockey_listing.fee_sku'))
            ->where('active', true)
            ->first();
    }

    public function initiate(V4HockeyListing $listing, V4User $actor): array
    {
        throw new LogicException('not implemented');
    }

    public function confirm(V4HockeyListing $listing, V4User $actor, array $receipt): array
    {
        throw new LogicException('not implemented');
    }

    public function status(V4HockeyListing $listing): array
    {
        throw new LogicException('not implemented');
    }

    public function reject(V4PaymentRequest $request, V4HockeyListing $listing, ?string $reason): void
    {
        throw new LogicException('not implemented');
    }
}
