<?php

namespace App\Observers;

use App\Models\V4PostLike;
use App\Models\V4PostLikeHistory;

class V4PostLikeObserver
{
    /**
     * Handle the V4PostLike "created" event.
     *
     * @param  \App\Models\V4PostLike  $v4PostLike
     * @return void
     */
    public function created(V4PostLike $v4PostLike)
    {
        // Increment the post's like count
        $v4PostLike->post->increment('likes_count');

        // Record the "liked" action
        V4PostLikeHistory::create([
            'user_id' => $v4PostLike->user_id,
            'post_id' => $v4PostLike->post_id,
            'action'  => 'liked',
        ]);
    }

    /**
     * Handle the V4PostLike "updated" event.
     *
     * @param  \App\Models\V4PostLike  $v4PostLike
     * @return void
     */
    public function updated(V4PostLike $v4PostLike)
    {
        // No action needed unless you're tracking other fields
    }

    /**
     * Handle the V4PostLike "deleted" event.
     *
     * @param  \App\Models\V4PostLike  $v4PostLike
     * @return void
     */
    public function deleted(V4PostLike $v4PostLike)
    {
        // Decrement the post's like count
        $v4PostLike->post->decrement('likes_count');

        // Record the "unliked" action
        V4PostLikeHistory::create([
            'user_id' => $v4PostLike->user_id,
            'post_id' => $v4PostLike->post_id,
            'action'  => 'unliked',
        ]);
    }

    /**
     * Handle the V4PostLike "restored" event.
     *
     * @param  \App\Models\V4PostLike  $v4PostLike
     * @return void
     */
    public function restored(V4PostLike $v4PostLike)
    {
        // Increment the post's like count again
        $v4PostLike->post->increment('likes_count');

        // Record the "liked" action again
        V4PostLikeHistory::create([
            'user_id' => $v4PostLike->user_id,
            'post_id' => $v4PostLike->post_id,
            'action'  => 'liked',
        ]);
    }

    /**
     * Handle the V4PostLike "force deleted" event.
     *
     * @param  \App\Models\V4PostLike  $v4PostLike
     * @return void
     */
    public function forceDeleted(V4PostLike $v4PostLike)
    {
        // Optional: treat force delete same as soft delete
        V4PostLikeHistory::create([
            'user_id' => $v4PostLike->user_id,
            'post_id' => $v4PostLike->post_id,
            'action'  => 'unliked',
        ]);
    }
}
