<?php

namespace App\Services\Payments;

use App\Models\V4AppSetting;
use App\Models\V4Event;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Event platform-fee payments, mirroring HockeyListingPaymentService.
 *
 * PORT NOTE: the full StoreKit2 JWS decode + rootReconcile/recovery/self-heal
 * hardening from HockeyListingPaymentService (binding-token cross-listing replay,
 * orphan-receipt logging) is intentionally not ported here yet. This service
 * covers the core paths — adult pay, child->parent approve/reject, dedup-safe
 * transaction recording (partial-unique + SAVEPOINT), idempotent replay — which
 * are what the unit suite exercises. Port the remaining SK2 hardening during the
 * payment-flow review and validate against real Apple/Google receipts.
 */
class EventPaymentService
{
    /** v4_app_settings key for the global events platform-fee switch. Missing row = ON (fee required). */
    public const FEE_SETTING_KEY = 'event_platform_fee_enabled';

    public function __construct(private NotificationService $notifications)
    {
    }

    /** Global switch. Absent setting defaults to ON so current behaviour is preserved. */
    public static function feeEnabled(): bool
    {
        $row = V4AppSetting::where('name', self::FEE_SETTING_KEY)->first();

        return $row === null ? true : $row->value === '1';
    }

    public static function setFeeEnabled(bool $on): void
    {
        V4AppSetting::updateOrCreate(['name' => self::FEE_SETTING_KEY], ['value' => $on ? '1' : '0']);
    }

    public function feeProduct(): ?V4InAppPurchase
    {
        return V4InAppPurchase::where('sku', config('services.event.fee_sku'))
            ->where('active', true)
            ->first();
    }

    public function initiate(V4Event $event, V4User $actor): array
    {
        // Global fee switch OFF: no role pays. Publish immediately, no payment request,
        // no SKU, no parent approval. Runs before any child/parent branch so it covers all roles.
        if (! self::feeEnabled()) {
            if ($event->status !== V4Event::STATUS_PUBLISHED) {
                $event->update(['status' => V4Event::STATUS_PUBLISHED, 'published_at' => now()]);
            }

            return ['http' => 200, 'payload' => ['success' => true, 'data' => [
                'fee_waived' => true,
                'awaiting_parent' => false,
                'payment_request_id' => null,
                'sku' => null,
                'status' => V4Event::STATUS_PUBLISHED,
            ]]];
        }

        $fee = $this->feeProduct();
        if (! $fee) {
            return ['http' => 404, 'payload' => ['success' => false, 'message' => 'Event fee product not found or inactive.']];
        }
        if ($event->status === V4Event::STATUS_PUBLISHED) {
            return ['http' => 400, 'payload' => ['success' => false, 'message' => 'Event is already published.']];
        }

        $isChild = (bool) ($actor->is_child ?? false);
        $parentId = $isChild ? $actor->parent_id : null;
        if ($isChild && ! $parentId) {
            return ['http' => 400, 'payload' => ['success' => false, 'message' => 'Child account is missing a parent.']];
        }

        $token = strtolower((string) Str::uuid());
        $request = DB::transaction(function () use ($event, $actor, $isChild, $parentId, $fee, $token) {
            $req = V4PaymentRequest::create([
                'payer_id' => $isChild ? $parentId : $actor->id,
                'parent_id' => $isChild ? $parentId : null,
                'player_id' => $actor->id,
                'in_app_purchase_id' => $fee->id,
                'amount_cents' => $fee->amount_cents,
                'currency' => $fee->currency,
                'status' => $isChild ? V4PaymentRequest::STATUS_PENDING : V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                'binding_token' => $token,
                'meta' => ['purpose' => 'event', 'event_id' => $event->id],
            ]);
            $event->payment_request_id = $req->id;
            if ($isChild) {
                $event->status = V4Event::STATUS_PAYMENT_REQUESTED;
            }
            $event->save();

            return $req;
        });

        if ($isChild) {
            $this->notify($actor->parent, 'Event payment request',
                "{$actor->first_name} needs approval to publish \"{$event->name}\".",
                $event, 'event_payment_request', "/events/parent-payment/{$event->id}", $request);
        }

        return ['http' => 200, 'payload' => ['success' => true, 'data' => [
            'awaiting_parent' => $isChild,
            'payment_request_id' => $request->id,
            'sku' => $isChild ? null : $fee->sku,
            'amount_cents' => $fee->amount_cents,
            'currency' => $fee->currency,
            'formatted_amount' => $fee->formatted_amount,
            'binding_token' => $token,
        ]]];
    }

