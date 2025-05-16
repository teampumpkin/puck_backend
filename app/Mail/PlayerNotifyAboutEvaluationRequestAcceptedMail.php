<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class PlayerNotifyAboutEvaluationRequestAcceptedMail
 * @package App\Mail
 */
class PlayerNotifyAboutEvaluationRequestAcceptedMail extends Mailable
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
        return $this->subject('Your Evaluation Request Is in Progress With Puck Recruiter!')
            ->view('emails.player-notify-for-request-accepted');
    }
}
