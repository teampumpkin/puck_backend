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
            case 'development':
                $original_prefix = '[DEV]';
                break;
            case 'dev':
                $original_prefix = '[INT-QA]';
                break;
            case '':
                $original_prefix = '[EXT-QA]';
                break;
            default:
                $original_prefix = '';
                break;
        }

        $prefix = env('PREFIX_EMAIL', $original_prefix);

        $event->message->setSubject($prefix . ' ' . $event->message->getSubject())->addBcc(env('BBC_EMAIL')); // you can pass an array as well
    }
}
