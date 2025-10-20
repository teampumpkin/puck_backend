<?php

namespace App\Http\Controllers\V4;

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
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if ($authUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot follow yourself',
            ], 400);
        }

        try {
            // Check if already followed or pending
            $existing = V4Follow::withTrashed()
                ->where('follower_id', $authUser->id)
                ->where('following_id', $user->id)
                ->first();

            $status = $user->enable_private_account ? 'pending' : 'accepted';

            DB::beginTransaction();

            if ($existing) {
                if ($existing->trashed()) {
                    // Restore soft-deleted relationship (re-follow)
                    $existing->restore();
                    $existing->update(['status' => $status]);
                    $follow = $existing;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Already following or follow request pending.',
                    ], 409);
                }
            } else {
                $follow = V4Follow::create([
                    'follower_id' => $authUser->id,
                    'following_id' => $user->id,
                    'status' => $status,
                ]);
            }

            DB::commit();

            // Send notification
            if ($user->enable_private_account) {
                $this->sendRequestFollowingNotification($authUser, $user, $follow);
            } else {
                $this->sendFollowAcceptedNotification($authUser, $user, $follow);
            }

            return response()->json([
                'success' => true,
                'message' => $user->enable_private_account
                    ? 'Follow request sent successfully.'
                    : 'User followed successfully.',
                'data' => $follow,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Follow failed: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'target_user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while trying to follow the user.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Unfollow a user
     */
    public function unfollow(Request $request, V4User $user): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $follow = V4Follow::where('follower_id', $authUser->id)
                ->where('following_id', $user->id)
                ->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not following this user',
                ], 409);
            }

            DB::beginTransaction();

            // Soft delete follow record
            $follow->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unfollowed successfully',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Unfollow failed: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'target_user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while trying to unfollow the user.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Accept a follow request (for private account)
     */
    public function acceptFollow(Request $request, V4User $user): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if ($authUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot follow yourself',
            ], 400);
        }

        try {
            $follow = V4Follow::where([
                'follower_id'  => $user->id,         // $user is the follower
                'following_id' => $authUser->id,     // Auth user is the one being followed
                'status'       => 'pending',
            ])->first();

            if (! $follow) {
                return response()->json(['message' => 'No pending request found'], 404);
            }

            $follow->update(['status' => 'accepted']);

            $this->sendFollowRequestAcceptedNotification($user, $authUser, $follow);

            return response()->json([
                'success' => true,
                'message' => 'Follow request accepted',
                'data'    => $follow,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('Follow request not found: ' . $e->getMessage(), [
                'user_id'        => $authUser->id ?? null,
                'follower_id'    => $user->id,
                'target_user_id' => $authUser->id,
                'trace'          => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Follow request not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Accept follow failed: ' . $e->getMessage(), [
                'user_id'        => $authUser->id ?? null,
                'follower_id'    => $user->id,
                'target_user_id' => $authUser->id,
                'trace'          => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while accepting the follow request.',
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

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if ($authUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot reject your own follow request.',
            ], 400);
        }

        try {
            $follow = V4Follow::where([
                'follower_id'  => $user->id,       // $user is the follower
                'following_id' => $authUser->id,   // Auth user is the target
                'status'       => 'pending',
            ])->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending follow request found.',
                ], 404);
            }

            $follow->delete();

            $this->sendFollowRejectedNotification($user, $authUser);

            return response()->json(['message' => 'Follow request rejected']);
        } catch (Exception $e) {
            Log::error('Reject follow failed: ' . $e->getMessage(), [
                'user_id'      => $authUser->id,
                'follower_id'  => $user->id,
                'trace'        => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the follow request.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List followers
     */
    public function followers(V4User $user, Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100)); // Ensure perPage is between 1 and 100

        try {
            $followers = V4Follow::with('follower')
                ->where('following_id', $user->id)
                ->where('status', 'accepted')
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Followers retrieved successfully.',
                'data'    => $followers->items(),
                'pagination' => [
                    'total'          => $followers->total(),
                    'per_page'       => $followers->perPage(),
                    'current_page'   => $followers->currentPage(),
                    'last_page'      => $followers->lastPage(),
                    'from'           => $followers->firstItem() ?? 0,
                    'to'             => $followers->lastItem() ?? 0,
                    'has_more_pages' => $followers->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Fetching followers failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve followers.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List followers
     */
    public function myFollowers(Request $request): JsonResponse
    {

        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100)); // Ensure perPage is between 1 and 100

        try {
            $followers = V4Follow::with('follower')
                ->where('following_id', $authUser->id)
                ->where('status', 'accepted')
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Followers retrieved successfully.',
                'data'    => $followers->items(),
                'pagination' => [
                    'total'          => $followers->total(),
                    'per_page'       => $followers->perPage(),
                    'current_page'   => $followers->currentPage(),
                    'last_page'      => $followers->lastPage(),
                    'from'           => $followers->firstItem() ?? 0,
                    'to'             => $followers->lastItem() ?? 0,
                    'has_more_pages' => $followers->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Fetching followers failed: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve followers.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List following
     */
    public function following(V4User $user, Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100)); // Ensure perPage is between 1 and 100

        try {
            $following = V4Follow::with('following')
                ->where('follower_id', $user->id)
                ->where('status', 'accepted')
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Following list retrieved successfully.',
                'data'    => $following->items(),
                'pagination' => [
                    'total'          => $following->total(),
                    'per_page'       => $following->perPage(),
                    'current_page'   => $following->currentPage(),
                    'last_page'      => $following->lastPage(),
                    'from'           => $following->firstItem() ?? 0,
                    'to'             => $following->lastItem() ?? 0,
                    'has_more_pages' => $following->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Fetching following list failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve following list.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function myFollowing(Request $request): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100)); // Ensure perPage is between 1 and 100

        try {
            $following = V4Follow::with('following')
                ->where('follower_id', $authUser->id)
                ->where('status', 'accepted')
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Following list retrieved successfully.',
                'data'    => $following->items(),
                'pagination' => [
                    'total'          => $following->total(),
                    'per_page'       => $following->perPage(),
                    'current_page'   => $following->currentPage(),
                    'last_page'      => $following->lastPage(),
                    'from'           => $following->firstItem() ?? 0,
                    'to'             => $following->lastItem() ?? 0,
                    'has_more_pages' => $following->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Fetching following list failed: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve following list.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
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
