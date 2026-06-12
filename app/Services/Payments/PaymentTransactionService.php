<?php

namespace App\Services\Payments;

use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use Illuminate\Support\Facades\DB;

class PaymentTransactionService
{
    /**
     * Sentinel purchase_id values produced by Xcode StoreKit testing.
     * These are stored as NULL so they don't poison the unique constraint
     * on (purchase_id, source).
     */
    private const SANDBOX_SENTINELS = ['0', ''];

    /**
     * Idempotently record a successful payment for the given payment request.
     *
     * Concurrency-safe: row-locks the payment_request inside a DB transaction,
     * short-circuits if the request is already marked paid, and dedups real
     * (non-sandbox) purchase_ids against historical replays.
     *
     * @return array{0: V4PaymentTransaction, 1: bool}
     *         [transaction, wasExisting] — wasExisting is true when an
     *         existing successful transaction was returned instead of
     *         inserting a new row.
     */
    public function recordSuccess(int $paymentRequestId, int $payerId, array $data): array
    {
        return DB::transaction(function () use ($paymentRequestId, $payerId, $data) {
            $paymentRequest = V4PaymentRequest::lockForUpdate()->findOrFail($paymentRequestId);

            // Idempotent short-circuit: same payment_request, already paid.
            if ($paymentRequest->status === V4PaymentRequest::STATUS_PAID) {
                $existing = V4PaymentTransaction::where('payment_request_id', $paymentRequest->id)
                    ->where('status', V4PaymentTransaction::STATUS_SUCCESS)
                    ->first();
                if ($existing) {
                    return [$existing, true];
                }
            }

            $purchaseId = $this->normalizePurchaseId($data['purchase_id'] ?? null);
            $source = $data['source'] ?? null;

            // Production replay protection. Sandbox sentinels bypass this layer;
            // the partial unique index on (payment_request_id) WHERE status='success'
            // protects the sandbox path.
            if ($purchaseId !== null && $source !== null) {
                $duplicate = V4PaymentTransaction::where('purchase_id', $purchaseId)
                    ->where('source', $source)
                    ->first();
                if ($duplicate) {
                    return [$duplicate, true];
                }
            }

            $verification = $data['verification_data'] ?? null;
            $payload = $data['payload'] ?? null;

            $transaction = V4PaymentTransaction::create([
                'payment_request_id' => $paymentRequest->id,
                'payer_id'           => $payerId,
                'amount_cents'       => $paymentRequest->amount_cents,
                'currency'           => $paymentRequest->currency,
                'gateway'            => 'internal',
                'gateway_reference'  => 'internal_' . uniqid() . '_' . time(),
                'status'             => V4PaymentTransaction::STATUS_SUCCESS,
                'purchase_id'        => $purchaseId,
                'source'             => $source,
                'verification_data'  => is_array($verification) ? json_encode($verification) : $verification,
                'store_status'       => $data['store_status'] ?? null,
                'transaction_date'   => $data['transaction_date'] ?? null,
                'payload'            => is_array($payload) ? json_encode($payload) : $payload,
            ]);

            $paymentRequest->markPaid();

            return [$transaction, false];
        });
    }

    private function normalizePurchaseId($raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = is_string($raw) ? $raw : (string) $raw;

        if (in_array($value, self::SANDBOX_SENTINELS, true)) {
            return null;
        }

        return $value;
    }
}
