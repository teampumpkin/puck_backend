<?php

namespace App\Observers;

use App\Models\V4Post;
use App\Models\V4PostComment;
use App\Models\V4PostCommentHistory;

class V4PostCommentObserver
{
    /**
     * Handle the V4PostComment "created" event.
     *
     * @param  \App\Models\V4PostComment  $v4PostComment
     * @return void
     */
    public function created(V4PostComment $v4PostComment)
    {
        $post = V4Post::find($v4PostComment->post_id);
        if ($post) {
            $post->increment('comments_count');
        }

        V4PostCommentHistory::create([
            'user_id' => $v4PostComment->user_id,
            'post_id' => $v4PostComment->post_id,
            'comment_id' => $v4PostComment->id,
            'action' => 'created',
        ]);
    }

    /**
     * Handle the V4PostComment "updated" event.
     *
     * @param  \App\Models\V4PostComment  $v4PostComment
     * @return void
     */
    public function updated(V4PostComment $v4PostComment)
    {
        if ($v4PostComment->isDirty('body')) {
            V4PostCommentHistory::create([
                'user_id' => $v4PostComment->user_id,
                'post_id' => $v4PostComment->post_id,
                'comment_id' => $v4PostComment->id,
                'action' => 'edited',
                'old_body' => $v4PostComment->getOriginal('body'), // This is correct
            ]);
        }
    }

    /**
     * Handle the V4PostComment "deleted" event.
     *
     * @param  \App\Models\V4PostComment  $v4PostComment
     * @return void
     */
    public function deleted(V4PostComment $v4PostComment)
    {
        $post = V4Post::find($v4PostComment->post_id);
        if ($post && $post->comments_count > 0) {
            $post->decrement('comments_count');
        }

        V4PostCommentHistory::create([
            'user_id' => $v4PostComment->user_id,
            'post_id' => $v4PostComment->post_id,
            'comment_id' => $v4PostComment->id,
            'action' => 'deleted',
        ]);
    }

    /**
     * Handle the V4PostComment "restored" event.
     *
     * @param  \App\Models\V4PostComment  $v4PostComment
     * @return void
     */
    public function restored(V4PostComment $v4PostComment)
    {
        //
    }

    /**
     * Handle the V4PostComment "force deleted" event.
     *
     * @param  \App\Models\V4PostComment  $v4PostComment
     * @return void
     */
    public function forceDeleted(V4PostComment $v4PostComment)
    {
        //
    }
}
