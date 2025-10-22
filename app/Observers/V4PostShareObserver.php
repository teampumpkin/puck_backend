<?php

namespace App\Observers;

use App\Models\V4PostShare;

class V4PostShareObserver
{
    /**
     * Handle the V4PostShare "created" event.
     *
     * @param  \App\Models\V4PostShare  $v4PostShare
     * @return void
     */
    public function created(V4PostShare $v4PostShare)
    {
        //
    }

    /**
     * Handle the V4PostShare "updated" event.
     *
     * @param  \App\Models\V4PostShare  $v4PostShare
     * @return void
     */
    public function updated(V4PostShare $v4PostShare)
    {
        //
    }

    /**
     * Handle the V4PostShare "deleted" event.
     *
     * @param  \App\Models\V4PostShare  $v4PostShare
     * @return void
     */
    public function deleted(V4PostShare $v4PostShare)
    {
        //
    }

    /**
     * Handle the V4PostShare "restored" event.
     *
     * @param  \App\Models\V4PostShare  $v4PostShare
     * @return void
     */
    public function restored(V4PostShare $v4PostShare)
    {
        //
    }

    /**
     * Handle the V4PostShare "force deleted" event.
     *
     * @param  \App\Models\V4PostShare  $v4PostShare
     * @return void
     */
    public function forceDeleted(V4PostShare $v4PostShare)
    {
        //
    }
}
