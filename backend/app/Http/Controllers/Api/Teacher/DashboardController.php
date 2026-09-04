<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $todaySessions = ClassSession::with(['subject', 'section'])
            ->where('teacher_id', $user->id)
            ->whereDate('session_date', $today)
            ->orderBy('start_time')
            ->get();

        $recentSessions = ClassSession::with(['subject', 'section'])
            ->where('teacher_id', $user->id)
            ->whereDate('session_date', '<', $today)
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->limit(5)
            ->get();

        $todaySessionIds = $todaySessions->pluck('id');

        $presentToday = AttendanceRecord::whereIn('class_session_id', $todaySessionIds)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $sessionIds = ClassSession::where('teacher_id', $user->id)->pluck('id');

        $records = AttendanceRecord::whereIn('class_session_id', $sessionIds)->get();
        $presentCount = $records->whereIn('status', ['present', 'late'])->count();
        $totalCount = $records->count();

        return response()->json([
            'stats' => [
                'sessions_today' => $todaySessions->count(),
                'students_present_today' => $presentToday,
                'total_sessions' => $sessionIds->count(),
                'attendance_rate' => $totalCount > 0
                    ? round(($presentCount / $totalCount) * 100, 1)
                    : 0,
            ],
            'today_sessions' => $todaySessions,
            'recent_sessions' => $recentSessions,
        ]);
    }
}
