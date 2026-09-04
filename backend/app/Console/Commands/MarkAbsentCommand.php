<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;

class MarkAbsentCommand extends Command
{
    protected $signature = 'attendance:mark-absent';

    protected $description = 'Mark students absent for expired class sessions and notify parents';

    public function handle(AttendanceService $attendanceService): int
    {
        $count = $attendanceService->markAbsentForExpiredSessions();

        $this->info("Processed {$count} absent attendance record(s).");

        return self::SUCCESS;
    }
}
