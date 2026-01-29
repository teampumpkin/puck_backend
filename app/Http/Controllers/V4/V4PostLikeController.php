<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4Post;
use App\Models\V4PostLike;
use App\Models\V4User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Contracts\ErrorTrackerInterface;

class V4PostLikeController extends Controller
{
    protected $errorTracker;


    protected $notificationService;

    public function __construct(ErrorTrackerInterface $errorTracker, NotificationService $notificationService)
    {
        $this->errorTracker = $errorTracker;
        $this->notificationService = $notificationService;
    }

    /**
     * Like a post
     */
    public function like(Request $request, $postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (! $authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 401);
        }

        $validator = Validator::make(['post_id' => $postId], [
            'post_id' => 'required|exists:v4_posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($authUser, $postId) {
                $post = V4Post::findOrFail($postId);

                $existingLike = V4PostLike::withTrashed()
                    ->where('user_id', $authUser->id)
                    ->where('post_id', $post->id)
                    ->first();

                if ($existingLike) {
                    if ($existingLike->trashed()) {
                        $existingLike->restore();

                        // Delete old notifications for same like
                        $post->user->v4Notifications()
                            ->where('type', 'user_post_liked')
                            ->where('data->postLike->id', $existingLike->id)
                            ->delete();

                        // Send new notification
                        if ($post->user_id !== $authUser->id) {
                            $this->sendToLikeNotification(
                                $authUser,
                                $post->user,
                                $post,
                                $existingLike
                            );
                        }
                        return response()->json([
                            'success' => true,
                            'message' => 'Post liked again.',
                            'data'    => $existingLike,
                        ]);
                    }

                    // Already liked & active
                    return response()->json([
                        'success' => false,
                        'message' => 'You already liked this post.',
                    ], 409);
                }

                /* ----------------------------------------
             * NEW LIKE
             * ---------------------------------------- */
                $like = V4PostLike::create([
                    'user_id' => $authUser->id,
                    'post_id' => $post->id,
                ]);

                // Send notification
                if ($post->user_id !== $authUser->id) {
                    $this->sendToLikeNotification($authUser, $post->user, $post, $like);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Post liked successfully.',
                    'data'    => $like,
                ]);
            });
        } catch (ModelNotFoundException $e) {

            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        } catch (Exception $e) {

            Log::error('Like failed', [
                'error'   => $e->getMessage(),
                'user_id' => $authUser->id ?? null,
                'post_id' => $postId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while liking the post.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Unlike a post
     */
    public function unlike(Request $request, $postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (! $authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 401);
        }

        $validator = Validator::make(['post_id' => $postId], [
            'post_id' => 'required|exists:v4_posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($authUser, $postId) {

                $post = V4Post::findOrFail($postId);

                $like = V4PostLike::withTrashed()
                    ->where('user_id', $authUser->id)
                    ->where('post_id', $post->id)
                    ->first();

                if (! $like) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You have not liked this post.',
                    ], 404);
                }

                // Soft delete like
                $like->delete();

                // Remove notification using JSON lookup
                $post->user->v4Notifications()
                    ->where('type', 'user_post_liked')
                    ->where('data->postLike->id', $like->id)
                    ->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Post unliked successfully.',
                ]);
            });
        } catch (ModelNotFoundException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Unlike failed', [
                'error'   => $e->getMessage(),
                'user_id' => $authUser->id ?? null,
                'post_id' => $postId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while unliking the post.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get all likes for a post
     */
    public function postLikes($postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make(['post_id' => $postId], [
            'post_id' => 'required|exists:v4_posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $post = V4Post::findOrFail($postId);

            $likes = V4PostLike::with('user:id,username,profile_photo,first_name,last_name,date_of_birth')
                ->where('post_id', $post->id)
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $likes,
            ]);
        } catch (Exception $e) {
            Log::error('Fetch likes failed', ['error' => $e->getMessage()]);
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch likes.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Notify Like that their follow request was rejected
     */
    protected function sendToLikeNotification(V4User $fromUser, V4User $toUser, V4Post $post, V4PostLike $postLike)
    {
        $title   = "Post Liked";
        $message = "{$fromUser->name} Liked your post";

        $data = [
            'type'            => 'user_post_liked',
            'action_required' => false,
            'post'            => $post,
            'postLike'        => $postLike,
            'from_user'       => $fromUser->only(['id', 'name', 'first_name', 'last_name', 'profile_photo', 'role', 'date_of_birth']),
        ];

        return $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo ?? '',
            $data,
            'user_post_liked',
            "posts/{$post->id}",
            "user_liked_action",
            $postLike,
        );
    }
}
