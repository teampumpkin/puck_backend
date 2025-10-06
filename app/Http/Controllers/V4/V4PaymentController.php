<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class V4PaymentController extends Controller
{
    /**
     * Process payment for in-app purchase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processPayment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $validated = $request->validate([
                'sku' => 'required|string|exists:v4_in_app_purchases,sku',
                'player_id' => 'nullable|integer|exists:v4_users,id'
            ]);

            // Get the in-app purchase
            $inAppPurchase = V4InAppPurchase::where('sku', $validated['sku'])
                ->where('active', true)
                ->first();

            if (!$inAppPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'In-app purchase not found or inactive'
                ], 404);
            }

            // Check if the user is a parent (has children)
            $isParent = V4User::where('parent_id', $user->id)->exists();

            // Determine player_id based on request
            if (isset($validated['player_id'])) {
                // Parent is making payment for specific child
                $playerId = $validated['player_id'];
                $player = V4User::findOrFail($playerId);

                // Check if player is a child and belongs to this parent
                if (is_null($player->parent_id) || $player->parent_id != $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Player not found or not authorized'
                    ], 403);
                }
            } else {
                // If user is a parent, player_id is required
                if ($isParent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'player_id is required when making a payment for a child'
                    ], 422);
                }

                // User is making payment for themselves
                $playerId = $user->id;
                $player = $user;

                // Check if user is a child (should not be able to make their own payment)
                if (!is_null($player->parent_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Child players cannot make payments. Parent must make payment.'
                    ], 403);
                }
            }

            // Check if payment already exists and is successful
            $existingPayment = V4PaymentRequest::where('player_id', $playerId)
                ->where('in_app_purchase_id', $inAppPurchase->id)
                ->whereHas('paymentTransaction', function ($query) {
                    $query->where('status', V4PaymentTransaction::STATUS_SUCCESS);
                })
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already completed',
                    'data' => [
                        'sku' => $inAppPurchase->sku,
                        'title' => $inAppPurchase->title,
                        'payment_transaction_id' => $existingPayment->paymentTransaction->id
                    ]
                ], 200);
            }

            // Determine if this is a child payment
            $isChild = !is_null($player->parent_id);

            // Create payment request
            $paymentRequest = V4PaymentRequest::create([
                'payer_id' => $user->id,
                'parent_id' => $isChild ? $user->id : null,
                'player_id' => $playerId,
                'in_app_purchase_id' => $inAppPurchase->id,
                'amount_cents' => $inAppPurchase->amount_cents,
                'currency' => $inAppPurchase->currency,
                'status' => V4PaymentRequest::STATUS_PAYMENT_INITIATED
            ]);

            // Create payment transaction with success status
            $paymentTransaction = V4PaymentTransaction::create([
                'payment_request_id' => $paymentRequest->id,
                'payer_id' => $user->id,
                'amount_cents' => $inAppPurchase->amount_cents,
                'currency' => $inAppPurchase->currency,
                'gateway' => 'internal',
                'gateway_reference' => 'internal_' . time(),
                'status' => V4PaymentTransaction::STATUS_SUCCESS
            ]);

            // Update payment request to paid
            $paymentRequest->markPaid();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => [
                    'sku' => $inAppPurchase->sku,
                    'title' => $inAppPurchase->title,
                    'payment_transaction_id' => $paymentTransaction->id
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}