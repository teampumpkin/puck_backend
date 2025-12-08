<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class MessageSendingListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MessageSending $event)
    {
        $domain = env('APP_DOMAIN');
        $original_prefix = '';

        switch ($domain) {
            case 'production':
                $original_prefix = '';
                break;
            default:
                $original_prefix = '[DEV]';
                break;
        }

        $event->message->setSubject($original_prefix . ' ' . $event->message->getSubject());
    }
}
