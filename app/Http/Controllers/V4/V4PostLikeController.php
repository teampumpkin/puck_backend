<?php

namespace App\Http\Controllers\V4;


use App\Http\Controllers\Controller;
use App\Models\V4Post;
use App\Models\V4PostLike;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class V4PostLikeController extends Controller
{

    /**
     * Like a post
     */
    public function like(Request $request, $postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make(['post_id' => $postId], [
            'post_id' => 'required|exists:v4_posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $post = V4Post::findOrFail($postId);

            $existingLike = V4PostLike::withTrashed()
                ->where('user_id', $authUser->id)
                ->where('post_id', $post->id)
                ->first();

            if ($existingLike) {
                if ($existingLike->trashed()) {
                    $existingLike->restore(); // Triggers observer to log "liked"
                    return response()->json([
                        'success' => true,
                        'message' => 'Post liked again.',
                        'data' => $existingLike,
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'You already liked this post.',
                ], 409);
            }

            $like = V4PostLike::create([
                'user_id' => $authUser->id,
                'post_id' => $post->id,
            ]);

            // Send notification to post owner
            if ($post->user_id !== $authUser->id) {
                // NotificationService::send([
                //     'user_id' => $post->user_id,
                //     'title' => "{$authUser->username} liked your post",
                //     'body' => $post->caption ?? '',
                //     'data' => ['type' => 'like', 'post_id' => $post->id],
                // ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post liked successfully.',
                'data' => $like,
            ]);
        } catch (Exception $e) {
            Log::error('Like failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while liking the post.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Unlike a post
     */
    public function unlike(Request $request, $postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make(['post_id' => $postId], [
            'post_id' => 'required|exists:v4_posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $like = V4PostLike::where('user_id', $authUser->id)
                ->where('post_id', $postId)
                ->first();

            if (!$like) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have not liked this post.',
                ], 404);
            }

            $like->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post unliked successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Unlike failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while unliking the post.',
            ], 500);
        }
    }

    /**
     * Get all likes for a post
     */
    public function postLikes($postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make(['post_id' => $postId], [
            'post_id' => 'required|exists:v4_posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $post = V4Post::findOrFail($postId);

            $likes = V4PostLike::with('user:id,username,profile_photo,first_name,last_name,date_of_birth')
                ->where('post_id', $post->id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $likes,
            ]);
        } catch (Exception $e) {
            Log::error('Fetch likes failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch likes.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
