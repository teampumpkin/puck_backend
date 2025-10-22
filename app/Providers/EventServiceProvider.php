<?php

namespace App\Providers;

use App\Listeners\MessageSendingListener;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Auth\Events\Registered;
use App\Events\InvalidFcmToken;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Listeners\HandleInvalidFcmToken;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Models\User;
use App\Models\V4PostComment;
use App\Models\V4PostLike;
use App\Models\V4PostShare;
use App\Observers\UserObserver;
use App\Observers\V4PostLikeObserver;
use App\Observers\V4PostCommentObserver;
use App\Observers\V4PostShareObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        MessageSending::class => [
            MessageSendingListener::class
        ],
        InvalidFcmToken::class => [HandleInvalidFcmToken::class]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        User::observe(UserObserver::class);
        V4PostLike::observe(V4PostLikeObserver::class);
        V4PostComment::observe(V4PostCommentObserver::class);
        V4PostShare::observe(V4PostShareObserver::class);
    }
}
