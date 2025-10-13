<?php

namespace App\Listeners;

use App\Events\InvalidFcmToken;
use App\Models\V4User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleInvalidFcmToken
{
    public function handle(InvalidFcmToken $event)
    {
        // Find users with this token and remove it
        $users = V4User::where('fcm_token', $event->token)->get();

        foreach ($users as $user) {
            $user->update(['fcm_token' => null]);
            Log::info('Removed invalid FCM token for user', [
                'user_id' => $user->id,
                'token' => $event->token
            ]);
        }
    }
}
