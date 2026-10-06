<?php

namespace App\Services;

use App\Jobs\SendAttendanceEmail;
use App\Jobs\SendGateArrivalEmail;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\GateEntry;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceService
{
    // -------------------------------------------------------------------------
    // Daily student QR token
    // -------------------------------------------------------------------------

    /**
     * Return the student's daily QR token, regenerating it if it's from a previous day.
     */
    public function getOrCreateDailyToken(Student $student): string
    {
        $today = now()->toDateString();

        if ($student->daily_qr_date?->toDateString() === $today && $student->daily_qr_token) {
            return $student->daily_qr_token;
        }

        // Generate a new unique token for today
        do {
            $token = Str::uuid()->toString();
        } while (Student::where('daily_qr_token', $token)->exists());

        $student->update([
            'daily_qr_token' => $token,
            'daily_qr_date' => $today,
        ]);

        return $token;
    }

    // -------------------------------------------------------------------------
    // Gate entry (hardware scanner)
    // -------------------------------------------------------------------------

    /**
     * Record a gate entry for the student whose daily token matches.
     * Returns ['entry' => GateEntry|null, 'status' => 'ok'|'already_scanned'|'invalid'].
     */
    public function recordGateEntry(string $token): array
    {
        $today = now()->toDateString();

        $student = Student::query()
            ->where('daily_qr_token', $token)
            ->whereDate('daily_qr_date', $today)
            ->with('user')
            ->first();

        if (! $student) {
            return ['entry' => null, 'status' => 'invalid'];
        }

        $existing = GateEntry::query()
            ->where('student_id', $student->id)
            ->whereDate('scan_date', $today)
            ->first();

        if ($existing) {
            return ['entry' => $existing, 'status' => 'already_scanned', 'student' => $student];
        }

        $entry = GateEntry::create([
            'student_id' => $student->id,
            'scan_date' => $today,
            'scanned_at' => now(),
            'qr_token_used' => $token,
        ]);

        SendGateArrivalEmail::dispatch($student, $entry);

        return ['entry' => $entry, 'status' => 'ok', 'student' => $student];
    }

    // -------------------------------------------------------------------------
    // Session roster
    // -------------------------------------------------------------------------

    /**
     * Return enrolled students for a session, each annotated with:
     *  - gate_entry_today (bool)
     *  - attendance_status (present|absent|late|null)
     *  - attendance_record_id (int|null)
     */
    public function getSessionRoster(ClassSession $session): Collection
    {
        $enrollments = StudentEnrollment::query()
            ->where('teaching_assignment_id', $session->teaching_assignment_id)
            ->with(['student.user', 'student.gateEntries' => function ($q) use ($session) {
                $q->whereDate('scan_date', $session->session_date->toDateString());
            }])
            ->get();

        // Index existing attendance records by student_id
        $records = AttendanceRecord::query()
            ->where('class_session_id', $session->id)
            ->get()
            ->keyBy('student_id');

        return $enrollments->map(function ($enrollment) use ($records) {
            $student = $enrollment->student;
            $record = $records->get($student->id);

            return (object) [
                'student_id' => $student->id,
                'student_name' => $student->user?->name,
                'student_number' => $student->student_number,
                'gate_entry_today' => $student->gateEntries->isNotEmpty(),
                'gate_scanned_at' => $student->gateEntries->first()?->scanned_at,
                'attendance_status' => $record?->status,
                'attendance_record_id' => $record?->id,
                'enrollment_type' => $enrollment->enrollment_type,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Auto-finalize absent on session close
    // -------------------------------------------------------------------------

    /**
     * When a session is closed, finalize attendance for all enrolled students
     * that have no explicit record yet:
     *  - gate entry today → present
     *  - no gate entry    → absent
     */
    public function finalizeSessionAttendance(ClassSession $session): void
    {
        if (! $session->teaching_assignment_id) {
            return;
        }

        $sessionDate = $session->session_date->toDateString();

        $enrollments = StudentEnrollment::query()
            ->where('teaching_assignment_id', $session->teaching_assignment_id)
            ->with(['student.gateEntries' => function ($q) use ($sessionDate) {
                $q->whereDate('scan_date', $sessionDate);
            }])
            ->get();

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;

            $alreadyRecorded = AttendanceRecord::query()
                ->where('student_id', $student->id)
                ->where('class_session_id', $session->id)
                ->exists();

            if ($alreadyRecorded) {
                continue;
            }

            $status = $student->gateEntries->isNotEmpty() ? 'present' : 'absent';

            $record = AttendanceRecord::create([
                'student_id' => $student->id,
                'class_session_id' => $session->id,
                'status' => $status,
                'scanned_at' => $status === 'present'
                    ? $student->gateEntries->first()->scanned_at
                    : null,
            ]);

            if ($status === 'absent') {
                SendAttendanceEmail::dispatch($student, $session, 'absent');
            }
        }

        $session->update(['absent_processed' => true]);
    }

    // -------------------------------------------------------------------------
    // Cron / scheduled job: mark absent for expired sessions not yet closed
    // -------------------------------------------------------------------------

    public function markAbsentForExpiredSessions(): int
    {
        $processedCount = 0;

        $sessions = ClassSession::query()
            ->where('expires_at', '<=', now())
            ->where('absent_processed', false)
            ->whereNull('closed_at')
            ->whereNotNull('teaching_assignment_id')
            ->get();

        foreach ($sessions as $session) {
            $this->finalizeSessionAttendance($session);
            $processedCount++;
        }

        return $processedCount;
    }

    // -------------------------------------------------------------------------
    // QR SVG helper
    // -------------------------------------------------------------------------

    public function qrCodeSvg(string $token): string
    {
        return (string) QrCode::size(300)->margin(2)->generate($token);
    }
}
