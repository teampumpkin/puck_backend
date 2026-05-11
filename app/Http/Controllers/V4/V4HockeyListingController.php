<?php

namespace App\Http\Controllers\V4;

use App\Constants\HockeyListingCategories;
use App\Constants\HockeyListingConditions;
use App\Http\Controllers\Controller;
use App\Models\V4HockeyListing;
use App\Models\V4HockeyListingImage;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class V4HockeyListingController extends Controller
{
    const LISTING_FEE_SKU = 'hockey_listing_fee';

    /**
     * Create a payment request for the listing fee.
     * The client uses the returned payment_request_id to complete payment
     * via the existing V4PaymentController@processPayment flow.
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $inAppPurchase = V4InAppPurchase::where('sku', self::LISTING_FEE_SKU)
                ->where('active', true)
                ->first();

            if (!$inAppPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing fee product not found or inactive.',
                ], 404);
            }

            $paymentRequest = V4PaymentRequest::create([
                'payer_id' => $user->id,
                'player_id' => $user->id,
                'in_app_purchase_id' => $inAppPurchase->id,
                'amount_cents' => $inAppPurchase->amount_cents,
                'currency' => $inAppPurchase->currency,
                'status' => V4PaymentRequest::STATUS_PENDING,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment request created. Complete payment to activate your listing.',
                'data' => [
                    'payment_request_id' => $paymentRequest->id,
                    'sku' => $inAppPurchase->sku,
                    'amount_cents' => $inAppPurchase->amount_cents,
                    'currency' => $inAppPurchase->currency,
                    'formatted_amount' => $paymentRequest->formatted_amount,
                ],
            ], 201);
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
     * Create a new listing. Requires a paid payment_request_id.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $validated = $request->validate([
                'payment_request_id' => 'required|integer|exists:v4_payment_requests,id',
                'name' => 'required|string|max:255',
                'price_cents' => 'required|integer|min:0',
                'currency' => 'required|string|size:3',
                'description' => 'nullable|string',
                'category' => 'required|string|in:' . implode(',', HockeyListingCategories::all()),
                'condition' => 'required|string|in:' . implode(',', HockeyListingConditions::all()),
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'sell_radius' => 'required|integer|min:1',
                'images' => 'required|array|min:1|max:10',
                'images.*.image_url' => 'required|url|max:500',
                'images.*.sort_order' => 'nullable|integer|min:0',
            ]);

            // Verify the payment request belongs to this user and is paid
            $paymentRequest = V4PaymentRequest::where('id', $validated['payment_request_id'])
                ->where('payer_id', $user->id)
                ->where('status', V4PaymentRequest::STATUS_PAID)
                ->first();

            if (!$paymentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not completed. Please complete payment before creating a listing.',
                ], 402);
            }

            // Prevent reuse of the same payment_request_id for multiple listings
            $alreadyUsed = V4HockeyListing::where('payment_request_id', $validated['payment_request_id'])
                ->withTrashed()
                ->exists();

            if ($alreadyUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment has already been used for another listing.',
                ], 409);
            }

            DB::beginTransaction();
            try {
                $listing = V4HockeyListing::create([
                    'user_id' => $user->id,
                    'payment_request_id' => $validated['payment_request_id'],
                    'name' => $validated['name'],
                    'price_cents' => $validated['price_cents'],
                    'currency' => $validated['currency'],
                    'description' => $validated['description'] ?? null,
                    'category' => $validated['category'],
                    'condition' => $validated['condition'],
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'address' => $validated['address'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'country' => $validated['country'] ?? null,
                    'sell_radius' => $validated['sell_radius'],
                ]);

                $listing->markActive();

                if (!empty($validated['images'])) {
                    $images = array_map(function ($img, $index) use ($listing) {
                        return [
                            'listing_id' => $listing->id,
                            'image_url' => $img['image_url'],
                            'sort_order' => $img['sort_order'] ?? $index,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $validated['images'], array_keys($validated['images']));

                    V4HockeyListingImage::insert($images);
                }

                DB::commit();

                $listing->load('images');

                return response()->json([
                    'success' => true,
                    'message' => 'Listing created successfully.',
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
                ->where('status', V4HockeyListing::STATUS_ACTIVE)
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
                'status' => 'nullable|string|in:pending_payment,active,sold',
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
