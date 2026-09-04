<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $records = AttendanceRecord::with(['classSession.subject', 'classSession.section'])
            ->where('student_id', $student->id)
            ->latest()
            ->limit(20)
            ->get();

        $allRecords = AttendanceRecord::where('student_id', $student->id)->get();
        $presentCount = $allRecords->whereIn('status', ['present', 'late'])->count();
        $totalCount = $allRecords->count();

        return response()->json([
            'student' => $student->load(['user', 'section']),
            'stats' => [
                'present' => $allRecords->where('status', 'present')->count(),
                'late' => $allRecords->where('status', 'late')->count(),
                'absent' => $allRecords->where('status', 'absent')->count(),
                'total' => $totalCount,
                'rate' => $totalCount > 0
                    ? round(($presentCount / $totalCount) * 100, 1)
                    : 0,
            ],
            'recent' => $records->map(fn ($record) => [
                'id' => $record->id,
                'date' => $record->classSession?->session_date?->format('Y-m-d'),
                'subject' => $record->classSession?->subject?->name,
                'section' => $record->classSession?->section?->name,
                'status' => $record->status,
                'scanned_at' => $record->scanned_at,
            ]),
        ]);
    }
}
