<?php

namespace App\Services;

use App\Models\EmailLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class GmailService
{
    public function send(string $recipientEmail, string $subject, string $body, ?Mailable $mailable = null): EmailLog
    {
        $log = EmailLog::create([
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
        ]);

        try {
            if ($mailable) {
                Mail::to($recipientEmail)->send($mailable);
            } else {
                Mail::raw($body, function ($message) use ($recipientEmail, $subject) {
                    $message->to($recipientEmail)->subject($subject);
                });
            }

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $log->update(['status' => 'failed']);

            throw $exception;
        }

        return $log;
    }
}
