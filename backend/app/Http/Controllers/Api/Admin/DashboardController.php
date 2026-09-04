<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        $todayRecords = AttendanceRecord::query()
            ->whereHas('classSession', fn ($q) => $q->whereDate('session_date', $today))
            ->get();

        $presentToday = $todayRecords->whereIn('status', ['present', 'late'])->count();
        $totalToday = $todayRecords->count();
        $attendanceRate = $totalToday > 0
            ? round(($presentToday / $totalToday) * 100, 1)
            : 0;

        return response()->json([
            'stats' => [
                'students' => Student::count(),
                'teachers' => Teacher::count(),
                'sections' => Section::count(),
                'subjects' => Subject::count(),
                'sessions_today' => ClassSession::whereDate('session_date', $today)->count(),
                'attendance_rate_today' => $attendanceRate,
            ],
            'recent_sessions' => ClassSession::with(['subject', 'section', 'teacher'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
