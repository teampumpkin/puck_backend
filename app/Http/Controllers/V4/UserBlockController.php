<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BlockedUser;
use Illuminate\Support\Facades\Validator;

class UserBlockController extends Controller
{
    /**
     * Block a user
     */
    public function blockUser(Request $request)
    {
        $request->validate([
            'blocked_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($request->blocked_id == Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot block yourself'
            ], 422);
        }

        $existingBlock = BlockedUser::where('blocker_id', Auth::id())
            ->where('blocked_id', $request->blocked_id)
            ->active()
            ->first();

        if ($existingBlock) {
            return response()->json([
                'success' => false,
                'message' => 'User is already blocked'
            ], 422);
        }

        $block = BlockedUser::create([
            'blocker_id' => Auth::id(),
            'blocked_id' => $request->blocked_id,
            'reason' => $request->reason,
            'blocked_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully',
            'data' => $block
        ], 200);
    }

    /**
     * Unblock a user
     */
    public function unblockUser(Request $request, $userId)
    {
        $block = BlockedUser::where('blocker_id', Auth::id())
            ->where('blocked_id', $userId)
            ->active()
            ->first();

        if (!$block) {
            return response()->json([
                'success' => false,
                'message' => 'User is not blocked or already unblocked'
            ], 404);
        }

        $block->update([
            'unblocked_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully',
            'data' => $block
        ], 200);
    }

    /**
     * Get blocked users list
     */
    public function getBlockedUsers()
    {
        $blockedUsers = BlockedUser::with('blocked')
            ->where('blocker_id', Auth::id())
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $blockedUsers
        ], 200);
    }

    /**
     * Get blocking history
     */
    public function getBlockHistory()
    {
        $history = BlockedUser::with(['blocker', 'blocked'])
            ->where(function ($query) {
                $query->where('blocker_id', Auth::id())
                    ->orWhere('blocked_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ], 200);
    }

    /**
     * Check if user is blocked
     */
    public function checkBlockStatus($userId)
    {
        $isBlocked = BlockedUser::where('blocker_id', Auth::id())
            ->where('blocked_id', $userId)
            ->active()
            ->exists();

        $hasBlockedYou = BlockedUser::where('blocker_id', $userId)
            ->where('blocked_id', Auth::id())
            ->active()
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'you_blocked_them' => $isBlocked,
                'they_blocked_you' => $hasBlockedYou,
                'is_blocked' => $isBlocked || $hasBlockedYou
            ]
        ], 200);
    }
}
