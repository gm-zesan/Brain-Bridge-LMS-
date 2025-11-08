<?php

namespace App\Mail;

use App\Models\LessonSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SessionBookedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $session;
    public $role;

    public function __construct(LessonSession $session, $role)
    {
        $this->session = $session;
        $this->role = $role;
    }

    public function build()
    {
        return $this->subject('Lesson Session Scheduled')
                    ->view('emails.session-booked');
    }
}
