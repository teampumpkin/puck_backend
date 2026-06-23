<?php

namespace App\Services\Payments;

use App\Models\V4HockeyListing;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use App\Services\Payments\Sk2ReceiptDecoder;
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
            $this->ensureBindingToken($existing);
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
                'binding_token' => (string) \Illuminate\Support\Str::uuid(),
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
            'binding_token' => $request->binding_token,
        ];
    }

    public function ensureBindingToken(V4PaymentRequest $request): string
    {
        if (empty($request->binding_token)) {
            $request->binding_token = (string) \Illuminate\Support\Str::uuid();
            $request->save();
        }
        return $request->binding_token;
    }

    public function confirm(V4HockeyListing $listing, V4User $actor, array $receipt): array
    {
        $request = $listing->payment_request_id
            ? V4PaymentRequest::find($listing->payment_request_id)
            : null;

        $authId = (int) $actor->id;
        $isOwner = (int) $listing->user_id === $authId;
        $isParentPayer = $request && $request->parent_id && (int) $request->parent_id === $authId;

        $source = $receipt['source'];

        // iOS StoreKit2: decode the JWS receipt to (a) bind it to THIS listing's
        // payment request via appAccountToken, and (b) dedup on the real transactionId
        // — the client `purchase_id` is unreliable under SK2 (can arrive as "0").
        // Signature verification is a tracked follow-up (the SEAM below).
        if ($source === 'ios' && $request) {
            $jws = $receipt['verification_data']['server_verification_data'] ?? null;
            $decoded = Sk2ReceiptDecoder::decode(is_string($jws) ? $jws : null);
            if ($decoded) {
                if ($this->decider->bindingMismatch($decoded['app_account_token'] ?? null, $request->binding_token)) {
                    return ['code' => 'binding_mismatch', 'http' => 422, 'payload' => [
                        'message' => 'This receipt does not belong to this listing.',
                    ]];
                }
                if (!empty($decoded['transaction_id'])) {
                    $receipt['purchase_id'] = $decoded['transaction_id'];
                }
            }
        }

        $purchaseId = $receipt['purchase_id'] ?? null;

        $duplicate = null;
        if (!empty($purchaseId)) {
            $duplicate = V4PaymentTransaction::where('purchase_id', $purchaseId)
                ->where('source', $source)
                ->first();
        }

        $successTxn = $request
            ? V4PaymentTransaction::where('payment_request_id', $request->id)
                ->where('status', V4PaymentTransaction::STATUS_SUCCESS)
                ->latest('id')
                ->first()
            : null;

        $code = $this->decider->confirm([
            'listing_status' => $listing->status,
            'has_request' => (bool) $request,
            'request_status' => $request?->status,
            'is_owner' => $isOwner,
            'is_parent_payer' => $isParentPayer,
            'purchase_id_provided' => !empty($purchaseId),
            'duplicate_txn_exists' => (bool) $duplicate,
            'success_txn_exists' => (bool) $successTxn,
        ]);

        switch ($code) {
            case HockeyListingPaymentDecider::CONFIRM_UNAUTHORIZED:
                return ['code' => $code, 'http' => 403, 'payload' => ['message' => 'Unauthorized.']];

            case HockeyListingPaymentDecider::CONFIRM_DUPLICATE:
                // Idempotent: the store receipt was already processed.
                return ['code' => $code, 'http' => 200, 'payload' => [
                    'message' => 'Payment already processed.',
                    'listing_id' => $listing->id,
                    'listing_status' => $listing->status,
                    'payment_transaction_id' => $duplicate->id,
                ]];

            case HockeyListingPaymentDecider::CONFIRM_ALREADY_PUBLISHED:
                return ['code' => $code, 'http' => 200, 'payload' => [
                    'message' => 'Listing is already published.',
                    'listing_id' => $listing->id,
                    'listing_status' => $listing->status,
                    'payment_transaction_id' => $successTxn?->id,
                ]];

            case HockeyListingPaymentDecider::CONFIRM_SELF_HEAL_PUBLISH:
                DB::transaction(function () use ($request, $listing) {
                    $request->markPaid();
                    $listing->markPublished();
                });
                return ['code' => $code, 'http' => 200, 'payload' => [
                    'message' => 'Listing published.',
                    'listing_id' => $listing->id,
                    'listing_status' => $listing->status,
                    'payment_request_id' => $request->id,
                    'payment_transaction_id' => $successTxn->id,
                ]];

            case HockeyListingPaymentDecider::CONFIRM_RECOVER_PUBLISH:
                $fee = $this->feeProduct();
                if (!$fee) {
                    return ['code' => 'fee_missing', 'http' => 404, 'payload' => ['message' => 'Listing fee product not found or inactive.']];
                }
                $recovered = DB::transaction(function () use ($listing, $actor, $receipt, $source, $fee) {
                    $request = V4PaymentRequest::create([
                        'payer_id' => $actor->id,
                        'player_id' => $listing->user_id,
                        'in_app_purchase_id' => $fee->id,
                        'amount_cents' => $fee->amount_cents,
                        'currency' => $fee->currency,
                        'status' => V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                        'meta' => ['purpose' => 'hockey_listing', 'listing_id' => $listing->id, 'recovered' => true],
                    ]);
                    $listing->payment_request_id = $request->id;
                    $listing->save();

                    $txn = $this->recordSuccessTransaction($request, $actor, $receipt, $source);
                    $request->markPaid();
                    $listing->markPublished();
                    return ['request' => $request, 'txn' => $txn];
                });
                return [
                    'code' => $code,
                    'http' => 200,
                    'payload' => [
                        'message' => 'Payment confirmed. Your listing is now live.',
                        'listing_id' => $listing->id,
                        'listing_status' => $listing->status,
                        'listed_at' => $listing->listed_at,
                        'payment_request_id' => $recovered['request']->id,
                        'payment_transaction_id' => $recovered['txn']->id,
                        'recovered' => true,
                    ],
                    'request' => $recovered['request'],
                    'listing' => $listing,
                    'is_parent_payer' => false,
                ];

            case HockeyListingPaymentDecider::CONFIRM_NO_ACTIVE_REQUEST:
                return ['code' => $code, 'http' => 400, 'payload' => ['message' => 'No active payment request found. Call initiate-payment first.']];

            case HockeyListingPaymentDecider::CONFIRM_NOT_CONFIRMABLE:
                return ['code' => $code, 'http' => 400, 'payload' => ['message' => 'Payment request is not in a confirmable state.']];

            case HockeyListingPaymentDecider::CONFIRM_PARENT_ONLY:
                return ['code' => $code, 'http' => 403, 'payload' => ['message' => 'Only the parent can confirm this payment.']];
        }

        // CONFIRM_PROCEED — record transaction + publish.
        // SEAM: server-side receipt verification (Apple/Google) can be inserted here before success.
        $transaction = DB::transaction(function () use ($request, $actor, $receipt, $source, $listing) {
            $txn = $this->recordSuccessTransaction($request, $actor, $receipt, $source);
            $request->markPaid();
            $listing->markPublished();
            return $txn;
        });

        return [
            'code' => $code,
            'http' => 200,
            'payload' => [
                'message' => 'Payment confirmed. Your listing is now live.',
                'listing_id' => $listing->id,
                'listing_status' => $listing->status,
                'listed_at' => $listing->listed_at,
                'payment_request_id' => $request->id,
                'payment_transaction_id' => $transaction->id,
            ],
            'request' => $request,
            'listing' => $listing,
            'is_parent_payer' => $isParentPayer,
        ];
    }

    private function recordSuccessTransaction(V4PaymentRequest $request, V4User $actor, array $receipt, string $source): V4PaymentTransaction
    {
        return V4PaymentTransaction::create([
            'payment_request_id' => $request->id,
            'payer_id' => $actor->id,
            'amount_cents' => $request->amount_cents,
            'currency' => $request->currency,
            'gateway' => $this->decider->gatewayForSource($source),
            'gateway_reference' => $source . '_' . uniqid() . '_' . time(),
            'status' => V4PaymentTransaction::STATUS_SUCCESS,
            'purchase_id' => $receipt['purchase_id'] ?? null,
            'source' => $source,
            'verification_data' => $receipt['verification_data'] ?? null,
            'store_status' => $receipt['store_status'] ?? null,
            'transaction_date' => $receipt['transaction_date'] ?? null,
            'payload' => $receipt['payload'] ?? null,
        ]);
    }

    public function status(V4HockeyListing $listing): array
    {
        $request = $listing->relationLoaded('paymentRequest')
            ? $listing->paymentRequest
            : $listing->load('paymentRequest.inAppPurchase')->paymentRequest;

        return [
            'payload' => [
                'listing_id' => $listing->id,
                'listing_status' => $listing->status,
                'is_published' => $listing->status === V4HockeyListing::STATUS_PUBLISHED,
                'awaiting_parent' => $request
                    && $request->status === V4PaymentRequest::STATUS_PENDING,
                'payment_request_id' => $request?->id,
                'payment_status' => $request?->status,
                'sku' => optional($request?->inAppPurchase)->sku,
                'amount_cents' => $request?->amount_cents,
                'currency' => $request?->currency,
                'formatted_amount' => $request?->formatted_amount,
            ],
        ];
    }

    public function reject(V4PaymentRequest $request, V4HockeyListing $listing, ?string $reason): void
    {
        DB::transaction(function () use ($request, $listing, $reason) {
            $request->markParentRejected($reason);
            $listing->markPaymentRejected();
        });

        $request->loadMissing('notification');
        if ($request->notification) {
            $request->notification->delete();
        }
    }
}
