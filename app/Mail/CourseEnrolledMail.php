<?php

namespace App\Mail;

use App\Models\CourseEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseEnrolledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $enrollment;
    public $recipientType;

    /**
     * Create a new message instance.
     */
    public function __construct(CourseEnrollment $enrollment, $recipientType = 'student')
    {
        $this->enrollment = $enrollment->load(['course.subject', 'student', 'teacher']);
        $this->recipientType = $recipientType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->recipientType === 'teacher' 
            ? 'New Student Enrolled in Your Course'
            : 'Course Enrollment Confirmed';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->recipientType === 'teacher' 
            ? 'emails.course-enrolled-teacher'
            : 'emails.course-enrolled-student';

        return new Content(
            view: $view,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
