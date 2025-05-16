<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AcceptanceOfBecomingAPuckRecruiterEvaluatorMail
 * @package App\Mail
 */
class AcceptanceOfBecomingAPuckRecruiterEvaluatorMail extends Mailable
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
        $details = $this->details;
        return $this->subject('Your request to be a Puck Recruiter Evaluator Has Been Approved!')
            ->view('emails.acceptance-of-becoming-a-puck-recruiter-evaluator', compact('details'));
    }
}
