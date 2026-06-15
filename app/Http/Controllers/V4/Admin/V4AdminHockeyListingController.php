<?php

namespace App\Http\Controllers\V4\Admin;

use App\Constants\HockeyListingCategories;
use App\Http\Controllers\V4\V4HockeyListingController;
use App\Models\V4HockeyListing;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class V4AdminHockeyListingController extends V4HockeyListingController
{
    public function stats(): JsonResponse
    {
        try {
            $published = V4HockeyListing::STATUS_PUBLISHED;
            $sold      = V4HockeyListing::STATUS_SOLD;

            $row = V4HockeyListing::selectRaw("
                    SUM(CASE WHEN status IN ('$published', '$sold') THEN 1 ELSE 0 END) as total_listings,
                    SUM(CASE WHEN status = '$published' THEN 1 ELSE 0 END) as active_listings,
                    SUM(CASE WHEN status = '$sold'      THEN 1 ELSE 0 END) as sold_listings,
                    SUM(CASE WHEN status = '$sold'      THEN price_cents ELSE 0 END) as total_revenue_cents,
                    MAX(currency) as currency
                ")
                ->first();

            $listingFee = V4InAppPurchase::where('sku', config('services.hockey_listing.fee_sku'))
                ->where('active', true)
                ->first(['amount_cents', 'currency']);

            $currency     = $row->currency ?? 'USD';
            $revenueCents = (int) $row->total_revenue_cents;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_listings'          => (int) $row->total_listings,
                    'active_listings'         => (int) $row->active_listings,
                    'sold_listings'           => (int) $row->sold_listings,
                    'total_revenue_cents'     => $revenueCents,
                    'total_revenue_formatted' => strtoupper($currency) . ' ' . number_format($revenueCents / 100, 2),
                    'listing_fee_cents'       => $listingFee?->amount_cents,
                    'listing_fee_formatted'   => $listingFee
                        ? strtoupper($listingFee->currency) . ' ' . number_format($listingFee->amount_cents / 100, 2)
                        : null,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Admin failed to fetch hockey listing stats', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch listing stats.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function manage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page'    => 'nullable|integer|min:12|max:50',
                'status'      => 'nullable|string|in:all,unsold,sold,deleted',
                'category'    => 'nullable|string|in:' . implode(',', HockeyListingCategories::all()),
                'search'      => 'nullable|string|max:255',
                'seller'    => 'nullable|string|max:255',
                'date_from' => 'nullable|date',
                'date_to'   => 'nullable|date',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 12);
            $status  = $validated['status'] ?? 'all';

            $query = V4HockeyListing::with([
                'images',
                'user:id,first_name,last_name,username,profile_photo,email,city,state,country,role',
                'paymentRequest:id,amount_cents,currency,status',
            ])->orderByDesc('listed_at');

            if ($status === 'all') {
                $query->whereIn('status', [V4HockeyListing::STATUS_PUBLISHED, V4HockeyListing::STATUS_SOLD]);
            } elseif ($status === 'unsold') {
                $query->where('status', V4HockeyListing::STATUS_PUBLISHED);
            } elseif ($status === 'deleted') {
                // Exclusively show soft-deleted listings; their status is rendered as "deleted".
                $query->onlyTrashed();
            } else {
                $query->where('status', $status);
            }

            if (!empty($validated['category'])) {
                $query->where('category', $validated['category']);
            }

            if (!empty($validated['search'])) {
                $term = '%' . $validated['search'] . '%';
                $query->where(function ($q) use ($term) {
                    $q->whereRaw("name ILIKE ?", [$term])
                      ->orWhereRaw("description ILIKE ?", [$term])
                      ->orWhereRaw("address ILIKE ?", [$term]);
                });
            }

            if (!empty($validated['seller'])) {
                $term = '%' . $validated['seller'] . '%';
                $query->whereHas('user', function ($q) use ($term) {
                    $q->whereRaw("email ILIKE ?", [$term])
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", [$term])
                      ->orWhereRaw("username ILIKE ?", [$term]);
                });
            }

            if (!empty($validated['date_from'])) {
                $query->whereDate('listed_at', '>=', $validated['date_from']);
            }

            if (!empty($validated['date_to'])) {
                $query->whereDate('listed_at', '<=', $validated['date_to']);
            }

            $listings = $query->paginate($perPage);

            return response()->json([
                'success'    => true,
                'data'       => array_map(fn($l) => $this->formatManageListing($l), $listings->items()),
                'pagination' => [
                    'current_page'   => $listings->currentPage(),
                    'per_page'       => $listings->perPage(),
                    'total'          => $listings->total(),
                    'last_page'      => $listings->lastPage(),
                    'has_more_pages' => $listings->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Admin failed to fetch managed hockey listings', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch listings.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(int $listing): JsonResponse
    {
        try {
            $record = V4HockeyListing::find($listing);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                ], 404);
            }

            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Listing removed successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Admin failed to delete hockey listing', [
                'listing_id' => $listing,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove listing.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function markSold(int $listing): JsonResponse
    {
        try {
            $record = V4HockeyListing::where('id', $listing)
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
            Log::error('Admin failed to mark hockey listing as sold', [
                'listing_id' => $listing,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark listing as sold.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function markAvailable(int $listing): JsonResponse
    {
        try {
            $record = V4HockeyListing::where('id', $listing)
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
            Log::error('Admin failed to mark hockey listing as available', [
                'listing_id' => $listing,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark listing as available.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    protected function formatManageListing(V4HockeyListing $listing): array
    {
        if ($listing->relationLoaded('user') && $listing->user) {
            $listing->user->setAppends([]);
        }

        $data = $listing->toArray();

        // Soft-deleted listings have no stored "deleted" status; surface it for the admin view.
        if ($listing->trashed()) {
            $data['status'] = V4HockeyListing::STATUS_DELETED;
        }

        if ($listing->relationLoaded('user') && $listing->user) {
            $u = $listing->user;
            $data['user'] = [
                'id' => $u->id,
                'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: null,
                'username' => $u->username,
                'email' => $u->email,
                'profile_photo' => $u->profile_photo,
                'city' => $u->city,
                'state' => $u->state,
                'country' => $u->country,
                'role' => $u->role,
            ];
        }

        $pr = $listing->relationLoaded('paymentRequest') ? $listing->paymentRequest : null;
        $feeCents = ($pr && $pr->status === V4PaymentRequest::STATUS_PAID) ? $pr->amount_cents : 0;

        $data['total_publishing_fee'] = number_format($feeCents / 100, 2);

        return $data;
    }
}
