<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4ConsultationRequest;
use App\Models\V4Follow;
use App\Models\V4User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    public function follow(Request $request, $userId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        // Prevent following self
        if ($authUser->id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot follow yourself.',
            ], 400);
        }

        try {
            $user = V4User::findOrFail($userId);
            $status = 'pending'; // $user->enable_private_account ? 'pending' : 'accepted';

            DB::beginTransaction();
            // Check if already followed or pending
            $existing = V4Follow::withTrashed()
                ->where('follower_id', $authUser->id)
                ->where('following_id', $user->id)
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                    $existing->update(['status' => $status]);
                    $follow = $existing;


                    try {
                        $token = $request->bearerToken();

                        $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');

                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type'  => 'application/json',
                        ])->post($baseUrl . '/conversation/create', [
                            'type'         => 'single',
                            'participants' => [(string)$authUser->id, (string)$user->id],
                        ]);

                        if ($response->successful() && isset($response->json()['_id'])) {
                            $conversationId = $response->json()['_id'];
                            $existing->update(['conversation_id' => $conversationId,]);
                            $follow = $existing;
                        } else {
                            Log::warning('Conversation API failed', [
                                'status' => $response->status(),
                                'body'   => $response->body(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('Conversation API error', ['error' => $e->getMessage()]);
                    }
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Already following or follow request pending.',
                    ], 409);
                }
            } else {
                /**
                 * 🔹 Create conversation BEFORE creating follow record
                 */
                $conversationId = null;
                try {
                    $token = $request->bearerToken();

                    $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ])->post($baseUrl . '/conversation/create', [
                                'type' => 'single',
                                'participants' => [(string) $authUser->id, (string) $user->id],
                            ]);

                    if ($response->successful() && isset($response->json()['_id'])) {
                        $conversationId = $response->json()['_id'];
                    } else {
                        Log::warning('Conversation API failed', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('Conversation API error', ['error' => $e->getMessage()]);
                }


                $follow = V4Follow::create([
                    'follower_id' => $authUser->id,
                    'following_id' => $user->id,
                    'status' => $status,
                    'conversation_id' => $conversationId,
                ]);
            }

            DB::commit();

            // Send notification
            if ($status === 'pending') {
                $this->sendRequestFollowingNotification($authUser, $user, $follow);
            } else {
                $this->sendFollowAcceptedNotification($authUser, $user, $follow);
            }

            return response()->json([
                'success' => true,
                'message' => $status === 'pending'
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
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('Database error during follow operation.', [
                'user_id' => $authUser->id,
                'target_user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Unexpected error during follow.', [
                'user_id' => $authUser->id,
                'target_user_id' => $userId,
                'error' => $e->getMessage(),
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
    public function unfollow(Request $request, $userId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($authUser->id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot unfollow yourself.',
            ], 400);
        }

        try {
            $user = V4User::findOrFail($userId);

            $follow = V4Follow::where('follower_id', $authUser->id)
                ->where('following_id', $user->id)
                ->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not following this user.',
                ], 409);
            }

            DB::beginTransaction();

            $follow->update(['status' => 'pending']);

            // Soft delete follow record
            $follow->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unfollowed successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('Database error during unfollow.', [
                'user_id' => $authUser->id,
                'target_user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Unexpected error during unfollow.', [
                'user_id' => $authUser->id,
                'target_user_id' => $userId,
                'error' => $e->getMessage(),
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
    public function acceptFollow(Request $request, $userId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($authUser->id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot accept a follow request from yourself.',
            ], 400);
        }

        try {
            $user = V4User::findOrFail($userId);

            $follow = V4Follow::where([
                'follower_id' => $user->id,     // $user is the follower (request sender)
                'following_id' => $authUser->id, // Auth user is the one being followed (request receiver)
                'status' => 'pending',
            ])->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending follow request found.',
                ], 404);
            }

            DB::beginTransaction();

            $follow->update(['status' => 'accepted']);

            $follow->notifications()
                ->where('type', 'user_follow_request')
                ->delete();

            DB::commit();

            $this->sendFollowRequestAcceptedNotification($user, $authUser, $follow);

            return response()->json([
                'success' => true,
                'message' => 'Follow request accepted.',
                'data' => $follow,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('User not found while accepting follow request: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'follower_id' => $userId,
                'target_user_id' => $authUser->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('Database error while accepting follow request: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'follower_id' => $userId,
                'target_user_id' => $authUser->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Unexpected error while accepting follow request: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'follower_id' => $userId,
                'target_user_id' => $authUser->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while accepting the follow request.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject a follow request
     */
    public function rejectFollow(Request $request, $userId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($authUser->id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot reject your own follow request.',
            ], 400);
        }

        try {
            $user = V4User::findOrFail($userId);

            $follow = V4Follow::where([
                'follower_id' => $user->id,     // $user is the follower (request sender)
                'following_id' => $authUser->id, // Auth user is the one being followed (request receiver)
                'status' => 'pending',
            ])->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending follow request found.',
                ], 404);
            }

            DB::beginTransaction();

            $follow->notifications()
                ->where('type', 'user_follow_request')
                ->delete();

            $follow->delete();

            DB::commit();

            // $this->sendFollowRejectedNotification($user, $authUser);

            return response()->json([
                'success' => true,
                'message' => 'Follow request rejected.',
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('User not found while rejecting follow request: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'follower_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('Database error while rejecting follow request: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'follower_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Unexpected error while rejecting follow request: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'follower_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the follow request.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function cancelFollow(Request $request, $userId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (! $authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($authUser->id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot cancel a follow request to yourself.',
            ], 400);
        }

        try {
            $user = V4User::findOrFail($userId);

            // Find pending follow request sent by the auth user to this user
            $follow = V4Follow::where([
                'follower_id'  => $authUser->id,
                'following_id' => $user->id,
                'status'       => 'pending',
            ])->first();

            if (! $follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending follow request found to cancel.',
                ], 404);
            }

            DB::beginTransaction();

            // Delete any pending notifications related to this follow
            $follow->notifications()
                ->where('type', 'user_follow_request')
                ->delete();

            // Soft delete the follow record
            $follow->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Follow request canceled successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('Database error while canceling follow request.', [
                'user_id'        => $authUser->id,
                'target_user_id' => $userId,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Unexpected error while canceling follow request.', [
                'user_id'        => $authUser->id,
                'target_user_id' => $userId,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while canceling the follow request.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function removeFollower(Request $request, $userId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (! $authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            $currentUser = V4User::findOrFail($authUser->id);
            $targetUser = V4User::findOrFail($userId);

            if ($currentUser->hasBlocked($targetUser->id) || $currentUser->isBlockedBy($targetUser->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action not allowed due to blocking.',
                ], 403);
            }

            // Find pending follow request sent by the auth user to this user
            $followerRelation = V4Follow::where('follower_id', $targetUser->id)
                ->where('following_id', $currentUser->id)
                ->where('status', 'accepted')
                ->first();

            if (!$followerRelation) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is not your follower.',
                ], 404);
            }

            DB::beginTransaction();

            $followerRelation->delete();
            $currentUser->decrement('followers_count');
            $targetUser->decrement('followings_count');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Follower has been removed successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to remove follower: ' . $e->getMessage(), [
                'auth_user_id'   => $authUser->id ?? null,
                'target_user_id' => $userId,
                'trace'          => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove follower.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List followers
     */
    public function followers($userId, Request $request): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        // Validate query parameters
        $request->validate([
            'q'        => 'nullable|string|max:255',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100)); // Clamp between 1 and 100
        $searchQuery = $request->query('q');

        try {
            $currentUser = V4User::findOrFail($authUser->id);
            $user = V4User::findOrFail($userId);

            $query = V4Follow::with('follower')
                ->where('following_id', $user->id)
                ->where('status', 'accepted')
                ->latest();

            // Apply search filter if query is provided
            if ($searchQuery) {
                $query->whereHas('follower', function ($q) use ($searchQuery) {
                    $q->where('first_name', 'ilike', "%{$searchQuery}%")
                        ->orWhere('last_name', 'ilike', "%{$searchQuery}%");
                });
            }

            // Paginate results
            $followers = $query->paginate($perPage);



            // Add is_following and is_follower inside each "follower" object
            foreach ($followers->items() as $item) {
                if ($item->follower) {
                    $target = $item->follower;

                    $target->is_following     = $currentUser->isFollowing($target->id);
                    $target->is_follower      = $currentUser->isFollowedBy($target->id);
                    $target->has_sent_request = $currentUser->hasPendingRequest($target->id);
                    $target->has_received_request = $currentUser->hasSendPendingRequest($target->id);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Followers retrieved successfully.',
                'data' => $followers->items(),
                'pagination' => [
                    'total' => $followers->total(),
                    'per_page' => $followers->perPage(),
                    'current_page' => $followers->currentPage(),
                    'last_page' => $followers->lastPage(),
                    'from' => $followers->firstItem() ?? 0,
                    'to' => $followers->lastItem() ?? 0,
                    'has_more_pages' => $followers->hasMorePages(),
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('User not found when fetching followers: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Fetching followers failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve followers.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
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
        $perPage = max(1, min($perPage, 100)); // Clamp between 1 and 100

        try {
            $followers = V4Follow::with('follower')
                ->where('following_id', $authUser->id)
                ->where('status', 'accepted')
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Followers retrieved successfully.',
                'data' => $followers->items(),
                'pagination' => [
                    'total' => $followers->total(),
                    'per_page' => $followers->perPage(),
                    'current_page' => $followers->currentPage(),
                    'last_page' => $followers->lastPage(),
                    'from' => $followers->firstItem() ?? 0,
                    'to' => $followers->lastItem() ?? 0,
                    'has_more_pages' => $followers->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Fetching my followers failed: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve followers.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List following
     */
    public function following($userId, Request $request): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        $request->validate([
            'q'        => 'nullable|string|max:255',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100)); // Clamp between 1 and 100
        $searchQuery = $request->query('q');

        try {
            $currentUser = V4User::findOrFail($authUser->id);
            $user = V4User::findOrFail($userId);

            $query = V4Follow::with('following')
                ->where('follower_id', $user->id)
                ->where('status', 'accepted')
                ->latest();

            // Apply search filter if query is provided
            if ($searchQuery) {
                $query->whereHas('following', function ($q) use ($searchQuery) {
                    $q->where('first_name', 'ilike', "%{$searchQuery}%")
                        ->orWhere('last_name', 'ilike', "%{$searchQuery}%");
                });
            }

            // Paginate results
            $following = $query->paginate($perPage);



            // Transform paginated items (safe method)
            foreach ($following->items() as $item) {
                if ($item->following) {
                    $target = $item->following;

                    $target->is_following     = $currentUser->isFollowing($target->id);
                    $target->is_follower      = $currentUser->isFollowedBy($target->id);
                    $target->has_sent_request = $currentUser->hasPendingRequest($target->id);
                    $target->has_received_request = $currentUser->hasSendPendingRequest($target->id);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Following list retrieved successfully.',
                'data' => $following->items(),
                'pagination' => [
                    'total' => $following->total(),
                    'per_page' => $following->perPage(),
                    'current_page' => $following->currentPage(),
                    'last_page' => $following->lastPage(),
                    'from' => $following->firstItem() ?? 0,
                    'to' => $following->lastItem() ?? 0,
                    'has_more_pages' => $following->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Fetching following list failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve following list.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
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
        $perPage = max(1, min($perPage, 100)); // Clamp between 1 and 100

        try {
            $following = V4Follow::with('following')
                ->where('follower_id', $authUser->id)
                ->where('status', 'accepted')
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Following list retrieved successfully.',
                'data' => $following->items(),
                'pagination' => [
                    'total' => $following->total(),
                    'per_page' => $following->perPage(),
                    'current_page' => $following->currentPage(),
                    'last_page' => $following->lastPage(),
                    'from' => $following->firstItem() ?? 0,
                    'to' => $following->lastItem() ?? 0,
                    'has_more_pages' => $following->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Fetching following list failed: ' . $e->getMessage(), [
                'user_id' => $authUser->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve following list.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // ------------------- NOTIFICATION METHODS --------------------

    protected function sendFollowAcceptedNotification(V4User $fromUser, V4User $toUser, V4Follow $follow)
    {
        $title = "New Follower";
        $message = "$fromUser->name started following you.";

        $data = [
            'type' => 'follow',
            'action_required' => false,
            'status' => $follow->status,
            'from_user' => $fromUser->only(['id', 'name', 'first_name', 'last_name', 'profile_photo', 'role', 'date_of_birth']),
        ];

        $notification = $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo ?? '',
            $data,
            'user_follow',
            "profile/$fromUser->id",
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
        $title = "Follow Request";
        $message = "$fromUser->name requested to connect with you";

        $data = [
            'type' => 'follow_request',
            'quick_actions' => ['accept', 'reject'],
            'action_required' => true,
            'status' => $follow->status,
            'from_user' => $fromUser->only(['id', 'name', 'first_name', 'last_name', 'profile_photo', 'role', 'date_of_birth']),
        ];

        $notification = $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo ?? '',
            $data,
            'user_follow_request',
            "profile/$fromUser->id",
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
        $title = 'Follow Request Accepted';
        $message = "$fromUser->name accepted your follow request.";

        $data = [
            'type' => 'follow_accepted',
            'action_required' => false,
            'status' => $follow->status,
            'from_user' => $fromUser->only(['id', 'name', 'first_name', 'last_name', 'profile_photo', 'role', 'date_of_birth']),
        ];

        return $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo ?? '',
            $data,
            'user_follow_accepted',
            "profile/$fromUser->id",
            'user_follow_accepted_action',
            $follow
        );
    }

    /**
     * Notify follower that their follow request was rejected
     */
    protected function sendFollowRejectedNotification(V4User $fromUser, V4User $toUser)
    {
        $title = 'Follow Request Rejected';
        $message = "$fromUser->name rejected your follow request.";

        $data = [
            'type' => 'follow_rejected',
            'action_required' => false,
            'from_user' => $fromUser->only(['id', 'name', 'first_name', 'last_name', 'profile_photo', 'role', 'date_of_birth']),
        ];

        return $this->notificationService->sendToUserWithImage(
            $toUser,
            $title,
            $message,
            $fromUser->profile_photo ?? '',
            $data,
            'user_follow_rejected',
            "profile/$fromUser->id",
            'user_follow_rejected_action',
            null // No follow model passed since it may be deleted
        );
    }

    /**
     * Send consultation request notification to evaluator
     */
    public function sendConsultationRequestNotification(V4User $player, V4User $evaluator, V4ConsultationRequest $consultationRequest)
    {
        $playerName = $player->first_name . ' ' . $player->last_name;
        $title = '1-on-1 Consultation Request';
        $message = "$playerName requested for a 1 on 1 consultation";

        $data = [
            'type' => 'consultation_request',
            'action_required' => true,
            'player' => $player->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
            'consultation_request_id' => $consultationRequest->id,
            'evaluation_id' => $consultationRequest->evaluation_id,
            'consultation_date' => $consultationRequest->submissionVersion->consultation_date ?? null,
            'consultation_time' => $consultationRequest->submissionVersion->consultation_time ?? null,
        ];

        return $this->notificationService->sendToUserWithImage(
            $evaluator,
            $title,
            $message,
            $player->profile_photo ?? "",
            $data,
            'consultation_request',
            "consultation/requests/{$consultationRequest->id}",
            'consultation_request_action',
            $consultationRequest
        );
    }
}
