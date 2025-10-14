<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\EvaluationSubmission;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class V4PaymentController extends Controller
{

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Process payment for in-app purchase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processPayment(Request $request): JsonResponse
    {
        try {
            $user      = Auth::guard('v4api')->user();
            $validated = $request->validate([
                'sku'       => 'required|string|exists:v4_in_app_purchases,sku',
                'player_id' => 'nullable|integer|exists:v4_users,id',
            ]);

            $inAppPurchase = V4InAppPurchase::where('sku', $validated['sku'])->where('active', true)->first();
            if (! $inAppPurchase) {
                return response()->json(['success' => false, 'message' => 'In-app purchase not found or inactive'], 404);
            }

            $payerId  = $user->id;
            $playerId = $validated['player_id'] ?? $payerId;
            $player   = V4User::find($playerId);

            if (! $player) {
                return response()->json(['success' => false, 'message' => 'Player not found'], 404);
            }

            // Validate payer eligibility and relationship
            if ($player->is_child) {
                if (! $player->parent_id || $player->parent_id != $payerId) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized. Only the parent can make payment for this child.'], 403);
                }
            } else {
                if ($playerId != $payerId) {
                    return response()->json(['success' => false, 'message' => 'Cannot make payment for another user.'], 403);
                }
            }

            // Get latest payment request
            $latestPayment = V4PaymentRequest::where('player_id', $playerId)
                ->where('in_app_purchase_id', $inAppPurchase->id)
                ->orderBy('updated_at', 'desc')
                ->first();

            // Handle payment_initiated status
            if ($latestPayment && $latestPayment->status === V4PaymentRequest::STATUS_PAYMENT_INITIATED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already in process',
                    'data'    => [
                        'sku'                => $inAppPurchase->sku,
                        'title'              => $inAppPurchase->title,
                        'payment_request_id' => $latestPayment->id,
                        'status'             => $latestPayment->status,
                    ],
                ], 400);
            }

            // Handle paid status - check evaluation submission
            if ($latestPayment && $latestPayment->status === V4PaymentRequest::STATUS_PAID) {
                $submission = EvaluationSubmission::where('payment_request_id', $latestPayment->id)
                    ->where('player_id', $playerId)
                    ->first();

                if (! $submission || $submission->status === EvaluationSubmission::STATUS_PENDING) {
                    return response()->json(['success' => false, 'message' => 'Video submission pending for previous payment'], 400);
                }

                if (in_array($submission->status, [EvaluationSubmission::STATUS_UPLOADED, EvaluationSubmission::STATUS_ASSIGNED])) {
                    return response()->json(['success' => false, 'message' => 'Previous evaluation under process'], 400);
                }

                // If status is rejected or completed, continue to create new payment
            }

            // Handle failed status - create new transaction and update
            if ($latestPayment && $latestPayment->status === V4PaymentRequest::STATUS_FAILED) {
                // Transaction for payment request and transaction creation
                DB::beginTransaction();
                try {
                    $transaction = V4PaymentTransaction::create([
                        'payment_request_id' => $latestPayment->id,
                        'payer_id'           => $payerId,
                        'amount_cents'       => $inAppPurchase->amount_cents,
                        'currency'           => $inAppPurchase->currency,
                        'gateway'            => 'internal',
                        'gateway_reference'  => 'internal_' . uniqid() . '_' . time(),
                        'status'             => V4PaymentTransaction::STATUS_SUCCESS,
                    ]);

                    $latestPayment->markPaid();

                    DB::commit();

                    // Create evaluation submission entry outside transaction
                    try {
                        $submission = EvaluationSubmission::create([
                            'player_id'          => $playerId,
                            'payment_request_id' => $latestPayment->id,
                            'status'             => EvaluationSubmission::STATUS_PENDING,
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Payment processed successfully',
                            'data'    => [
                                'sku'                    => $inAppPurchase->sku,
                                'title'                  => $inAppPurchase->title,
                                'payment_request_id'     => $latestPayment->id,
                                'payment_transaction_id' => $transaction->id,
                                'submission_id'          => $submission->id,
                            ],
                        ], 201);
                    } catch (Exception $submissionError) {
                        Log::error('Failed to create submission after payment', [
                            'payment_request_id' => $latestPayment->id,
                            'error'              => $submissionError->getMessage(),
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Payment processed successfully',
                            'data'    => [
                                'sku'                    => $inAppPurchase->sku,
                                'title'                  => $inAppPurchase->title,
                                'payment_request_id'     => $latestPayment->id,
                                'payment_transaction_id' => $transaction->id,
                                'submission_id'          => null,
                            ],
                        ], 201);
                    }
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            // Create new payment request - wrapped in transaction
            DB::beginTransaction();
            try {
                $paymentRequestData = [
                    'payer_id'           => $payerId,
                    'player_id'          => $playerId,
                    'in_app_purchase_id' => $inAppPurchase->id,
                    'amount_cents'       => $inAppPurchase->amount_cents,
                    'currency'           => $inAppPurchase->currency,
                    'status'             => V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                ];

                // Only add parent_id if player is a child
                if ($player->is_child && $player->parent_id) {
                    $paymentRequestData['parent_id'] = $player->parent_id;
                }

                $paymentRequest = V4PaymentRequest::create($paymentRequestData);

                $transaction = V4PaymentTransaction::create([
                    'payment_request_id' => $paymentRequest->id,
                    'payer_id'           => $payerId,
                    'amount_cents'       => $inAppPurchase->amount_cents,
                    'currency'           => $inAppPurchase->currency,
                    'gateway'            => 'internal',
                    'gateway_reference'  => 'internal_' . uniqid() . '_' . time(),
                    'status'             => V4PaymentTransaction::STATUS_SUCCESS,
                ]);

                $paymentRequest->markPaid();

                DB::commit();

                // Create evaluation submission entry outside transaction
                try {
                    $submission = EvaluationSubmission::create([
                        'player_id'          => $playerId,
                        'payment_request_id' => $paymentRequest->id,
                        'status'             => EvaluationSubmission::STATUS_PENDING,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'data'    => [
                            'sku'                    => $inAppPurchase->sku,
                            'title'                  => $inAppPurchase->title,
                            'payment_request_id'     => $paymentRequest->id,
                            'payment_transaction_id' => $transaction->id,
                            'submission_id'          => $submission->id,
                        ],
                    ], 201);
                } catch (Exception $submissionError) {
                    Log::error('Failed to create submission after payment', [
                        'payment_request_id' => $paymentRequest->id,
                        'error'              => $submissionError->getMessage(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment processed successfully but submission creation failed',
                        'data'    => [
                            'sku'                    => $inAppPurchase->sku,
                            'title'                  => $inAppPurchase->title,
                            'payment_request_id'     => $paymentRequest->id,
                            'payment_transaction_id' => $transaction->id,
                            'submission_id'          => null,
                        ],
                    ], 201);
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to process payment', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Check if payment is done for a specific SKU
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function isPaymentDone(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $validated = $request->validate([
                'sku' => 'required|string|exists:v4_in_app_purchases,sku',
            ]);

            // Get the in-app purchase
            $inAppPurchase = V4InAppPurchase::where('sku', $validated['sku'])
                ->where('active', true)
                ->first();

            if (! $inAppPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'In-app purchase not found or inactive',
                ], 404);
            }

            // Check if payment exists and is successful
            $paymentRequest = V4PaymentRequest::where('payer_id', $user->id)
                ->where('in_app_purchase_id', $inAppPurchase->id)
                ->where('status', V4PaymentRequest::STATUS_PAID)
                ->whereHas('paymentTransaction', function ($query) {
                    $query->where('status', V4PaymentTransaction::STATUS_SUCCESS);
                })
                ->first();

            if ($paymentRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment is completed',
                    'data'    => [
                        'is_paid'                => true,
                        'sku'                    => $inAppPurchase->sku,
                        'title'                  => $inAppPurchase->title,
                        'payment_transaction_id' => $paymentRequest->paymentTransaction->id,
                        'paid_at'                => $paymentRequest->updated_at,
                    ],
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found or invalid',
                    'data'    => [
                        'is_paid' => false,
                        'sku'     => $inAppPurchase->sku,
                    ],
                ], 404);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error checking payment status: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'sku'     => $request->input('sku'),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function requestPaymentToParent(Request $request): JsonResponse
    {
        try {
            $user      = Auth::guard('v4api')->user();
            $validated = $request->validate([
                'sku' => 'required|string|exists:v4_in_app_purchases,sku',
            ]);
            DB::beginTransaction();
            try {
                $sku = $validated['sku'];

                $inAppPurchase = V4InAppPurchase::where('sku', $sku)->where('active', true)->first();
                if (! $inAppPurchase) {
                    return response()->json(['success' => false, 'message' => 'In-app purchase not found or inactive'], 404);
                }

                $payerId  = '88'; //$user->parent_id;
                $playerId = $user->id;

                $player = V4User::find($user->id);

                if (! $player) {
                    return response()->json(['success' => false, 'message' => 'Player not found'], 404);
                }

                if ($player->is_child) {
                    if (! $player->parent_id || $player->parent_id != $payerId) {
                        return response()->json(['success' => false, 'message' => 'Unauthorized. Only the parent can make payment for this child.'], 403);
                    }
                }

                $paymentRequestData = [
                    'payer_id'           => $payerId,
                    'player_id'          => $playerId,
                    'in_app_purchase_id' => $inAppPurchase->id,
                    'amount_cents'       => $inAppPurchase->amount_cents,
                    'currency'           => $inAppPurchase->currency,
                    'status'             => V4PaymentRequest::STATUS_PENDING,
                ];
                $paymentRequest = V4PaymentRequest::create($paymentRequestData);

                $paymentRequest->load([
                    'player',
                    'parent'
                ]);


                $notification = $this->sendPaymentRequestNotification($paymentRequest, $sku);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment request sent to parent',
                    'data'    => [
                        'payment_request'   => $paymentRequest,
                        'notification_sent' => (bool) $notification,
                    ],
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error processing payment Rollback: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'message' => 'Failed to process payment', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
            }
        } catch (ValidationException $e) {
            Log::error('Error processing payment validation: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to process payment', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    protected function sendPaymentRequestNotification(V4PaymentRequest $paymentRequest, String $sku)
    {
        try {


            Log::info('loadSendPaymentRequestNotification', [
                'parent_id' => $paymentRequest->parent_id
            ]);

            $child  = $paymentRequest->player;
            $parent = $child->parent;

            $amount   = $paymentRequest->amount_cents;
            $currency = $paymentRequest->currency;
            $purpose  = "video_evaluation_payment_request";

            $title   = "💰 Payment Request from " . $child->name;
            $message = 'There is a payment approval request from your child ' . $child->name;

            $data = [
                'payment_request_id' => $paymentRequest->id,
                'sku'                => $sku,
                'child'              => $child,
                'amount'             => $amount,
                'currency'           => $currency,
                'purpose'            => $purpose,
                'status'             => 'pending',
                'action_required'    => true,
                'quick_actions'      => ['approve', 'decline'],
                // 'parent' => $parent,
            ];
            $icon  = 'payments';
            $color = '#2196F3'; // Blue for low urgency

            $notification = $this->notificationService->sendToUserWithMaterialIcon(
                $parent,
                $title,
                $message,
                $icon,
                $color,
                $data,
                'payment_request_received',
                '/payment-requests/{$paymentRequest->id}', // Redirect to payment request details
                'payment_request_action',
                $paymentRequest
            );

            return $notification;
        } catch (Exception $e) {

            Log::error('errorSendPaymentRequestNotification ' . $e->getMessage(), [
                $parent,
                $title,
                $message,
                $icon,
                $color,
                $data,
                'payment_request_received',
                "/payment-requests/{$paymentRequest->id}", // Redirect to payment request details
                'payment_request_action',
                $paymentRequest,
                'trace' => $e->getTraceAsString(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ]);

            return null;
        }
    }
}
