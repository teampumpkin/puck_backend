<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\EvaluationSubmission;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use App\Services\NotificationService;
use App\Services\Payments\PaymentTransactionService;
use App\Services\Payments\PaymentValidator;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

class V4PaymentController extends Controller
{
    protected $errorTracker;


    protected $notificationService;

    public function __construct(ErrorTrackerInterface $errorTracker, NotificationService $notificationService)
    {
        $this->errorTracker = $errorTracker;
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
            $user = Auth::guard('v4api')->user();
            $validated = (new PaymentValidator)->validate($request);

            $inAppPurchase = V4InAppPurchase::where('sku', $validated['sku'])->where('active', true)->first();
            if (!$inAppPurchase) {
                return response()->json(['success' => false, 'message' => 'In-app purchase not found or inactive'], 404);
            }

            $payerId = $user->id;
            $playerId = $validated['player_id'] ?? $payerId;
            $player = V4User::find($playerId);

            if (!$player) {
                return response()->json(['success' => false, 'message' => 'Player not found'], 404);
            }

            // Validate payer eligibility and relationship
            if ($player->is_child) {
                if (!$player->parent_id || $player->parent_id != $payerId) {
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
                    'data' => [
                        'sku' => $inAppPurchase->sku,
                        'title' => $inAppPurchase->title,
                        'payment_request_id' => $latestPayment->id,
                        'status' => $latestPayment->status,
                    ],
                ], 400);
            }

            // Handle paid status - check evaluation submission
            if ($latestPayment && $latestPayment->status === V4PaymentRequest::STATUS_PAID) {
                $submission = EvaluationSubmission::where('payment_request_id', $latestPayment->id)
                    ->where('player_id', $playerId)
                    ->first();

                if (!$submission || $submission->status === EvaluationSubmission::STATUS_PENDING) {
                    return response()->json(['success' => false, 'message' => 'Submission is pending for previous payment'], 400);
                }

                if (in_array($submission->status, [EvaluationSubmission::STATUS_UPLOADED, EvaluationSubmission::STATUS_ASSIGNED])) {
                    return response()->json(['success' => false, 'message' => 'Previous submission is under process'], 400);
                }

                // If status is rejected or completed, continue to create new payment
            }

