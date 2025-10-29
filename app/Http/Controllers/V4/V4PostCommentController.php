<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4Post;
use App\Models\V4PostComment;
use App\Models\V4User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class V4PostCommentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Add a comment to a post
     */
    public function store(Request $request, $postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make(
            array_merge($request->all(), ['post_id' => $postId]),
            [
                'post_id'   => 'required|integer|exists:v4_posts,id',
                'body'      => 'required|string|max:2000',
                'parent_id' => 'nullable|exists:v4_post_comments,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $post = V4Post::findOrFail($postId);

            $comment = V4PostComment::create([
                'user_id'   => $authUser->id,
                'post_id'   => $post->id,
                'parent_id' => $request->parent_id,
                'body'      => $request->body,
            ]);

            // Notify post owner (if not same user)
            if ($post->user_id !== $authUser->id) {
                $this->sendToCommentNotification($authUser, $post->user, $post, $comment);
            }

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully.',
                'data'    => $comment->load('user'),
            ]);
        } catch (Exception $e) {
            Log::error('Comment store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to add comment.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete a comment
     */
    public function destroy($commentId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $comment = V4PostComment::findOrFail($commentId);

            if ($comment->user_id !== $authUser->id) {
                return response()->json(['success' => false, 'message' => 'You can only delete your own comment.'], 403);
            }

            $authUser->notifications
                ->where('type', 'user_post_commented')
                ->where('data->comment->id', $comment->id)
                ->delete();

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Comment delete failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete comment.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get comments for a post (with nested replies)
     */
    public function index($postId): JsonResponse
    {
        $validator = Validator::make(['post_id' => $postId], [
            'post_id' => 'required|integer|exists:v4_posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post ID.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $post = V4Post::find($postId);

            if (! $post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found.',
                ], 404);
            }

            $comments = V4PostComment::with([
                'user:id,first_name,last_name,profile_photo',
                'replies.user:id,first_name,last_name,profile_photo',
            ])
                ->where('post_id', $post->id)
                ->whereNull('parent_id')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $comments,
            ]);
        } catch (Exception $e) {
            Log::error('Fetch comments failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch comments.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(Request $request, $postId, $commentId): JsonResponse
    {

        $user = Auth::guard('v4api')->user();

        $validator = Validator::make(
            array_merge($request->all(), ['post_id' => $postId]),
            [
                'post_id' => 'required|integer|exists:v4_posts,id',
                'body'    => 'required|string|max:2000',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid post.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $comment = V4PostComment::findOrFail($commentId);

            if ($comment->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only edit your own comment.',
                ], 403);
            }

            // Save previous body (optional, for history tracking)
            $oldBody = $comment->body;

            $comment->update([
                'body' => $request->input('body'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully.',
                'data'    => $comment->fresh('user'),
            ]);
        } catch (\Exception $e) {
            Log::error('Comment update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update comment.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Send a notification when a user comments on another user's post.
     */
    protected function sendToCommentNotification(V4User $fromUser, V4User $toUser, V4Post $post, V4PostComment $comment)
    {
        $title   = "New Comment on Your Post";
        $message = "$fromUser->name commented on your post";

        $data = [
            'type'            => 'post_commented',
            'action_required' => false,
            'post'            => $post,
            'from_user'       => $fromUser->only(['id', 'name', 'first_name', 'last_name', 'profile_photo', 'role', 'date_of_birth']),
            'comment'         => [
                'id'         => $comment->id,
                'body'       => $comment->body,
                'created_at' => $comment->created_at,
                'parent_id'  => $comment->parent_id,
            ],
        ];

        return $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo ?? '',
            $data,
            'user_post_commented',
            "posts/$post->id?comment-id=$comment->id",
            "user_commented_action",
            $comment,
        );
    }
}
