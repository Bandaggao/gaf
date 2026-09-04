<?php

namespace App\Mail;

use App\Models\ClassSession;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public ClassSession $session,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->status) {
            'absent' => 'GAFS A-Watch: Absence Notification',
            'late' => 'GAFS A-Watch: Late Arrival Notification',
            default => 'GAFS A-Watch: Attendance Update',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance-notification',
            with: [
                'student' => $this->student,
                'session' => $this->session,
                'status' => $this->status,
            ],
        );
    }

    public function renderBody(): string
    {
        return view('emails.attendance-notification', [
            'student' => $this->student,
            'session' => $this->session->loadMissing(['subject', 'section']),
            'status' => $this->status,
        ])->render();
    }
}
