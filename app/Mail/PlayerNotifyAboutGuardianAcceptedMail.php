<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class PlayerNotifyAboutGuardianAcceptedMail
 * @package App\Mail
 */
class PlayerNotifyAboutGuardianAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var
     */
    public $details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('You’re In! But Wait, Read About Online Safety FIRST!')
            ->view('emails.notify-child-for-approval');
    }
}