    public function confirm(V4Event $event, V4User $actor, array $receipt): array
    {
        $request = $event->payment_request_id ? V4PaymentRequest::find($event->payment_request_id) : null;
        if (! $request) {
            return ['http' => 400, 'payload' => ['success' => false, 'message' => 'No active payment request. Call initiate-payment first.']];
        }

        // Authorize: child flow -> parent only; adult flow -> owner only.
        if ($request->parent_id) {
            if ((int) $actor->id !== (int) $request->parent_id) {
                return ['http' => 403, 'payload' => ['success' => false, 'message' => 'Only the parent can confirm this payment.']];
            }
        } elseif ((int) $actor->id !== (int) $event->user_id) {
            return ['http' => 403, 'payload' => ['success' => false, 'message' => 'Unauthorized.']];
        }

        // Terminal states are not confirmable — a declined/failed request must be
        // restarted via initiate, never re-confirmed into a published event.
        if (in_array($request->status, [V4PaymentRequest::STATUS_PARENT_REJECTED, V4PaymentRequest::STATUS_FAILED], true)) {
            return ['http' => 409, 'payload' => ['success' => false, 'message' => 'This payment request was declined or failed. Please start a new payment.']];
        }

        // Client always sends a real platform (ios/android/web/...); mirror its own
        // fallback rather than fabricating a non-platform 'internal' value.
        $source = $receipt['source'] ?? 'android';
        $purchaseId = $receipt['purchase_id'] ?? null;

        // Idempotent: already published.
        if ($event->status === V4Event::STATUS_PUBLISHED) {
            return ['http' => 200, 'payload' => ['success' => true, 'message' => 'Event is already published.', 'event_id' => $event->id]];
        }

        // Idempotent: this store receipt was already processed.
        if (! empty($purchaseId)) {
            $dup = V4PaymentTransaction::where('purchase_id', $purchaseId)->where('source', $source)->first();
            if ($dup) {
                DB::transaction(function () use ($request, $event) {
                    $request->markPaid();
                    $event->update(['status' => V4Event::STATUS_PUBLISHED, 'published_at' => now()]);
                });

                return ['http' => 200, 'payload' => ['success' => true, 'message' => 'Payment already processed.', 'event_id' => $event->id, 'payment_transaction_id' => $dup->id]];
            }
        }

        // SEAM: server-side receipt verification (Apple/Google) belongs here before success.
        $txn = DB::transaction(function () use ($request, $actor, $receipt, $source, $event) {
            $t = $this->recordSuccessTransaction($request, $actor, $receipt, $source);
            $request->markPaid();
            $event->update(['status' => V4Event::STATUS_PUBLISHED, 'published_at' => now()]);

            return $t;
        });

        if ($request->parent_id) {
            // Clear the parent's pending approval notification (mirrors hockey/marketplace),
            // then record the approval for BOTH the parent (approver) and child (creator).
            $request->loadMissing('notification');
            if ($request->notification) {
                $request->notification->delete();
            }
            // Approved record -> parent (approver)
            $this->notify($request->parent, 'Event payment approved',
                "You approved the fee for \"{$event->name}\". It is now live.",
                $event, 'event_payment_approved', "/events/detail/{$event->id}");
            // Published record -> child (creator)
            $this->notify($event->creator, 'Event published',
                "Your event \"{$event->name}\" is now live.",
                $event, 'event_payment_approved', "/events/detail/{$event->id}");
        }

        return ['http' => 200, 'payload' => ['success' => true, 'message' => 'Payment confirmed. Your event is now live.', 'event_id' => $event->id, 'payment_transaction_id' => $txn->id]];
    }

    public function reject(V4Event $event, V4User $actor, ?string $reason): array
    {
        $request = $event->payment_request_id ? V4PaymentRequest::find($event->payment_request_id) : null;
        if (! $request) {
            return ['http' => 400, 'payload' => ['success' => false, 'message' => 'No active payment request.']];
        }
        if (! $request->parent_id || (int) $actor->id !== (int) $request->parent_id) {
            return ['http' => 403, 'payload' => ['success' => false, 'message' => 'Only the parent can reject this payment.']];
        }

        DB::transaction(fn () => $request->markParentRejected($reason));

        $request->loadMissing('notification');
        if ($request->notification) {
            $request->notification->delete();
        }

        // Record the decline for BOTH the parent (rejecter) and child (creator),
        // mirroring the approval path.
        $this->notify($request->parent, 'Event payment declined',
            "You declined the fee for \"{$event->name}\".",
            $event, 'event_payment_rejected', "/events/detail/{$event->id}");
        $this->notify($event->creator, 'Event payment declined',
            "Your request to publish \"{$event->name}\" was declined.",
            $event, 'event_payment_rejected', "/events/detail/{$event->id}");

        return ['http' => 200, 'payload' => ['success' => true, 'message' => 'Payment request rejected.']];
    }

