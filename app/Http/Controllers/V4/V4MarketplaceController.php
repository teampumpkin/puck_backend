<?php

namespace App\Http\Controllers\V4;


use App\Http\Controllers\Controller;
use App\Models\V4Marketplace;
use App\Models\V4InAppPurchase;
use App\Constants\MarketplaceTypes;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class V4MarketplaceController extends Controller
{

    /**
     * Get all marketplace items (with pagination + filters)
     */
    public function getMarketPlaces(Request $request): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {

            // ✅ Validate query parameters
            $validated = $request->validate([
                'active' => 'nullable|boolean',
                'with_trashed' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $query = V4Marketplace::with('inAppPurchase');

            // Optional filters
            if (!empty($validated['with_trashed'])) {
                $query->withTrashed();
            }

            if ($request->boolean('with_trashed')) {
                $query->withTrashed();
            }

            // Handle pagination parameters safely
            $perPage = $validated['per_page'] ?? 15;
            $page = $validated['page'] ?? 1;

            $marketplaces = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'Marketplaces retrieved successfully.',
                'data' => $marketplaces->items(),
                'pagination' => [
                    'current_page' => $marketplaces->currentPage(),
                    'per_page' => $marketplaces->perPage(),
                    'total' => $marketplaces->total(),
                    'last_page' => $marketplaces->lastPage(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid query parameters.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to fetch marketplaces.', [
                'user_id' => $authUser->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching marketplaces.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new marketplace item.
     */
    public function storeMarketplace(Request $request): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            DB::beginTransaction();

            // ✅ Validate request
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'price_cents' => 'required|integer|min:0',
                'price_breakdown' => 'nullable|array',
                'price_breakdown.*.label' => 'required_with:price_breakdown|string|max:255',
                'price_breakdown.*.amount_cents' => 'required_with:price_breakdown|integer|min:0',
                'in_app_purchase_id' => 'required|exists:v4_in_app_purchases,id',
                // ✅ header_url can be file OR string
                'header_url' => [
                    'nullable',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->hasFile($attribute)) {
                            $file = $request->file($attribute);
                            $ext = strtolower($file->getClientOriginalExtension());
                            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                $fail('The ' . $attribute . ' must be a file of type: jpg, jpeg, png.');
                            }
                            if ($file->getSize() > 5 * 1024 * 1024) {
                                $fail('The ' . $attribute . ' may not be greater than 5MB.');
                            }
                            return;
                        }
                        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail('The ' . $attribute . ' must be a valid URL.');
                        }
                    },
                ],
                // ✅ icon can be file OR string
                'icon' => [
                    'nullable',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->hasFile($attribute)) {
                            $file = $request->file($attribute);
                            $ext = strtolower($file->getClientOriginalExtension());
                            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                $fail('The ' . $attribute . ' must be a file of type: jpg, jpeg, png.');
                            }
                            if ($file->getSize() > 5 * 1024 * 1024) {
                                $fail('The ' . $attribute . ' may not be greater than 5MB.');
                            }
                            return;
                        }

                        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail('The ' . $attribute . ' must be a valid URL.');
                        }
                    },
                ],
                'type' => 'required|string|in:' . implode(',', MarketplaceTypes::all()),
                'active' => 'nullable|boolean',
                'currency' => ['nullable', 'string', 'size:3'],
            ]);

            $validated['currency'] = $validated['currency'] ?? 'CDN';

            if ($request->hasFile('header_url')) {
                $filePath = $request->file('header_url')->store('marketplace/headers', 's3');
                $validated['header_url'] = Storage::disk('s3')->url($filePath);
            }

            if ($request->hasFile('icon')) {
                $filePath = $request->file('icon')->store('marketplace/icons', 's3');
                $validated['icon'] = Storage::disk('s3')->url($filePath);
            }

            $marketplace = V4Marketplace::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marketplace item created successfully.',
                'data' => $marketplace->load('inAppPurchase')
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Marketplace creation failed.', [
                'user_id' => $authUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating marketplace item.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show a specific marketplace item.
     */
    public function getMarketPlaceById(Request $request, $v4MarketplaceId): JsonResponse
    {

        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        try {

            // ✅ Retrieve marketplace with relation
            $marketplace = V4Marketplace::with('inAppPurchase')
                ->findOrFail($v4MarketplaceId);

            return response()->json([
                'success' => true,
                'message' => 'Marketplace item retrieved successfully.',
                'data' => $marketplace,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid marketplace ID.',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            Log::warning('Post not found or access denied.', [
                'user_id' => $authUser->id,
                'marketplace_id' => $v4MarketplaceId,
                'error' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Marketplace item not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Error retrieving marketplace item.', [
                'user_id' => $authUser->id,
                'marketplace_id' => $v4MarketplaceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while retrieving the marketplace item.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    /**
     * Update a marketplace item.
     */
    public function updateMarketplaceById(Request $request, int $v4MarketplaceId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            DB::beginTransaction();

            // Find marketplace item
            $marketplace = V4Marketplace::findOrFail($v4MarketplaceId);

            // Define type options
            $rules = [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:5000',
                'price_cents' => 'sometimes|integer|min:0',
                'price_breakdown' => 'nullable|array',
                'price_breakdown.*.label' => 'required_with:price_breakdown|string|max:255',
                'price_breakdown.*.amount_cents' => 'required_with:price_breakdown|integer|min:0',
                'in_app_purchase_id' => 'sometimes|exists:v4_in_app_purchases,id',

                // ✅ header_url can be file OR string (URL)
                'header_url' => [
                    'nullable',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->hasFile($attribute)) {
                            $file = $request->file($attribute);
                            $ext = strtolower($file->getClientOriginalExtension());
                            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                $fail('The ' . $attribute . ' must be a file of type: jpg, jpeg, png.');
                            }
                            if ($file->getSize() > 5 * 1024 * 1024) { // 5MB
                                $fail('The ' . $attribute . ' may not be greater than 5MB.');
                            }
                            return;
                        }
                        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail('The ' . $attribute . ' must be a valid URL.');
                        }
                    },
                ],

                // ✅ icon can be file OR string (URL)
                'icon' => [
                    'nullable',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->hasFile($attribute)) {
                            $file = $request->file($attribute);
                            $ext = strtolower($file->getClientOriginalExtension());
                            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                $fail('The ' . $attribute . ' must be a file of type: jpg, jpeg, png.');
                            }
                            if ($file->getSize() > 5 * 1024 * 1024) {
                                $fail('The ' . $attribute . ' may not be greater than 5MB.');
                            }
                            return;
                        }
                        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail('The ' . $attribute . ' must be a valid URL.');
                        }
                    },
                ],
                'currency' => 'sometimes|string|size:3',
                'type' => 'required|string|in:' . implode(',', MarketplaceTypes::all()),
                'active' => 'nullable|boolean',
            ];

            $validated = $request->validate($rules);

            // ✅ Handle header_url upload
            if ($request->hasFile('header_url')) {
                $filePath = $request->file('header_url')->store('marketplace/headers', 's3');
                $validated['header_url'] = Storage::disk('s3')->url($filePath);
            }

            // ✅ Handle icon upload
            if ($request->hasFile('icon')) {
                $filePath = $request->file('icon')->store('marketplace/icons', 's3');
                $validated['icon'] = Storage::disk('s3')->url($filePath);
            }

            // Merge with existing fields to avoid overwriting missing fields
            $updateData = array_merge($marketplace->only([
                'title',
                'description',
                'price_cents',
                'price_breakdown',
                'in_app_purchase_id',
                'header_url',
                'icon',
                'currency',
                'type',
                'active'
            ]), $validated);

            $marketplace->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marketplace item updated successfully.',
                'data' => $marketplace->fresh()->load('inAppPurchase'),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Marketplace item not found.',
            ], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Marketplace update failed.', [
                'user_id' => $authUser->id,
                'marketplace_id' => $v4MarketplaceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the marketplace item.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    /**
     * Soft delete a marketplace item.
     */
    public function destroyMarketplaceById($v4MarketplaceId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            DB::beginTransaction();

            $v4Marketplace = V4Marketplace::findOrFail($v4MarketplaceId);
            $v4Marketplace->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marketplace item soft deleted successfully.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::warning('Post not found during deletion attempt.', [
                'marketplace_id' => $v4MarketplaceId,
                'user_id' => $authUser->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Marketplace item not found.',
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Marketplace deletion failed.', [
                'user_id' => $authUser->id,
                'marketplace_id' => $v4MarketplaceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while deleting the marketplace item.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getInAppPurchases(Request $request): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {

            // ✅ Validate query parameters
            $validated = $request->validate([
                'active' => 'nullable|boolean',
                'with_trashed' => 'nullable|boolean',
                'only_trashed' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $query = V4InAppPurchase::query();

            // Optional filters
            if ($request->boolean('only_trashed')) {
                $query->onlyTrashed();
            } elseif ($request->boolean('with_trashed')) {
                $query->withTrashed();
            }

            if (isset($validated['active'])) {
                $query->where('is_active', $validated['active']);
            }

            // Handle pagination parameters safely
            $perPage = $validated['per_page'] ?? 15;
            $page = $validated['page'] ?? 1;

            // ✅ Fetch paginated results
            $purchases = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'In-app purchases retrieved successfully.',
                'data' => $purchases->items(),
                'pagination' => [
                    'current_page' => $purchases->currentPage(),
                    'per_page' => $purchases->perPage(),
                    'total' => $purchases->total(),
                    'last_page' => $purchases->lastPage(),
                    'from' => $purchases->firstItem(),
                    'to' => $purchases->lastItem(),
                    'has_more_pages' => $purchases->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid query parameters.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to fetch in-app purchases.', [
                'user_id' => $authUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching in-app purchases.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
