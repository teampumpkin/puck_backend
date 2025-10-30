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


class V4InAppPurchaseController extends Controller
{
    // ✅ GET all (already exists)
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
            return response()->json(['success' => false, 'message' => 'Invalid query parameters.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Failed to fetch in-app purchases.', [
                'user_id' => $authUser->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching in-app purchases.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ✅ GET by ID
    public function getInAppPurchaseById($id): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $purchase = V4InAppPurchase::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'In-app purchase retrieved successfully.',
                'data' => $purchase,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'In-app purchase not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Failed to retrieve in-app purchase.', [
                'user_id' => $authUser->id,
                'purchase_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Error retrieving in-app purchase.'], 500);
        }
    }

    // ✅ UPDATE by ID
    public function updateInAppPurchaseById(Request $request, $id): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $validated = $request->validate([
                'sku' => 'sometimes|string|unique:v4_in_app_purchases,sku,' . $id,
                'title' => 'sometimes|string',
                'product_type' => 'sometimes|string|in:' . implode(',', V4InAppPurchase::PRODUCT_TYPES),
                'amount_cents' => 'sometimes|integer|min:0',
                'currency' => 'sometimes|string|size:3',
                'meta' => 'nullable|array',
                'active' => 'sometimes|boolean',
            ]);

            $purchase = V4InAppPurchase::findOrFail($id);

            $purchase->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'In-app purchase updated successfully.',
                'data' => $purchase,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'In-app purchase not found.'], 404);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Failed to update in-app purchase.', [
                'user_id' => $authUser->id,
                'purchase_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Error updating in-app purchase.'], 500);
        }
    }

    // ✅ DELETE by ID
    public function destroyInAppPurchaseById($id): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $purchase = V4InAppPurchase::findOrFail($id);
            $purchase->delete();

            return response()->json([
                'success' => true,
                'message' => 'In-app purchase deleted successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'In-app purchase not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Failed to delete in-app purchase.', [
                'user_id' => $authUser->id,
                'purchase_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Error deleting in-app purchase.'], 500);
        }
    }

    public function restoreInAppPurchaseById($id): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $purchase = V4InAppPurchase::onlyTrashed()->findOrFail($id);
            $purchase->restore();

            return response()->json([
                'success' => true,
                'message' => 'In-app purchase restored successfully.',
                'data' => $purchase,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'In-app purchase not found or not deleted.'], 404);
        } catch (Exception $e) {
            Log::error('Failed to restore in-app purchase.', [
                'user_id' => $authUser->id,
                'purchase_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Error restoring in-app purchase.'], 500);
        }
    }

    public function createInAppPurchase(Request $request): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            // ✅ Validation
            $validated = $request->validate([
                'sku' => 'required|string|unique:v4_in_app_purchases,sku',
                'title' => 'required|string|max:255',
                'product_type' => 'required|string|in:' . implode(',', V4InAppPurchase::PRODUCT_TYPES),
                'amount_cents' => 'required|integer|min:0',
                'currency' => 'required|string|size:3',
                'meta' => 'nullable|array',
                'active' => 'nullable|boolean',
            ]);

            // ✅ Create the in-app purchase
            $purchase = V4InAppPurchase::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'In-app purchase created successfully.',
                'data' => $purchase,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to create in-app purchase.', [
                'user_id' => $authUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating in-app purchase.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
