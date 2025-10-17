<?php

namespace App\Observers;

use App\Models\V4Follow;
use App\Models\V4FollowHistory;

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
        V4FollowHistory::create([
            'follower_id' => $v4Follow->follower_id,
            'following_id' => $v4Follow->following_id,
            'action' => $v4Follow->status === 'pending' ? 'requested' : 'follow',
        ]);
    }

    /**
     * Handle the V4Follow "updated" event.
     *
     * @param  \App\Models\V4Follow  $v4Follow
     * @return void
     */
    public function updated(V4Follow $v4Follow)
    {
        if ($v4Follow->isDirty('status')) {
            V4FollowHistory::create([
                'follower_id' => $v4Follow->follower_id,
                'following_id' => $v4Follow->following_id,
                'action' => $v4Follow->status,
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
        V4FollowHistory::create([
            'follower_id' => $v4Follow->follower_id,
            'following_id' => $v4Follow->following_id,
            'action' => 'unfollow',
        ]);
    }

    /**
     * Handle the V4Follow "restored" event.
     *
     * @param  \App\Models\V4Follow  $v4Follow
     * @return void
     */
    public function restored(V4Follow $v4Follow)
    {
        //
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
}
