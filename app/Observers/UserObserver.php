<?php

namespace App\Observers;

use App\Models\PrcPosition;
use App\Models\PrcUserType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        //
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        Log::info("Updated user in Zapier with id: " . $user->id);
        $type = PrcUserType::where('id', $user->type)->first();
        $position = PrcPosition::where('id', $user->position)->first();
        if (!empty($type)){
            $user->type_name = $type->type_name;
        }
        if (!empty($position)){
            $user->position_name = $position->position_name;
        }
        $user->status_value = $user->status;
        $this->sendUserZapier($user);
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function saved(User $user)
    {
        //
    }

    function sendUserZapier($user) 
    {
        if (!empty(config('services.zapier.user_webhook'))){
            Http::post(
                config('services.zapier.user_webhook'),
                [
                    'data' => $user
                ]
            );
        }
    }
}