    public function status(V4Event $event): array
    {
        $request = $event->payment_request_id
            ? V4PaymentRequest::with('inAppPurchase')->find($event->payment_request_id)
            : null;

        return ['http' => 200, 'payload' => ['success' => true, 'data' => [
            'event_id' => $event->id,
            'event_status' => $event->status,
            'is_published' => $event->status === V4Event::STATUS_PUBLISHED,
            'awaiting_parent' => $request && $request->status === V4PaymentRequest::STATUS_PENDING,
            'payment_request_id' => $request?->id,
            'payment_status' => $request?->status,
            'sku' => optional($request?->inAppPurchase)->sku,
            'amount_cents' => $request?->amount_cents,
            'currency' => $request?->currency,
            'formatted_amount' => optional($request?->inAppPurchase)->formatted_amount,
        ]]];
    }

    public function parentPayment(V4Event $event, V4User $actor): array
    {
        $request = $event->payment_request_id
            ? V4PaymentRequest::with('inAppPurchase')->find($event->payment_request_id)
            : null;

        if (! $request || ! $request->parent_id || (int) $actor->id !== (int) $request->parent_id) {
            return ['http' => 403, 'payload' => ['success' => false, 'message' => 'Unauthorized.']];
        }

        return ['http' => 200, 'payload' => ['success' => true, 'data' => [
            'event_id' => $event->id,
            'event_name' => $event->name,
            'child_name' => optional($event->creator)->first_name,
            'sku' => optional($request->inAppPurchase)->sku,
            'amount_cents' => $request->amount_cents,
            'currency' => $request->currency,
            'formatted_amount' => optional($request->inAppPurchase)->formatted_amount,
            'binding_token' => $request->binding_token,
            'payment_request_id' => $request->id,
            'status' => $request->status,
        ]]];
    }

    private function recordSuccessTransaction(V4PaymentRequest $request, V4User $actor, array $receipt, string $source): V4PaymentTransaction
    {
        $purchaseId = $receipt['purchase_id'] ?? null;

        $existing = $this->findExistingSuccess($request, $purchaseId, $source);
        if ($existing) {
            return $existing;
        }

        try {
            // Nested transaction => SAVEPOINT, so a concurrent-replay unique violation
            // rolls back only to the savepoint and leaves the outer transaction usable.
            return DB::transaction(fn () => V4PaymentTransaction::create([
                'payment_request_id' => $request->id,
                'payer_id' => $actor->id,
                'amount_cents' => $request->amount_cents,
                'currency' => $request->currency,
                'gateway' => $this->gatewayForSource($source),
                'gateway_reference' => $source.'_'.uniqid().'_'.time(),
                'status' => V4PaymentTransaction::STATUS_SUCCESS,
                'purchase_id' => $purchaseId,
                'source' => $source,
                'verification_data' => $receipt['verification_data'] ?? null,
                'store_status' => $receipt['store_status'] ?? null,
                'transaction_date' => $receipt['transaction_date'] ?? null,
                'payload' => $receipt['payload'] ?? null,
            ]));
        } catch (QueryException $e) {
            // 23505 = Postgres unique_violation; 23000 = generic integrity constraint.
            if (! in_array((string) $e->getCode(), ['23505', '23000'], true)) {
                throw $e;
            }
            $existing = $this->findExistingSuccess($request, $purchaseId, $source);
            if ($existing) {
                return $existing;
            }
            throw $e;
        }
    }

    private function findExistingSuccess(V4PaymentRequest $request, ?string $purchaseId, string $source): ?V4PaymentTransaction
    {
        $byRequest = V4PaymentTransaction::where('payment_request_id', $request->id)
            ->where('status', V4PaymentTransaction::STATUS_SUCCESS)
            ->latest('id')
            ->first();
        if ($byRequest) {
            return $byRequest;
        }
        if (! empty($purchaseId) && ! in_array($purchaseId, ['0', ''], true)) {
            return V4PaymentTransaction::where('purchase_id', $purchaseId)
                ->where('source', $source)
                ->latest('id')
                ->first();
        }

        return null;
    }

    private function gatewayForSource(string $source): string
    {
        return $source === 'ios' ? 'app_store' : ($source === 'android' ? 'play_store' : 'internal');
    }

    private function notify(?V4User $user, string $title, string $message, V4Event $event, string $type, string $redirectUrl, ?V4PaymentRequest $reference = null): void
    {
        if (! $user) {
            return;
        }
        try {
            // $reference links the notification to the payment request (morphOne
            // `notification`) so confirm/reject can clear the parent's pending
            // approval notification — matching hockey/marketplace.
            $this->notifications->sendToUserWithImage(
                $user, $title, $message,
                optional($event->media()->first())->url ?? '',
                [], $type, $redirectUrl, $reference ? 'payment_request_action' : null, $reference
            );
        } catch (\Exception $e) {
            Log::error('Event payment notify failed', ['type' => $type, 'e' => $e->getMessage()]);
        }
    }
}
