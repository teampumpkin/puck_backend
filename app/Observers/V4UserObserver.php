<?php

namespace App\Observers;

use App\Models\V4User;

class V4UserObserver
{
    /**
     * Handle the V4User "created" event.
     *
     * @param  \App\Models\V4User  $v4User
     * @return void
     */
    public function created(V4User $v4User)
    {
        //
    }

    /**
     * Handle the V4User "updated" event.
     *
     * @param  \App\Models\V4User  $v4User
     * @return void
     */
    public function updated(V4User $v4User)
    {
        //
    }

    /**
     * Handle the V4User "deleted" event.
     *
     * @param  \App\Models\V4User  $v4User
     * @return void
     */
    public function deleted(V4User $v4User)
    {
        //
    }

    /**
     * Handle the V4User "restored" event.
     *
     * @param  \App\Models\V4User  $v4User
     * @return void
     */
    public function restored(V4User $v4User)
    {
        //
    }

    /**
     * Handle the V4User "force deleted" event.
     *
     * @param  \App\Models\V4User  $v4User
     * @return void
     */
    public function forceDeleted(V4User $v4User)
    {
        //
    }
}
