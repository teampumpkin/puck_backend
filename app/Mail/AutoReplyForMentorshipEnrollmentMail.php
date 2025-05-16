<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AutoReplyForMentorshipEnrollmentMail
 * @package App\Mail
 */
class AutoReplyForMentorshipEnrollmentMail extends Mailable
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
        return $this->subject('Thank You for enrolling in the Puck Recruiter Mentorship Program')
            ->view('emails.auto-reply-for-mentorship-enrollment');
    }
}
