<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AutoReplyForOneOnOneConsultationSignUpMail
 * @package App\Mail
 */
class AutoReplyForOneOnOneConsultationSignUpMail extends Mailable
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

        return $this->subject('Thank You for Ordering a Puck Recruiter One-on-One Consultation')
            ->view('emails.auto-reply-for-one-on-one-consultation-sign-up');
    }
}
