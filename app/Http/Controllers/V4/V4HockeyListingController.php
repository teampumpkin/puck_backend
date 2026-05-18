<?php

namespace App\Http\Controllers\V4;

use App\Constants\HockeyListingCategories;
use App\Constants\HockeyListingConditions;
use App\Http\Controllers\Controller;
use App\Models\V4HockeyListing;
use App\Models\V4HockeyListingImage;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
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
    const LISTING_FEE_SKU = 'hockey_listing_fee';

    /**
     * Create a payment request tied to a specific draft listing.
     * Returns the SKU for the client to complete the Play Store / App Store purchase,
     * then call confirmPayment to publish the listing.
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

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

            // Idempotency: return existing in-flight payment request
            if ($listing->payment_request_id) {
                $existingPayment = V4PaymentRequest::find($listing->payment_request_id);
                if ($existingPayment && $existingPayment->status === V4PaymentRequest::STATUS_PAYMENT_INITIATED) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment already initiated. Complete purchase then call confirm-payment.',
                        'data' => [
                            'payment_request_id' => $existingPayment->id,
                            'sku' => $existingPayment->inAppPurchase->sku,
                            'amount_cents' => $existingPayment->amount_cents,
                            'currency' => $existingPayment->currency,
                            'formatted_amount' => $existingPayment->formatted_amount,
                        ],
                    ]);
                }
            }

            $inAppPurchase = V4InAppPurchase::where('sku', self::LISTING_FEE_SKU)
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
                $paymentRequest = V4PaymentRequest::create([
                    'payer_id' => $user->id,
                    'player_id' => $user->id,
                    'in_app_purchase_id' => $inAppPurchase->id,
                    'amount_cents' => $inAppPurchase->amount_cents,
                    'currency' => $inAppPurchase->currency,
                    'status' => V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                ]);

                $listing->payment_request_id = $paymentRequest->id;
                $listing->status = V4HockeyListing::STATUS_PAYMENT_REQUESTED;
                $listing->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated. Complete purchase then call confirm-payment.',
                    'data' => [
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

            $record = V4HockeyListing::where('id', $listing)
                ->where('user_id', $user->id)
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            if ($record->status === V4HockeyListing::STATUS_PUBLISHED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing is already published.',
                ], 400);
            }

            if ($record->status !== V4HockeyListing::STATUS_PAYMENT_REQUESTED || !$record->payment_request_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active payment request found. Call initiate-payment first.',
                ], 400);
            }

            $paymentRequest = V4PaymentRequest::find($record->payment_request_id);

            if (!$paymentRequest || $paymentRequest->status !== V4PaymentRequest::STATUS_PAYMENT_INITIATED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request is not in a confirmable state.',
                ], 400);
            }

            $validated = $request->validate([
                'purchase_id' => 'nullable|string',
                'source' => 'required|in:ios,android,web,window,linux,macos',
                'verification_data' => 'nullable|array',
                'store_status' => 'nullable|string',
                'transaction_date' => 'nullable|date',
                'payload' => 'nullable|array',
            ]);

            // Duplicate purchase prevention
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
     * Create a new listing. Requires a paid payment_request_id.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price_cents' => 'required|integer|min:0',
                'currency' => 'required|string|size:3',
                'description' => 'nullable|string',
                'category' => 'required|string|in:' . implode(',', HockeyListingCategories::all()),
                'condition' => 'required|string|in:' . implode(',', HockeyListingConditions::all()),
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'sell_radius' => 'nullable|integer|min:1',
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:10240',
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

                $listing->load('images');

                return response()->json([
                    'success' => true,
                    'message' => 'Listing saved as draft.',
                    'data' => $listing,
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
            $validated = $request->validate([
                'category' => 'nullable|string|in:' . implode(',', HockeyListingCategories::all()),
                'condition' => 'nullable|string|in:' . implode(',', HockeyListingConditions::all()),
                'country' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'min_price_cents' => 'nullable|integer|min:0',
                'max_price_cents' => 'nullable|integer|min:0',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = max(1, min((int) ($validated['per_page'] ?? 20), 100));

            $query = V4HockeyListing::active()
                ->with('images')
                ->orderByDesc('listed_at');

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

            if (isset($validated['min_price_cents'])) {
                $query->where('price_cents', '>=', $validated['min_price_cents']);
            }

            if (isset($validated['max_price_cents'])) {
                $query->where('price_cents', '<=', $validated['max_price_cents']);
            }

            $listings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $listings->items(),
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

    /**
     * Get a single listing by ID.
     */
    public function show(int $listing): JsonResponse
    {
        try {
            $record = V4HockeyListing::active()
                ->with('images')
                ->find($listing);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $record,
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
                'sell_radius' => 'sometimes|integer|min:1',
                'images' => 'nullable|array|max:10',
                'images.*.image_url' => 'required|url|max:500',
                'images.*.sort_order' => 'nullable|integer|min:0',
            ]);

            DB::beginTransaction();
            try {
                $record->fill(collect($validated)->except('images')->toArray());
                $record->save();

                if (array_key_exists('images', $validated)) {
                    $record->images()->delete();

                    if (!empty($validated['images'])) {
                        $images = array_map(function ($img, $index) use ($record) {
                            return [
                                'listing_id' => $record->id,
                                'image_url' => $img['image_url'],
                                'sort_order' => $img['sort_order'] ?? $index,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }, $validated['images'], array_keys($validated['images']));

                        V4HockeyListingImage::insert($images);
                    }
                }

                DB::commit();

                $record->load('images');

                return response()->json([
                    'success' => true,
                    'message' => 'Listing updated successfully.',
                    'data' => $record,
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

    /**
     * Get the authenticated user's own listings (all statuses).
     */
    public function myListings(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $validated = $request->validate([
                'status' => 'nullable|string|in:draft,payment_requested,payment_failed,payment_rejected,published',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = max(1, min((int) ($validated['per_page'] ?? 14), 100));

            $query = V4HockeyListing::withTrashed()
                ->where('user_id', $user->id)
                ->with('images')
                ->orderByDesc('created_at');

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            $listings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $listings->items(),
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
}
