<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\ParentModel;
use App\Services\GmailService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WeeklySummaryCommand extends Command
{
    protected $signature = 'attendance:weekly-summary';

    protected $description = 'Send weekly attendance summary emails to parents';

    public function handle(GmailService $gmailService): int
    {
        $weekStart = Carbon::now()->subWeek()->startOfWeek();
        $weekEnd = Carbon::now()->subWeek()->endOfWeek();

        $parents = ParentModel::with(['students.user', 'students.attendanceRecords.classSession.subject'])
            ->get();

        $sentCount = 0;

        foreach ($parents as $parent) {
            $lines = [];
            $lines[] = "Weekly Attendance Summary ({$weekStart->format('M j')} – {$weekEnd->format('M j, Y')})";
            $lines[] = str_repeat('-', 50);

            foreach ($parent->students as $student) {
                $records = AttendanceRecord::query()
                    ->where('student_id', $student->id)
                    ->whereHas('classSession', function ($query) use ($weekStart, $weekEnd) {
                        $query->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()]);
                    })
                    ->with(['classSession.subject'])
                    ->get();

                $present = $records->where('status', 'present')->count();
                $late = $records->where('status', 'late')->count();
                $absent = $records->where('status', 'absent')->count();
                $total = $records->count();

                $lines[] = '';
                $lines[] = "Student: {$student->user->name} ({$student->student_number})";
                $lines[] = "  Present: {$present} | Late: {$late} | Absent: {$absent} | Total sessions: {$total}";

                foreach ($records as $record) {
                    $date = $record->classSession->session_date->format('M j');
                    $subject = $record->classSession->subject->name ?? 'Unknown';
                    $lines[] = "  • {$date} — {$subject}: ".strtoupper($record->status);
                }
            }

            $body = implode("\n", $lines);
            $subject = 'GAFS A-Watch: Weekly Attendance Summary';

            $recipient = $parent->email ?: $parent->user->email;

            if (! $recipient) {
                continue;
            }

            $gmailService->send($recipient, $subject, $body);
            $sentCount++;
        }

        $this->info("Sent {$sentCount} weekly summary email(s).");

        return self::SUCCESS;
    }
}
