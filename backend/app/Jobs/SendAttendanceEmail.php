<?php

namespace App\Jobs;

use App\Mail\AttendanceNotification;
use App\Models\ClassSession;
use App\Models\Student;
use App\Services\GmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAttendanceEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Student $student,
        public ClassSession $session,
        public string $status,
    ) {}

    public function handle(GmailService $gmailService): void
    {
        $this->session->loadMissing(['subject', 'section', 'teacher']);

        $subject = match ($this->status) {
            'absent' => 'GAFS A-Watch: Absence Notification',
            'late' => 'GAFS A-Watch: Late Arrival Notification',
            default => 'GAFS A-Watch: Attendance Notification',
        };

        $mailable = new AttendanceNotification(
            student: $this->student,
            session: $this->session,
            status: $this->status,
        );

        $recipient = $this->student->parent_email;

        $gmailService->send(
            recipientEmail: $recipient,
            subject: $subject,
            body: $mailable->renderBody(),
            mailable: $mailable,
        );
    }
}
