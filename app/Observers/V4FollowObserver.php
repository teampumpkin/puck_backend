<?php

namespace App\Observers;

use App\Models\V4User;
use App\Models\V4Follow;
use App\Models\V4FollowHistory;
use Illuminate\Support\Facades\Log;

class V4FollowObserver
{
    /**
     * Handle the V4Follow "created" event.
     *
     * @param  \App\Models\V4Follow  $v4Follow
     * @return void
     */
    public function created(V4Follow $v4Follow)
    {
        try {
            // Make sure we only insert allowed enum values in `action`
            V4FollowHistory::create([
                'follower_id' => $v4Follow->follower_id,
                'following_id' => $v4Follow->following_id,
                'action' => $this->getValidAction($v4Follow->status),
            ]);

            if ($v4Follow->status === 'accepted') {
                $this->incrementFollowCounts($v4Follow);
            }
        } catch (\Throwable $e) {
            Log::error('V4FollowObserver created() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'follower_id' => $v4Follow->follower_id,
                'following_id' => $v4Follow->following_id,
            ]);
        }
    }

    /**
     * Handle the V4Follow "updated" event.
     *
     * @param  \App\Models\V4Follow  $v4Follow
     * @return void
     */
    public function updated(V4Follow $v4Follow)
    {
        try {
            // If a pending follow request becomes accepted
            if ($v4Follow->isDirty('status') && $v4Follow->status === 'accepted') {
                $this->incrementFollowCounts($v4Follow);

                V4FollowHistory::create([
                    'follower_id' => $v4Follow->follower_id,
                    'following_id' => $v4Follow->following_id,
                    'action' => $this->getValidAction($v4Follow->status),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('V4FollowObserver updated() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'follower_id' => $v4Follow->follower_id,
                'following_id' => $v4Follow->following_id,
            ]);
        }
    }

    /**
     * Handle the V4Follow "deleted" event.
     *
     * @param  \App\Models\V4Follow  $v4Follow
     * @return void
     */
    public function deleted(V4Follow $v4Follow)
    {
        try {
            $this->decrementFollowCounts($v4Follow);

            V4FollowHistory::create([
                'follower_id' => $v4Follow->follower_id,
                'following_id' => $v4Follow->following_id,
                'action' => $this->getValidAction($v4Follow->status), // 'unfollow' can be mapped to 'rejected'
            ]);
        } catch (\Throwable $e) {
            Log::error('V4FollowObserver deleted() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'follower_id' => $v4Follow->follower_id,
                'following_id' => $v4Follow->following_id,
            ]);
        }
    }

    /**
     * Handle the V4Follow "restored" event.
     *
     * @param  \App\Models\V4Follow  $v4Follow
     * @return void
     */
    public function restored(V4Follow $v4Follow)
    {
        try {
            $this->incrementFollowCounts($v4Follow);

            V4FollowHistory::create([
                'follower_id' => $v4Follow->follower_id,
                'following_id' => $v4Follow->following_id,
                'action' => $this->getValidAction($v4Follow->status),
            ]);
        } catch (\Throwable $e) {
            Log::error('V4FollowObserver restored() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle the V4Follow "force deleted" event.
     *
     * @param  \App\Models\V4Follow  $v4Follow
     * @return void
     */
    public function forceDeleted(V4Follow $v4Follow)
    {
        //
    }

    private function getValidAction($status)
    {
        $validActions = ['pending', 'accepted', 'rejected', 'blocked'];

        // We map 'requested' to 'pending' as a valid action
        if ($status === 'pending') {
            return 'pending';  // You can adjust this if 'requested' should be treated differently
        }

        // Return status if it matches a valid action, otherwise default to 'rejected'
        return in_array($status, $validActions) ? $status : 'rejected';
    }

    /**
     * Increment follower & following counts.
     */
    protected function incrementFollowCounts(V4Follow $v4Follow): void
    {
        V4User::where('id', $v4Follow->follower_id)->increment('followings_count');
        V4User::where('id', $v4Follow->following_id)->increment('followers_count');
    }

    /**
     * Decrement follower & following counts.
     */
    protected function decrementFollowCounts(V4Follow $v4Follow): void
    {
        V4User::where('id', $v4Follow->follower_id)
            ->where('followings_count', '>', 0)
            ->decrement('followings_count');

        V4User::where('id', $v4Follow->following_id)
            ->where('followers_count', '>', 0)
            ->decrement('followers_count');
    }
}
