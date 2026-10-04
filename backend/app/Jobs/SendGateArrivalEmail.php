<?php

namespace App\Jobs;

use App\Mail\GateArrivalNotification;
use App\Models\GateEntry;
use App\Models\Student;
use App\Services\GmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendGateArrivalEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Student $student,
        public GateEntry $entry,
    ) {}

    public function handle(GmailService $gmailService): void
    {
        $recipient = $this->student->parent_email;

        if (empty($recipient)) {
            return;
        }

        $mailable = new GateArrivalNotification(
            student: $this->student,
            entry: $this->entry,
        );

        $gmailService->send(
            recipientEmail: $recipient,
            subject: 'GAFS A-Watch: Student Arrived at School',
            body: $mailable->renderBody(),
            mailable: $mailable,
        );
    }
}
