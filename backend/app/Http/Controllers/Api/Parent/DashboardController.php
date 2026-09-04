<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $parent = $request->user()->parentModel;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $students = $parent->students()->with(['user', 'section'])->get();
        $studentIds = $students->pluck('id');

        $records = AttendanceRecord::with(['classSession.subject', 'classSession.section'])
            ->whereIn('student_id', $studentIds)
            ->latest()
            ->limit(30)
            ->get();

        $childrenSummary = $students->map(function ($student) {
            $studentRecords = AttendanceRecord::where('student_id', $student->id)->get();

            return [
                'student' => $student,
                'stats' => [
                    'present' => $studentRecords->where('status', 'present')->count(),
                    'late' => $studentRecords->where('status', 'late')->count(),
                    'absent' => $studentRecords->where('status', 'absent')->count(),
                    'total' => $studentRecords->count(),
                ],
            ];
        });

        return response()->json([
            'parent' => $parent->load('user'),
            'children_summary' => $childrenSummary,
            'recent_attendance' => $records,
        ]);
    }
}
