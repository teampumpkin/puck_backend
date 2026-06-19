<?php

namespace App\Http\Controllers\V4;

use App\Constants\HockeyListingCategories;
use App\Constants\HockeyListingConditions;
use App\DTOs\SellerInfoDTO;
use App\Http\Controllers\Controller;
use App\Models\V4HockeyListing;
use App\Models\V4HockeyListingImage;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class V4HockeyListingController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }


    /**
     * Create a payment request tied to a specific draft listing.
     * Returns the SKU for the client to complete the Play Store / App Store purchase,
     * then call confirmPayment to publish the listing.
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing initiate payment', ['user_id' => $user->id, 'payload' => $request->all()]);

            $validated = $request->validate([
                'listing_id' => 'required|integer',
            ]);

            $listing = V4HockeyListing::where('id', $validated['listing_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$listing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            if ($listing->status === V4HockeyListing::STATUS_PUBLISHED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing is already published.',
                ], 400);
            }

            $isChild = (bool) ($user->is_child ?? false);
            $parentId = $isChild ? $user->parent_id : null;

            if ($isChild && !$parentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child account is missing a parent. Cannot request payment.',
                ], 400);
            }

            // Idempotency: return existing in-flight payment request
            if ($listing->payment_request_id) {
                $existingPayment = V4PaymentRequest::with('inAppPurchase')->find($listing->payment_request_id);

                if ($existingPayment && $existingPayment->status === V4PaymentRequest::STATUS_PENDING && $isChild) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment request already sent to parent.',
                        'data' => [
                            'awaiting_parent' => true,
                            'payment_request_id' => $existingPayment->id,
                            'sku' => null,
                            'amount_cents' => $existingPayment->amount_cents,
                            'currency' => $existingPayment->currency,
                            'formatted_amount' => $existingPayment->formatted_amount,
                        ],
                    ]);
                }

                if ($existingPayment && $existingPayment->status === V4PaymentRequest::STATUS_PAYMENT_INITIATED) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment already initiated. Complete purchase then call confirm-payment.',
                        'data' => [
                            'awaiting_parent' => false,
                            'payment_request_id' => $existingPayment->id,
                            'sku' => optional($existingPayment->inAppPurchase)->sku,
                            'amount_cents' => $existingPayment->amount_cents,
                            'currency' => $existingPayment->currency,
                            'formatted_amount' => $existingPayment->formatted_amount,
                        ],
                    ]);
                }
            }

            $inAppPurchase = V4InAppPurchase::where('sku', config('services.hockey_listing.fee_sku'))
                ->where('active', true)
                ->first();

            if (!$inAppPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing fee product not found or inactive.',
                ], 404);
            }

            DB::beginTransaction();
            try {
                $paymentRequestData = [
                    'payer_id' => $isChild ? $parentId : $user->id,
                    'player_id' => $user->id,
                    'in_app_purchase_id' => $inAppPurchase->id,
                    'amount_cents' => $inAppPurchase->amount_cents,
                    'currency' => $inAppPurchase->currency,
                    'status' => $isChild
                        ? V4PaymentRequest::STATUS_PENDING
                        : V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                ];

                if ($isChild) {
                    $paymentRequestData['parent_id'] = $parentId;
                }

                $paymentRequest = V4PaymentRequest::create($paymentRequestData);

                $listing->payment_request_id = $paymentRequest->id;

                // A non-child pays directly, so the listing stays draft until confirmPayment publishes it.
                if ($isChild) {
                    $listing->status = V4HockeyListing::STATUS_PAYMENT_REQUESTED;
                }
                $listing->save();

                DB::commit();

                if ($isChild) {
                    $paymentRequest->load(['player', 'parent']);
                    $this->sendListingPaymentRequestNotification($paymentRequest, $listing, $inAppPurchase);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment request sent to parent.',
                        'data' => [
                            'awaiting_parent' => true,
                            'payment_request_id' => $paymentRequest->id,
                            'sku' => null,
                            'amount_cents' => $inAppPurchase->amount_cents,
                            'currency' => $inAppPurchase->currency,
                            'formatted_amount' => $paymentRequest->formatted_amount,
                        ],
                    ], 201);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated. Complete purchase then call confirm-payment.',
                    'data' => [
                        'awaiting_parent' => false,
                        'payment_request_id' => $paymentRequest->id,
                        'sku' => $inAppPurchase->sku,
                        'amount_cents' => $inAppPurchase->amount_cents,
                        'currency' => $inAppPurchase->currency,
                        'formatted_amount' => $paymentRequest->formatted_amount,
                    ],
                ], 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to initiate hockey listing payment', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Confirm a completed Play Store / App Store purchase and publish the listing.
     * Must be called after initiatePayment + completing the IAP on the device.
     */
    public function confirmPayment(Request $request, int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing confirm payment', ['user_id' => $user->id, 'listing_id' => $listing, 'payload' => $request->all()]);

            $record = V4HockeyListing::find($listing);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            $paymentRequest = $record->payment_request_id
                ? V4PaymentRequest::find($record->payment_request_id)
                : null;

            $authId = (int) $user->id;
            $isOwner = (int) $record->user_id === $authId;
            $isParentPayer = $paymentRequest
                && $paymentRequest->parent_id
                && (int) $paymentRequest->parent_id === $authId;

            if (!$isOwner && !$isParentPayer) {
                Log::warning('Hockey listing confirm unauthorized', [
                    'auth_user_id' => $authId,
                    'listing_user_id' => $record->user_id,
                    'pr_parent_id' => $paymentRequest?->parent_id,
                    'pr_id' => $paymentRequest?->id,
                    'pr_status' => $paymentRequest?->status,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }

            $validated = $request->validate([
                'purchase_id' => 'nullable|string',
                'source' => 'required|in:ios,android,web,window,linux,macos',
                'verification_data' => 'nullable|array',
                'store_status' => 'nullable|string',
                'transaction_date' => 'nullable|date',
                'payload' => 'nullable|array',
            ]);

            // Duplicate purchase prevention. Checked before status gates so that
            // StoreKit replays of an already-finalized transaction return an
            // idempotent signal instead of being blocked by "already published".
            if (!empty($validated['purchase_id'])) {
                $duplicate = V4PaymentTransaction::where('purchase_id', $validated['purchase_id'])
                    ->where('source', $validated['source'])
                    ->first();

                if ($duplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This purchase has already been processed.',
                        'payment_transaction_id' => $duplicate->id,
                    ], 400);
                }
            }

            if ($record->status === V4HockeyListing::STATUS_PUBLISHED) {
                $existing = $record->payment_request_id
                    ? V4PaymentTransaction::where('payment_request_id', $record->payment_request_id)
                        ->where('status', V4PaymentTransaction::STATUS_SUCCESS)
                        ->latest('id')
                        ->first()
                    : null;

                return response()->json([
                    'success' => false,
                    'message' => 'Listing is already published.',
                    'payment_transaction_id' => $existing?->id,
                ], 400);
            }

            // Child listings sit in payment_requested (awaiting parent); non-child listings
            // stay in draft after initiate-payment. Both are confirmable while a request is attached.
            $confirmableStatuses = [
                V4HockeyListing::STATUS_PAYMENT_REQUESTED,
                V4HockeyListing::STATUS_DRAFT,
            ];

            if (!in_array($record->status, $confirmableStatuses, true) || !$record->payment_request_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active payment request found. Call initiate-payment first.',
                ], 400);
            }

            if (!$paymentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request not found.',
                ], 400);
            }

            $allowedStatuses = [
                V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                V4PaymentRequest::STATUS_PENDING,
            ];

            if (!in_array($paymentRequest->status, $allowedStatuses, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request is not in a confirmable state.',
                ], 400);
            }

            if ($paymentRequest->status === V4PaymentRequest::STATUS_PENDING && !$isParentPayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the parent can confirm this payment.',
                ], 403);
            }

            DB::beginTransaction();
            try {
                $transaction = V4PaymentTransaction::create([
                    'payment_request_id' => $paymentRequest->id,
                    'payer_id' => $user->id,
                    'amount_cents' => $paymentRequest->amount_cents,
                    'currency' => $paymentRequest->currency,
                    'gateway' => 'internal',
                    'gateway_reference' => 'internal_' . uniqid() . '_' . time(),
                    'status' => V4PaymentTransaction::STATUS_SUCCESS,
                    'purchase_id' => $validated['purchase_id'] ?? null,
                    'source' => $validated['source'],
                    'verification_data' => $validated['verification_data'] ?? null,
                    'store_status' => $validated['store_status'] ?? null,
                    'transaction_date' => $validated['transaction_date'] ?? null,
                    'payload' => $validated['payload'] ?? null,
                ]);

                $paymentRequest->markPaid();
                $record->markPublished();

                DB::commit();

                // Remove the original payment-request notification from the parent
                // so it stops showing as pending once payment is confirmed.
                $paymentRequest->loadMissing('notification');
                if ($paymentRequest->notification) {
                    $paymentRequest->notification->delete();
                }

                if ($isParentPayer) {
                    $this->sendListingPaymentApprovedNotification($paymentRequest, $record);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment confirmed. Your listing is now live.',
                    'data' => [
                        'listing_id' => $record->id,
                        'listing_status' => $record->status,
                        'listed_at' => $record->listed_at,
                        'payment_request_id' => $paymentRequest->id,
                        'payment_transaction_id' => $transaction->id,
                    ],
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to confirm hockey listing payment', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get the current payment status for a listing.
     * Accessible to the listing owner (child) or the parent payer.
     */
    public function paymentStatus(Request $request, int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing payment status', ['user_id' => $user->id, 'listing_id' => $listing]);

            $record = V4HockeyListing::with('paymentRequest.inAppPurchase')->find($listing);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            $paymentRequest = $record->paymentRequest;

            $authId = (int) $user->id;
            $isOwner = (int) $record->user_id === $authId;
            $isParentPayer = $paymentRequest
                && $paymentRequest->parent_id
                && (int) $paymentRequest->parent_id === $authId;

            if (!$isOwner && !$isParentPayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Listing payment status loaded.',
                'data' => [
                    'listing_id' => $record->id,
                    'listing_status' => $record->status,
                    'is_published' => $record->status === V4HockeyListing::STATUS_PUBLISHED,
                    'awaiting_parent' => $paymentRequest
                        && $paymentRequest->status === V4PaymentRequest::STATUS_PENDING,
                    'payment_request_id' => $paymentRequest?->id,
                    'payment_status' => $paymentRequest?->status,
                    'sku' => optional($paymentRequest?->inAppPurchase)->sku,
                    'amount_cents' => $paymentRequest?->amount_cents,
                    'currency' => $paymentRequest?->currency,
                    'formatted_amount' => $paymentRequest?->formatted_amount,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load hockey listing payment status', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment status.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject (decline) a pending parent-approval payment request for a listing.
     * Only the parent payer can decline. Marks the listing payment_rejected.
     */
    public function rejectPayment(Request $request, int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing reject payment', ['user_id' => $user->id, 'listing_id' => $listing, 'payload' => $request->all()]);

            $validated = $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $record = V4HockeyListing::find($listing);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            $paymentRequest = $record->payment_request_id
                ? V4PaymentRequest::find($record->payment_request_id)
                : null;

            if (!$paymentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment request for this listing.',
                ], 404);
            }

            $isParentPayer = $paymentRequest->parent_id
                && (int) $paymentRequest->parent_id === (int) $user->id;

            if (!$isParentPayer) {
                Log::warning('Hockey listing reject unauthorized', [
                    'auth_user_id' => (int) $user->id,
                    'pr_parent_id' => $paymentRequest->parent_id,
                    'pr_id' => $paymentRequest->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Only the parent can reject this payment request.',
                ], 403);
            }

            if ($record->status === V4HockeyListing::STATUS_PUBLISHED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing is already published.',
                ], 400);
            }

            if ($paymentRequest->status !== V4PaymentRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request is not in a rejectable state.',
                ], 400);
            }

            DB::beginTransaction();
            try {
                $paymentRequest->markParentRejected($validated['reason'] ?? null);
                $record->markPaymentRejected();

                DB::commit();

                // Remove the original payment-request notification from the parent
                // so it stops showing as pending once the request is rejected.
                $paymentRequest->loadMissing('notification');
                if ($paymentRequest->notification) {
                    $paymentRequest->notification->delete();
                }

                $paymentRequest->load(['player', 'parent']);
                $this->sendListingPaymentRejectedNotification($paymentRequest, $record);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment request rejected.',
                    'data' => [
                        'listing_id' => $record->id,
                        'listing_status' => $record->status,
                        'payment_request_id' => $paymentRequest->id,
                        'payment_status' => $paymentRequest->status,
                    ],
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to reject hockey listing payment', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject payment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Create a new listing. Requires a paid payment_request_id.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing store', ['user_id' => $user->id, 'payload' => $request->except('images')]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price_cents' => 'required|integer|min:0',
                'currency' => 'required|string|size:3',
                'description' => 'nullable|string',
                'category' => 'required|string|in:' . implode(',', HockeyListingCategories::all()),
                'condition' => 'required|string|in:' . implode(',', HockeyListingConditions::all()),
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address' => 'nullable|string|max:500',
                'city' => 'required|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'required|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'sell_radius' => 'nullable|integer|min:1',
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'required|file|image|mimes:jpeg,png,jpg,webp,heic,heif|max:3072',
                'sort_orders' => 'nullable|array',
                'sort_orders.*' => 'nullable|integer|min:0',
            ]);

            DB::beginTransaction();
            try {
                $listing = V4HockeyListing::create([
                    'user_id' => $user->id,
                    'name' => $validated['name'],
                    'price_cents' => $validated['price_cents'],
                    'currency' => $validated['currency'],
                    'description' => $validated['description'] ?? null,
                    'category' => $validated['category'],
                    'condition' => $validated['condition'],
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'country' => $validated['country'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'sell_radius' => $validated['sell_radius'] ?? null,
                ]);

                $imageFiles = $request->file('images');
                $sortOrders = $validated['sort_orders'] ?? [];
                $images = [];

                foreach ($imageFiles as $index => $file) {
                    $path = $file->store('hockey-listings/' . $listing->id, 's3');
                    $images[] = [
                        'listing_id' => $listing->id,
                        'image_url' => Storage::disk('s3')->url($path),
                        'sort_order' => $sortOrders[$index] ?? $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                V4HockeyListingImage::insert($images);

                DB::commit();

                $listing->load(['images', 'user:' . SellerInfoDTO::selectColumns()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Listing saved as draft.',
                    'data' => $this->formatListing($listing),
                ], 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            Log::error('Hockey listing store validation failed', ['user_id' => Auth::id(), 'errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to create hockey listing', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create listing.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List active listings with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Hockey listing index', ['filters' => $request->all()]);

            $validated = $request->validate([
                'category' => 'nullable|string|in:' . implode(',', HockeyListingCategories::all()),
                'condition' => 'nullable|string|in:' . implode(',', HockeyListingConditions::all()),
                'country' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'min_price_cents' => 'nullable|integer|min:0',
                'max_price_cents' => 'nullable|integer|min:0',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = max(1, min((int) ($validated['per_page'] ?? 20), 100));

            $query = V4HockeyListing::active()
                ->with(['images', 'user:' . SellerInfoDTO::selectColumns()])
                ->orderByDesc('listed_at')
                ->orderByDesc('created_at');

            if (!empty($validated['category'])) {
                $query->where('category', $validated['category']);
            }

            if (!empty($validated['condition'])) {
                $query->where('condition', $validated['condition']);
            }

            if (!empty($validated['country'])) {
                $query->where('country', $validated['country']);
            }

            if (!empty($validated['city'])) {
                $query->where('city', $validated['city']);
            }

            if (!empty($validated['postal_code'])) {
                $query->where('postal_code', $validated['postal_code']);
            }

            if (isset($validated['min_price_cents'])) {
                $query->where('price_cents', '>=', $validated['min_price_cents']);
            }

            if (isset($validated['max_price_cents'])) {
                $query->where('price_cents', '<=', $validated['max_price_cents']);
            }

            $listings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($l) => $this->formatListing($l), $listings->items()),
                'pagination' => [
                    'current_page' => $listings->currentPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                    'last_page' => $listings->lastPage(),
                    'has_more_pages' => $listings->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to fetch hockey listings', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch listings.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function nearby(Request $request): JsonResponse
    {
        try {
            Log::info('Hockey listing nearby', ['filters' => $request->all()]);

            $validated = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'search' => 'nullable|string|max:255',
                'categories' => 'nullable|array',
                'categories.*' => 'required|string|in:' . implode(',', HockeyListingCategories::all()),
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            $lat = $validated['latitude'];
            $lng = $validated['longitude'];
            $perPage = max(1, min((int) ($validated['per_page'] ?? 12), 50));

            $user = Auth::guard('v4api')->user();

            // Bounding box pre-filter using indexes (500 miles max covers all realistic sell_radius values)
            $maxMiles = 500;
            $latDelta = $maxMiles / 69.0;
            $lngDelta = $maxMiles / (69.0 * cos(deg2rad($lat)));

            $haversine = '(3958.8 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

            $query = V4HockeyListing::active()
                ->with(['images', 'user:' . SellerInfoDTO::selectColumns()])
                ->when($user, fn($q) => $q->where('user_id', '!=', $user->id))
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereNotNull('sell_radius')
                ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
                ->whereRaw("$haversine <= sell_radius", [$lat, $lng, $lat])
                ->selectRaw("*, $haversine AS distance_miles", [$lat, $lng, $lat])
                ->orderBy('distance_miles')
                ->orderByDesc('listed_at');

            if (!empty($validated['search'])) {
                $search = '%' . $validated['search'] . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            }

            if (!empty($validated['categories'])) {
                $query->whereIn('category', $validated['categories']);
            }

            $listings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($l) => $this->formatListing($l), $listings->items()),
                'pagination' => [
                    'current_page' => $listings->currentPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                    'last_page' => $listings->lastPage(),
                    'has_more_pages' => $listings->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to fetch nearby hockey listings', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nearby listings.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get a single listing by ID.
     */
    public function show(int $listing): JsonResponse
    {
        try {
            Log::info('Hockey listing show', ['listing_id' => $listing]);

            $record = V4HockeyListing::query()
                ->with(['images', 'user:' . SellerInfoDTO::selectColumns()])
                ->find($listing);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatListing($record),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch listing.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update own listing (name, price, description, images).
     */
    public function update(Request $request, int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing update', ['user_id' => $user->id, 'listing_id' => $listing, 'payload' => $request->except(['add_images'])]);

            $record = V4HockeyListing::where('id', $listing)
                ->where('user_id', $user->id)
                ->where('status', V4HockeyListing::STATUS_PUBLISHED)
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found or you do not have permission to edit it.',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'price_cents' => 'sometimes|integer|min:0',
                'currency' => 'sometimes|string|size:3',
                'description' => 'nullable|string',
                'category' => 'sometimes|string|in:' . implode(',', HockeyListingCategories::all()),
                'condition' => 'sometimes|string|in:' . implode(',', HockeyListingConditions::all()),
                'latitude' => 'sometimes|numeric|between:-90,90',
                'longitude' => 'sometimes|numeric|between:-180,180',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'sell_radius' => 'sometimes|integer|min:1',
                'remove_images' => 'nullable|array',
                'remove_images.*' => 'required|url|max:500',
                'add_images' => 'nullable|array|max:10',
                'add_images.*' => 'required|file|image|mimes:jpeg,png,jpg,webp,heic,heif|max:3072',
            ]);

            DB::beginTransaction();
            try {
                $record->fill(collect($validated)->except(['remove_images', 'add_images'])->toArray());
                $record->save();

                if (!empty($validated['remove_images'])) {
                    $record->images()->whereIn('image_url', $validated['remove_images'])->delete();
                }

                if ($request->hasFile('add_images')) {
                    $existing = $record->images()->count();
                    $images = [];
                    foreach ($request->file('add_images') as $index => $file) {
                        $path = $file->store('hockey-listings/' . $record->id, 's3');
                        $images[] = [
                            'listing_id' => $record->id,
                            'image_url' => Storage::disk('s3')->url($path),
                            'sort_order' => $existing + $index,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    V4HockeyListingImage::insert($images);
                }

                DB::commit();

                $record->load(['images', 'user:' . SellerInfoDTO::selectColumns()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Listing updated successfully.',
                    'data' => $this->formatListing($record),
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to update hockey listing', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Soft-delete own listing.
     */
    public function destroy(int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing destroy', ['user_id' => $user->id, 'listing_id' => $listing]);

            $record = V4HockeyListing::where('id', $listing)
                ->where('user_id', $user->id)
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found or you do not have permission to delete it.',
                ], 404);
            }

            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Listing removed successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete hockey listing', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove listing.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function markAvailable(int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $record = V4HockeyListing::where('id', $listing)
                ->where('user_id', $user->id)
                ->where('status', V4HockeyListing::STATUS_SOLD)
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found or is not marked as sold.',
                ], 404);
            }

            $record->markAvailable();

            return response()->json([
                'success' => true,
                'message' => 'Listing marked as available.',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to mark hockey listing as available', [
                'user_id' => $user->id,
                'listing_id' => $listing,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark listing as available.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function markSold(int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing mark sold', ['user_id' => $user->id, 'listing_id' => $listing]);

            $record = V4HockeyListing::where('id', $listing)
                ->where('user_id', $user->id)
                ->where('status', V4HockeyListing::STATUS_PUBLISHED)
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found or cannot be marked as sold.',
                ], 404);
            }

            $record->markSold();

            return response()->json([
                'success' => true,
                'message' => 'Listing marked as sold.',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to mark hockey listing as sold', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark listing as sold.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Parent fetches the pending payment request for a child's listing,
     * so the parent device can run the IAP flow and call confirm-payment.
     */
    public function parentListingPayment(Request $request, int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing parent payment fetch', [
                'user_id' => $user->id,
                'listing_id' => $listing,
            ]);

            $record = V4HockeyListing::with(['images', 'user:' . SellerInfoDTO::selectColumns(), 'paymentRequest.inAppPurchase'])
                ->find($listing);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            $paymentRequest = $record->paymentRequest;

            if (!$paymentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment request for this listing.',
                ], 404);
            }

            if (!$paymentRequest->parent_id || (int) $paymentRequest->parent_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the parent can access this payment request.',
                ], 403);
            }

            $inAppPurchase = $paymentRequest->inAppPurchase;

            if (!$inAppPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing fee product not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Listing payment request loaded.',
                'data' => [
                    'listing' => $this->formatListing($record),
                    'payment_request_id' => $paymentRequest->id,
                    'payment_status' => $paymentRequest->status,
                    'sku' => $inAppPurchase->sku,
                    'amount_cents' => $paymentRequest->amount_cents,
                    'currency' => $paymentRequest->currency,
                    'formatted_amount' => $paymentRequest->formatted_amount,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load parent listing payment', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment request.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Notify parent that a child has requested approval to pay a listing fee.
     */
    protected function sendListingPaymentRequestNotification(
        V4PaymentRequest $paymentRequest,
        V4HockeyListing $listing,
        V4InAppPurchase $inAppPurchase
    ): void {
        try {
            $child = $paymentRequest->player;
            $parent = $paymentRequest->parent;

            if (!$child || !$parent) {
                return;
            }

            $title = '💰 Listing Fee Approval from ' . $child->name;
            $message = $child->name . ' wants to publish "' . $listing->name . '". Approve the listing fee?';
            $redirectUrl = "/marketplace/parent-listing-payment/{$listing->id}";

            $data = [
                'payment_request_id' => $paymentRequest->id,
                'listing_id' => $listing->id,
                'listing_name' => $listing->name,
                'sku' => $inAppPurchase->sku,
                'child_id' => $child->id,
                'child_name' => $child->name,
                'amount_cents' => $paymentRequest->amount_cents,
                'currency' => $paymentRequest->currency,
                'status' => 'pending',
                'action_required' => true,
                'quick_actions' => ['pay', 'decline'],
                'redirect_url' => $redirectUrl,
            ];

            $this->notificationService->sendToUserWithImage(
                $parent,
                $title,
                $message,
                $child->profile_photo ?? '',
                $data,
                'hockey_listing_payment_request',
                $redirectUrl,
                'payment_request_action',
                $paymentRequest
            );
        } catch (Exception $e) {
            Log::error('errorSendListingPaymentRequestNotification: ' . $e->getMessage(), [
                'payment_request_id' => $paymentRequest->id,
                'listing_id' => $listing->id,
            ]);
        }
    }

    /**
     * Notify the child that the parent paid the listing fee and listing is live.
     */
    protected function sendListingPaymentApprovedNotification(
        V4PaymentRequest $paymentRequest,
        V4HockeyListing $listing
    ): void {
        try {
            $paymentRequest->loadMissing(['player']);

            $child = $paymentRequest->player;
            if (!$child) {
                return;
            }

            $title = '✅ Listing Published';
            $message = 'Your parent paid the fee. "' . $listing->name . '" is now live in the marketplace.';

            $this->notificationService->sendToUserWithImage(
                $child,
                $title,
                $message,
                $child->profile_photo ?? '',
                [
                    'payment_request_id' => $paymentRequest->id,
                    'listing_id' => $listing->id,
                    'listing_name' => $listing->name,
                    'status' => 'paid',
                ],
                'hockey_listing_payment_approved',
                "/marketplace/product-detail",
                'listing_published_action',
                $paymentRequest
            );
        } catch (Exception $e) {
            Log::error('errorSendListingPaymentApprovedNotification: ' . $e->getMessage(), [
                'payment_request_id' => $paymentRequest->id,
                'listing_id' => $listing->id,
            ]);
        }
    }

    /**
     * Notify the child that the parent declined the listing fee payment.
     */
    protected function sendListingPaymentRejectedNotification(
        V4PaymentRequest $paymentRequest,
        V4HockeyListing $listing
    ): void {
        try {
            $paymentRequest->loadMissing(['player']);

            $child = $paymentRequest->player;
            if (!$child) {
                return;
            }

            $title = '❌ Listing Fee Declined';
            $message = 'Your parent declined the listing fee for "' . $listing->name . '".';

            $this->notificationService->sendToUserWithImage(
                $child,
                $title,
                $message,
                $child->profile_photo ?? '',
                [
                    'payment_request_id' => $paymentRequest->id,
                    'listing_id' => $listing->id,
                    'listing_name' => $listing->name,
                    'status' => 'parent_rejected',
                ],
                'hockey_listing_payment_rejected',
                "/marketplace/product-detail",
                'listing_payment_rejected_action',
                $paymentRequest
            );
        } catch (Exception $e) {
            Log::error('errorSendListingPaymentRejectedNotification: ' . $e->getMessage(), [
                'payment_request_id' => $paymentRequest->id,
                'listing_id' => $listing->id,
            ]);
        }
    }

    /**
     * Get the authenticated user's own listings (all statuses).
     */
    public function myListings(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing my listings', ['user_id' => $user->id, 'filters' => $request->all()]);

            $validated = $request->validate([
                'status' => 'nullable|string|in:draft,payment_requested,payment_failed,payment_rejected,published,sold',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = max(1, min((int) ($validated['per_page'] ?? 10), 50));

            $query = V4HockeyListing::where('user_id', $user->id)
                ->with(['images', 'user:' . SellerInfoDTO::selectColumns()])
                ->orderByDesc('created_at');

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            $listings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($l) => $this->formatListing($l), $listings->items()),
                'pagination' => [
                    'current_page' => $listings->currentPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                    'last_page' => $listings->lastPage(),
                    'has_more_pages' => $listings->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to fetch my hockey listings', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch listings.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    private function formatListing(V4HockeyListing $listing): array
    {
        if ($listing->relationLoaded('user') && $listing->user) {
            // Strip computed $appends before toArray() — they fire DB queries per model
            // and are unused since the user is immediately replaced by the DTO below.
            $listing->user->setAppends([]);
        }

        $data = $listing->toArray();

        if ($listing->relationLoaded('user') && $listing->user) {
            $data['user'] = SellerInfoDTO::fromUser($listing->user)->toArray();
        }

        return $data;
    }

}
