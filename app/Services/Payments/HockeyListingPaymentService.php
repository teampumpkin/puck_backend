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
        $isChild = (bool) ($actor->is_child ?? false);
        $parentId = $isChild ? $actor->parent_id : null;

        $existing = $listing->payment_request_id
            ? V4PaymentRequest::with('inAppPurchase')->find($listing->payment_request_id)
            : null;

        $code = $this->decider->initiate([
            'listing_status' => $listing->status,
            'is_child' => $isChild,
            // has_parent means "child has a valid parent"; null for adults, who never reach CHILD_NO_PARENT
            'has_parent' => (bool) $parentId,
            'existing_request_status' => $existing?->status,
        ]);

        if ($code === HockeyListingPaymentDecider::INIT_ALREADY_PUBLISHED) {
            return ['code' => $code, 'http' => 400, 'payload' => ['message' => 'Listing is already published.']];
        }
        if ($code === HockeyListingPaymentDecider::INIT_CHILD_NO_PARENT) {
            return ['code' => $code, 'http' => 400, 'payload' => ['message' => 'Child account is missing a parent. Cannot request payment.']];
        }
        if ($code === HockeyListingPaymentDecider::INIT_RETURN_EXISTING_PENDING) {
            return ['code' => $code, 'http' => 200, 'payload' => $this->requestPayload($existing, true, null)];
        }
        if ($code === HockeyListingPaymentDecider::INIT_RETURN_EXISTING_INITIATED) {
            return ['code' => $code, 'http' => 200, 'payload' => $this->requestPayload($existing, false, $existing->inAppPurchase?->sku)];
        }

        // INIT_CREATE_NEW
        $fee = $this->feeProduct();
        if (!$fee) {
            return ['code' => 'fee_missing', 'http' => 404, 'payload' => ['message' => 'Listing fee product not found or inactive.']];
        }

        $actorId = $actor->id;
        $request = DB::transaction(function () use ($listing, $actorId, $isChild, $parentId, $fee) {
            $data = [
                'payer_id' => $isChild ? $parentId : $actorId,
                'player_id' => $actorId,
                'in_app_purchase_id' => $fee->id,
                'amount_cents' => $fee->amount_cents,
                'currency' => $fee->currency,
                'status' => $isChild
                    ? V4PaymentRequest::STATUS_PENDING
                    : V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                'meta' => ['purpose' => 'hockey_listing', 'listing_id' => $listing->id],
            ];
            if ($isChild) {
                $data['parent_id'] = $parentId;
            }
            $request = V4PaymentRequest::create($data);

            $listing->payment_request_id = $request->id;
            $listing->status = $isChild
                ? V4HockeyListing::STATUS_PAYMENT_REQUESTED
                : V4HockeyListing::STATUS_DRAFT;
            $listing->save();

            return $request;
        });

        $request->setRelation('inAppPurchase', $fee);

        return [
            'code' => $code,
            'http' => 201,
            'payload' => $this->requestPayload($request, $isChild, $isChild ? null : $fee->sku),
            'created' => true,
            'is_child' => $isChild,
            'request' => $request,
            'fee' => $fee,
        ];
    }

    private function requestPayload(V4PaymentRequest $request, bool $awaitingParent, ?string $sku): array
    {
        return [
            'awaiting_parent' => $awaitingParent,
            'payment_request_id' => $request->id,
            'sku' => $sku,
            'amount_cents' => $request->amount_cents,
            'currency' => $request->currency,
            'formatted_amount' => $request->formatted_amount,
        ];
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
