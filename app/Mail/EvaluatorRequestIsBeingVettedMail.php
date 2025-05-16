<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class EvaluatorRequestIsBeingVettedMail
 * @package App\Mail
 */
class EvaluatorRequestIsBeingVettedMail extends Mailable
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
        return $this->subject('Your Request is Now Being Reviewed to Become a Puck Recruiter Evaluator')
            ->view('emails.evaluator-request-is-being-vetted');
    }
}
