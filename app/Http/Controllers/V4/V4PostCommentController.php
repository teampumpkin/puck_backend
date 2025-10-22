<?php

namespace App\Http\Controllers\V4;


use App\Http\Controllers\Controller;

use App\Models\V4Post;
use App\Models\V4PostComment;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class V4PostCommentController extends Controller
{

    /**
     * Add a comment to a post
     */
    public function store(Request $request, $postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:v4_comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $post = V4Post::findOrFail($postId);

            $comment = V4PostComment::create([
                'user_id' => $authUser->id,
                'post_id' => $post->id,
                'parent_id' => $request->parent_id,
                'body' => $request->body,
            ]);

            // Notify post owner (if not same user)
            if ($post->user_id !== $authUser->id) {
                // NotificationService::send([
                //     'user_id' => $post->user_id,
                //     'title' => "{$authUser->username} commented on your post",
                //     'body' => $request->body,
                //     'data' => ['type' => 'comment', 'post_id' => $post->id],
                // ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully.',
                'data' => $comment->load('user'),
            ]);
        } catch (Exception $e) {
            Log::error('Comment store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to add comment.',
            ], 500);
        }
    }



    /**
     * Delete a comment
     */
    public function destroy($commentId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $comment = V4PostComment::findOrFail($commentId);

            if ($comment->user_id !== $authUser->id) {
                return response()->json(['success' => false, 'message' => 'You can only delete your own comment.'], 403);
            }

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
            ], 500);
        }
    }

    /**
     * Get comments for a post (with nested replies)
     */
    public function index($postId): JsonResponse
    {
        try {
            $comments = V4PostComment::with(['user:id,username,profile_picture', 'replies.user:id,username,profile_picture'])
                ->where('post_id', $postId)
                ->whereNull('parent_id')
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $comments,
            ]);
        } catch (Exception $e) {
            Log::error('Fetch comments failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch comments.',
            ], 500);
        }
    }
}
