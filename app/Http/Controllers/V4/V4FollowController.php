<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\V4Follow;
use App\Models\V4User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class V4FollowController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Follow a user (or send request if private)
     */
    public function follow(Request $request, V4User $user): JsonResponse
    {
        $authUser        = Auth::guard('v4api')->user();

        if ($authUser->id === $user->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        try {
            $status = $user->is_private ? 'pending' : 'accepted';

            $follow = V4Follow::updateOrCreate(
                ['follower_id' => $authUser->id, 'following_id' => $user->id],
                ['status' => $status]
            );

            // Send notification
            if ($status === 'pending') {
                $this->sendRequestFollowingNotification($authUser, $user, $follow);
            } else {
                $this->sendFollowAcceptedNotification($authUser, $user, $follow);
            }

            return response()->json([
                'success' => true,
                'message' => $user->is_private ? 'Follow request sent' : 'Followed successfully',
                'data'    => $follow,
            ]);
        } catch (Exception $e) {
            Log::error('Follow failed: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'target_user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to follow user',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Unfollow a user
     */
    public function unfollow(Request $request, V4User $user): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        try {
            $follow = V4Follow::where('follower_id', $authUser->id)
                ->where('following_id', $user->id)
                ->first();

            if (! $follow) {
                return response()->json(['message' => 'You are not following this user'], 404);
            }

            $follow->delete();

            return response()->json(['message' => 'Unfollowed successfully']);
        } catch (ValidationException $e) {
            Log::error('Error Validation Follow' . $e->getMessage(), [
                'user_id'     => Auth::id(),
                'question_id' => $request->input('id'),
                'trace'       => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            // Handle case where the record is not found
            Log::error('Evaluation Submission not found', [
                'submission_id' => $id,
                'trace'         => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Evaluation submission not found',
            ], 404);
        } catch (Exception $e) {
            Log::error('Error fetching evaluation questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluation questions',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Accept a follow request (for private account)
     */
    public function acceptFollow(Request $request, V4User $user): JsonResponse
    {
        try {
            $request->validate(['follower_id' => 'required|exists:v4_users,id']);

            $authUser = Auth::guard('v4api')->user();

            $followerId = $request->follower_id;

            $followerUser = V4User::findOrFail($followerId);

            $follow = V4Follow::where([
                'follower_id'  => $request->follower_id,
                'following_id' => $authUser->id,
                'status'       => 'pending',
            ])->firstOrFail();

            if (! $follow) {
                return response()->json(['message' => 'No pending request found'], 404);
            }

            $follow->update(['status' => 'accepted']);

            //FIXME: -
            $this->sendFollowRequestAcceptedNotification($followerUser, $user, $follow);

            return response()->json(['message' => 'Follow request accepted']);
        } catch (ValidationException $e) {
            Log::error('Error Validation Follow' . $e->getMessage(), [
                'user_id'     => Auth::id(),
                'question_id' => $request->input('id'),
                'trace'       => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            // Handle case where the record is not found
            Log::error('Evaluation Submission not found', [
                'submission_id' => $id,
                'trace'         => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Evaluation submission not found',
            ], 404);
        } catch (Exception $e) {
            Log::error('Error fetching evaluation questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluation questions',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject a follow request
     */
    public function rejectFollow(Request $request, V4User $user): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if ($authUser->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['follower_id' => 'required|exists:v4_users,id']);
        $followerId = $request->follower_id;
        try {
            $follow = V4Follow::where([
                'follower_id' => $followerId,
                'following_id' => $authUser->id,
                'status' => 'pending',
            ])->first();

            if (! $follow) {
                return response()->json(['message' => 'No pending follow request found'], 404);
            }

            $followerUser = V4User::findOrFail($followerId);

            $follow->delete();

            $this->sendFollowRejectedNotification($authUser, $user);

            return response()->json(['message' => 'Follow request rejected']);
        } catch (Exception $e) {
            Log::error('Reject follow failed: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'follower_id' => $followerId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject follow request',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List followers
     */
    public function followers(V4User $user): JsonResponse
    {
        $followers = V4Follow::with('follower')
            ->where('following_id', $user->id)
            ->where('status', 'accepted')
            ->latest()
            ->paginate(20);

        return response()->json($followers);
    }

    /**
     * List following
     */
    public function following(V4User $user): JsonResponse
    {
        $following = V4Follow::with('following')
            ->where('follower_id', $user->id)
            ->where('status', 'accepted')
            ->latest()
            ->paginate(20);

        return response()->json($following);
    }

    // ------------------- NOTIFICATION METHODS --------------------

    protected function sendFollowAcceptedNotification(V4User $fromUser, V4User $toUser, V4Follow $follow)
    {
        $title   = "New Follower";
        $message = "{$fromUser->name} started following you.";

        $data = [
            'type'            => 'follow',
            'action_required' => false,
            'status'          => $follow->status,
            'from_user_id'    => $fromUser->id,
        ];

        $notification = $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo,
            $data,
            'user_follow',
            "profile/{$fromUser->id}",
            'user_follow_action',
            $follow
        );
        return $notification;
    }
    /**
     * Private user - send follow request notification
     */

    protected function sendRequestFollowingNotification(V4User $fromUser, V4User $toUser, V4Follow $follow)
    {
        $title   = "Follow Request";
        $message = "{$fromUser->name} requested to connect with you";

        $data = [
            'type'            => 'follow_request',
            'quick_actions'   => ['accept', 'reject'],
            'action_required' => true,
            'status'          => $follow->status,
        ];

        $notification = $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo,
            $data,
            'user_follow_request',
            'profile/{$fromUser->id}',
            'user_follow_request_action',
            $follow,

        );
        return $notification;
    }

    /**
     * Notify follower that their follow request was accepted
     */
    protected function sendFollowRequestAcceptedNotification(V4User $fromUser, V4User $toUser, V4Follow $follow)
    {
        $title   = 'Follow Request Accepted';
        $message = "{$fromUser->name} accepted your follow request.";

        $data = [
            'type'            => 'follow_accepted',
            'action_required' => false,
            'status'          => $follow->status,
            'from_user_id'    => $fromUser->id,
        ];

        return $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo,
            $data,
            'user_follow_accepted',
            "profile/{$fromUser->id}",
            'user_follow_accepted_action',
            $follow
        );
    }

    /**
     * Notify follower that their follow request was rejected
     */
    protected function sendFollowRejectedNotification(V4User $fromUser, V4User $toUser)
    {
        $title   = 'Follow Request Rejected';
        $message = "{$fromUser->name} rejected your follow request.";

        $data = [
            'type'            => 'follow_rejected',
            'action_required' => false,
            'from_user_id'    => $fromUser->id,
        ];

        return $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo,
            $data,
            'user_follow_rejected',
            "profile/{$fromUser->id}",
            'user_follow_rejected_action',
            null // No follow model passed since it may be deleted
        );
    }
}
