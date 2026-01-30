<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4Post;
use App\Models\V4Follow;
use App\Models\V4User;
use App\Models\V4PostMedia;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

class V4FeedController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }


    public function getRecentFeeds(Request $request): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        try {
            // ✅ Inline validation with try-catch to handle ValidationException
            $validated = $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            // Pagination settings
            $perPage = (int) ($validated['per_page'] ?? 10);

            $followingIds = V4Follow::where('follower_id', $authUser->id)
                ->where('status', 'accepted')
                ->pluck('following_id')
                ->toArray();

            // Include own posts in feed
            $userIds = array_merge(
                []
                // [$authUser->id]
                ,
                $followingIds
            );

            // Fetch posts
            $posts = V4Post::with([
                'user:id,profile_photo,first_name,last_name,role',
                'media:id,post_id,type,url',
                'likedByAuthUser',
                // 'comments' => function ($query) {
                //     $query->latest()->limit(1); // ✅ Only latest comment
                // },
                // 'comments.user:id,username,profile_photo,role',
            ])
                ->whereIn('user_id', $userIds)
                ->orWhereHas('user.superAdminProfile', function ($query) {
                    $query->whereNull('super_admin_id');
                })
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => $posts->isEmpty()
                    ? 'No feeds found.'
                    : 'Recent feeds fetched successfully.',
                'data' => $posts->items(),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'last_page' => $posts->lastPage(),
                    'from'           => $posts->firstItem() ?? 0,
                    'to'             => $posts->lastItem() ?? 0,
                    'has_more_pages' => $posts->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $ve) {
            // ✅ Return validation errors in structured format
            

            // Track error in Sentry
            $this->errorTracker->captureException($ve, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $ve->errors(),
            ], 422);
        } catch (Exception $e) {
            // ✅ Log and handle unexpected errors
            Log::error('Feed fetch failed', [
                'user_id' => optional($authUser)->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch feeds.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getRecentFeedsByUserId(Request $request, ?int  $userId): JsonResponse
    {

        try {
            // Ensure user_id exists
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required.',
                ], 400);
            }

            // Merge and validate
            $request->merge(['user_id' => $userId]);

            // Validate input
            $validated = $request->validate([
                'user_id' => 'required|exists:v4_users,id',
                'per_page' => 'sometimes|integer|min:1|max:100'
            ]);

            // Fetch posts
            $perPage = (int) ($validated['per_page'] ?? 10);

            // Fetch posts of the given user only
            $posts = V4Post::with([
                'user:id,profile_photo,first_name,last_name,role',
                'media:id,post_id,type,url',
                'likedByAuthUser',
            ])
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => $posts->isEmpty()
                    ? 'No feeds found.'
                    : 'User feeds fetched successfully.',
                'data' => $posts->items(),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'last_page' => $posts->lastPage(),
                    'from' => $posts->firstItem() ?? 0,
                    'to' => $posts->lastItem() ?? 0,
                    'has_more_pages' => $posts->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $ve) {
            // ✅ Return validation errors in structured format
            

            // Track error in Sentry
            $this->errorTracker->captureException($ve, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $ve->errors(),
            ], 422);
        } catch (Exception $e) {
            // ✅ Log and handle unexpected errors
            Log::error('Feed fetch failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch feeds.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
