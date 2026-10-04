<?php

namespace App\Mail;

use App\Models\GateEntry;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GateArrivalNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public GateEntry $entry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'GAFS A-Watch: Student Arrived at School');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gate-arrival-notification',
            with: [
                'student' => $this->student,
                'entry' => $this->entry,
            ],
        );
    }

    public function renderBody(): string
    {
        return view('emails.gate-arrival-notification', [
            'student' => $this->student,
            'entry' => $this->entry,
        ])->render();
    }
}
