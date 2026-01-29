<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\BlockedUser;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

class UserBlockController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

    /**
     * Block a user
     */
    public function blockUser(Request $request)
    {
        try {
            $user = Auth::guard('v4api')->user();

            $request->validate([
                'blocked_id' => 'required|exists:v4_users,id',
                'reason' => 'nullable|string|max:500'
            ]);

            // Prevent blocking self
            if ($request->blocked_id == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot block yourself'
                ], 422);
            }

            // Check if user is already blocked
            $existingBlock = BlockedUser::blockedBy($user->id)
                ->blockedUser($request->blocked_id)
                ->active()
                ->exists();

            if ($existingBlock) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already blocked'
                ], 422);
            }

            // Block the user
            $block = BlockedUser::create([
                'blocker_id' => $user->id,
                'blocked_id' => $request->blocked_id,
                'reason' => $request->reason,
                'blocked_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User blocked successfully',
                'data' => $block
            ], 200);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Block User Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while blocking the user. Please try again',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Unblock a user
     */
    public function unblockUser(Request $request, $userId)
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Find the block record
            $block = BlockedUser::blockedBy($user->id)
                ->blockedUser($userId)
                ->active()
                ->first();

            if (!$block) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not blocked or already unblocked'
                ], 404);
            }

            // Unblock the user
            $block->update([
                'unblocked_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User unblocked successfully',
                'data' => $block
            ], 200);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Unblock User Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while unblocking the user. Please try again',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get the list of blocked users
     */
    public function getBlockedUsers()
    {
        try {
            $user = Auth::guard('v4api')->user();
            $blockedUsers = BlockedUser::with('blocked')
                ->blockedBy($user->id)
                ->active()
                ->get();


            return response()->json([
                'success' => true,
                'data' => $blockedUsers
            ], 200);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Get Blocked Users Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching blocked users. Please try again',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get blocking history
     */
    public function getBlockHistory()
    {
        try {
            $user = Auth::guard('v4api')->user();

            $history = BlockedUser::with(['blocker', 'blocked'])
                ->history($user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history
            ], 200);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Get Block History Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching block history. Please try again',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check block status
     */
    public function checkBlockStatus($userId)
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Check if any active blocks exist between the users
            $blockStatus = BlockedUser::betweenUsers($user->id, $userId)
                ->active()
                ->get(['blocker_id', 'blocked_id']);

            $isBlocked = $blockStatus->contains('blocker_id', $user->id);
            $hasBlockedYou = $blockStatus->contains('blocker_id', $userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'you_blocked_them' => $isBlocked,
                    'they_blocked_you' => $hasBlockedYou,
                    'is_blocked' => $isBlocked || $hasBlockedYou
                ]
            ], 200);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Check Block Status Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking block status. Please try again',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
