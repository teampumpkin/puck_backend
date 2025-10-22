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
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class V4FeedController extends Controller
{

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
                'user:id,profile_photo,first_name,last_name,date_of_birth',
                'media:id,post_id,type,url',
                // 'likes' => function ($query) use ($authUser) {
                //     $query->where('user_id', $authUser->id);
                // },
                'comments' => function ($query) {
                    $query->latest()->limit(1); // ✅ Only latest comment
                },
                'comments.user:id,username,profile_photo',
            ])
                ->whereIn('user_id', $userIds)
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
                ],
            ]);
        } catch (ValidationException $ve) {
            // ✅ Return validation errors in structured format
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
}
