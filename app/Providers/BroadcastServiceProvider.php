<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!env('WS_AUTH_BYPASS', false)) {
            Broadcast::routes([
                'prefix' => 'api',
                'middleware' => ['auth:v4api'],
            ]);
        }

        require base_path('routes/channels.php');
    }
}