            // Handle failed status - create new transaction and update
            if ($latestPayment && $latestPayment->status === V4PaymentRequest::STATUS_FAILED) {
                try {
                    [$transaction, $wasExisting] = app(PaymentTransactionService::class)
                        ->recordSuccess($latestPayment->id, $payerId, $validated);

                    // Create evaluation submission entry outside transaction
                    try {
                        $submission = EvaluationSubmission::create([
                            'player_id' => $playerId,
                            'payment_request_id' => $latestPayment->id,
                            'status' => EvaluationSubmission::STATUS_PENDING,
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Payment processed successfully',
                            'data' => [
                                'sku' => $inAppPurchase->sku,
                                'title' => $inAppPurchase->title,
                                'payment_request_id' => $latestPayment->id,
                                'payment_transaction_id' => $transaction->id,
                                'submission_id' => $submission->id,
                            ],
                        ], 201);
                    } catch (Exception $submissionError) {
                        Log::error('Failed to create submission after payment', [
                            'payment_request_id' => $latestPayment->id,
                            'error' => $submissionError->getMessage(),
                        ]);

                        

            // Track error in Sentry
            $this->errorTracker->captureException($submissionError, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                            'success' => true,
                            'message' => 'Payment processed successfully',
                            'data' => [
                                'sku' => $inAppPurchase->sku,
                                'title' => $inAppPurchase->title,
                                'payment_request_id' => $latestPayment->id,
                                'payment_transaction_id' => $transaction->id,
                                'submission_id' => null,
                            ],
                        ], 201);
                    }
                } catch (Exception $e) {
                    $this->errorTracker->captureException($e, [
                        'action' => __METHOD__,
                    ]);
                    throw $e;
                }
            }

            // Handle pending status - parent approving child's payment request
            if ($latestPayment && $latestPayment->status === V4PaymentRequest::STATUS_PENDING) {
                // Verify this is parent paying for child
                if (!$player->is_child || $player->parent_id !== $payerId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only the parent can approve this pending payment request.',
                    ], 403);
                }

                if ($latestPayment->notification) {
                    $this->handlePaymentSuccessNotifications($latestPayment, $inAppPurchase, $player);
                }

                try {
                    [$transaction, $wasExisting] = app(PaymentTransactionService::class)
                        ->recordSuccess($latestPayment->id, $payerId, $validated);

                    // Create evaluation submission entry outside transaction
                    try {
                        $submission = EvaluationSubmission::create([
                            'player_id' => $playerId,
                            'payment_request_id' => $latestPayment->id,
                            'status' => EvaluationSubmission::STATUS_PENDING,
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Payment approved successfully',
                            'data' => [
                                'sku' => $inAppPurchase->sku,
                                'title' => $inAppPurchase->title,
                                'payment_request_id' => $latestPayment->id,
                                'payment_transaction_id' => $transaction->id,
                                'submission_id' => $submission->id,
                            ],
                        ], 201);
                    } catch (Exception $submissionError) {
                        Log::error('Failed to create submission after payment approval', [
                            'payment_request_id' => $latestPayment->id,
                            'error' => $submissionError->getMessage(),
                        ]);

                        

            // Track error in Sentry
            $this->errorTracker->captureException($submissionError, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                            'success' => true,
                            'message' => 'Payment approved successfully',
                            'data' => [
                                'sku' => $inAppPurchase->sku,
                                'title' => $inAppPurchase->title,
                                'payment_request_id' => $latestPayment->id,
                                'payment_transaction_id' => $transaction->id,
                                'submission_id' => null,
                            ],
                        ], 201);
                    }
                } catch (Exception $e) {
                    $this->errorTracker->captureException($e, [
                        'action' => __METHOD__,
                    ]);
                    throw $e;
                }
            } else {
                try {
                    $paymentRequestData = [
                        'payer_id' => $payerId,
                        'player_id' => $playerId,
                        'in_app_purchase_id' => $inAppPurchase->id,
                        'amount_cents' => $inAppPurchase->amount_cents,
                        'currency' => $inAppPurchase->currency,
                        'status' => V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                    ];

                    if ($player->is_child && $player->parent_id) {
                        $paymentRequestData['parent_id'] = $player->parent_id;
                    }

                    $paymentRequest = V4PaymentRequest::create($paymentRequestData);

                    [$transaction, $wasExisting] = app(PaymentTransactionService::class)
                        ->recordSuccess($paymentRequest->id, $payerId, $validated);

                    // Create evaluation submission entry outside transaction
                    try {
                        $submission = EvaluationSubmission::create([
                            'player_id' => $playerId,
                            'payment_request_id' => $paymentRequest->id,
                            'status' => EvaluationSubmission::STATUS_PENDING,
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Payment processed successfully',
                            'data' => [
                                'sku' => $inAppPurchase->sku,
                                'title' => $inAppPurchase->title,
                                'payment_request_id' => $paymentRequest->id,
                                'payment_transaction_id' => $transaction->id,
                                'submission_id' => $submission->id,
                            ],
                        ], 201);
                    } catch (Exception $submissionError) {
                        Log::error('Failed to create submission after payment', [
                            'payment_request_id' => $paymentRequest->id,
                            'error' => $submissionError->getMessage(),
                        ]);

                        

            // Track error in Sentry
            $this->errorTracker->captureException($submissionError, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                            'success' => true,
                            'message' => 'Payment processed successfully but submission creation failed',
                            'data' => [
                                'sku' => $inAppPurchase->sku,
                                'title' => $inAppPurchase->title,
                                'payment_request_id' => $paymentRequest->id,
                                'payment_transaction_id' => $transaction->id,
                                'submission_id' => null,
                            ],
                        ], 201);
                    }
                } catch (Exception $e) {
                    $this->errorTracker->captureException($e, [
                        'action' => __METHOD__,
                    ]);
                    throw $e;
                }
            }
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
                'error' => $e->getMessage(),
            ]);
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

            if (!$inAppPurchase) {
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
                    'data' => [
                        'is_paid' => true,
                        'sku' => $inAppPurchase->sku,
                        'title' => $inAppPurchase->title,
                        'payment_transaction_id' => $paymentRequest->paymentTransaction->id,
                        'paid_at' => $paymentRequest->updated_at,
                    ],
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found or invalid',
                    'data' => [
                        'is_paid' => false,
                        'sku' => $inAppPurchase->sku,
                    ],
                ], 404);
            }
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error checking payment status: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'sku' => $request->input('sku'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function requestPaymentToParent(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $validated = $request->validate([
                'sku' => 'required|string|exists:v4_in_app_purchases,sku',
            ]);
            DB::beginTransaction();
            try {
                $sku = $validated['sku'];

                $inAppPurchase = V4InAppPurchase::where('sku', $sku)->where('active', true)->first();
                if (!$inAppPurchase) {
                    return response()->json(['success' => false, 'message' => 'In-app purchase not found or inactive'], 404);
                }

                $payerId = $user->parent_id;
                $playerId = $user->id;

                $player = V4User::find($user->id);

                if (!$player) {
                    return response()->json(['success' => false, 'message' => 'Player not found'], 404);
                }

                if ($player->is_child) {
                    if (!$player->parent_id || $player->parent_id != $payerId) {
                        return response()->json(['success' => false, 'message' => 'Unauthorized. Only the parent can make payment for this child.'], 403);
                    }
                }

                $paymentRequestData = [
                    'payer_id' => $payerId,
                    'parent_id' => $payerId,
                    'player_id' => $playerId,
                    'in_app_purchase_id' => $inAppPurchase->id,
                    'amount_cents' => $inAppPurchase->amount_cents,
                    'currency' => $inAppPurchase->currency,
                    'status' => V4PaymentRequest::STATUS_PENDING,
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
                    'data' => [
                        'payment_request' => $paymentRequest,
                        'notification_sent' => (bool) $notification,
                    ],
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error processing payment Rollback: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);
                

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to process payment', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
            }
        } catch (ValidationException $e) {
            Log::error('Error processing payment validation: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to process payment', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    public function getOrdersByUserId(Request $request, ?int $userId): JsonResponse
    {
        try {
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required.',
                ], 400);
            }
            $request->merge(['user_id' => $userId]);

            $validated = $request->validate([
                'user_id' => 'required|exists:v4_users,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = max(1, min((int) ($validated['per_page'] ?? 20), 100));

            $query = EvaluationSubmission::with([
                'paymentRequest.inAppPurchase.marketplaceItem',
                'paymentRequest.paymentTransaction',
            ])
                ->where('player_id', $userId)
                ->orderByDesc('created_at');

            $orders = $query->paginate($perPage);

            // Handle case where no orders exist
            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No orders found',
                    'data' => [],
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'per_page' => $orders->perPage(),
                        'total' => 0,
                        'last_page' => 0,
                    ],
                ]);
            }

            // Transform data if needed
            $ordersData = $orders->map(function ($order) {
                $payload = $order->paymentRequest->paymentTransaction->payload;

                $payloadData = json_decode($payload, true);

                $payloadData = Arr::only($payloadData ?? [], [
                    'order_id',
                    'products',
                    'price',
                    'purchase_state',
                    'currency_code',
                    'raw_price',
                ]);

                return [
                    'id' => $order->id,
                    'type' => $order->paymentRequest->inAppPurchase->marketplaceItem->type,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'payload' => !empty($payloadData) ? $payloadData : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully.',
                'data' => $ordersData,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem() ?? 0,
                    'to' => $orders->lastItem() ?? 0,
                    'has_more_pages' => $orders->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (QueryException $e) {
            Log::error('Database query error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
            ], 500);
        } catch (Exception $e) {
            Log::error('Unexpected error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    protected function sendPaymentRequestNotification(V4PaymentRequest $paymentRequest, string $sku)
    {
        try {
            $child = $paymentRequest->player;
            $parent = $child->parent;

            $amount = $paymentRequest->amount_cents;
            $currency = $paymentRequest->currency;
            $purpose = "video_evaluation_payment_request";

            $title = "💰 Payment Request from " . $child->name;
            $message = 'There is a payment approval request from your child ' . $child->name;

            $data = [
                'payment_request' => $paymentRequest,
                'sku' => $sku,
                'child' => $child,
                'amount' => $amount,
                'currency' => $currency,
                'purpose' => $purpose,
                'status' => 'pending',
                'action_required' => true,
                'quick_actions' => ['pay', 'decline'],
                // 'parent' => $parent,
            ];
            $icon = 'payments';
            $color = '#2196F3'; // Blue for low urgency

            $notification = $this->notificationService->sendToUserWithImage(
                $parent,
                $title,
                $message,
                $child->profile_photo ?? '',
                $data,
                'payment_request_received',
                "/payment-requests/$paymentRequest->id", // Redirect to payment request details
                'payment_request_action',
                $paymentRequest
            );

            return $notification;
        } catch (Exception $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
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

    protected function handlePaymentSuccessNotifications(
        V4PaymentRequest $paymentRequest,
        V4InAppPurchase $inAppPurchase,
        V4User $player
    ): void {
        try {
            // 1️⃣ Delete old child-to-parent payment request notification
            if ($paymentRequest->notification) {
                $paymentRequest->notification->delete();
            }

            // 2️⃣ Determine who receives the success notification
            $payer = $player->parent ?? $player;

            $title = "✅ Payment Successful";
            $message = 'Your payment for ' . $inAppPurchase->title . ' has been successfully processed.';

            // 3️⃣ Send success notification
            $this->notificationService->sendToUserWithImage(
                $payer,
                $title,
                $message,
                $player->profile_photo ?? '',
                [
                    'sku' => $inAppPurchase->sku,
                    'child' => $player,
                    'payment_request_id' => $paymentRequest->id,
                    'amount' => $inAppPurchase->amount_cents,
                    'currency' => $inAppPurchase->currency,
                    'status' => 'paid',
                ],
                'payment_success',
                "/payments/{$paymentRequest->id}", // Redirect or deep link
                'payment_completed_action',
                $paymentRequest
            );
        } catch (Exception $e) {
            Log::error('Error sending payment success notification', [
                'payment_request_id' => $paymentRequest->id,
                'error' => $e->getMessage(),
            ]);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }
}
