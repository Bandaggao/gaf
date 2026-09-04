<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function attendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'status' => ['nullable', 'in:present,absent,late'],
        ]);

        $records = $this->attendanceQuery($validated)->get();

        $summary = [
            'total' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'data' => $records,
        ]);
    }

    public function attendancePdf(Request $request): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'status' => ['nullable', 'in:present,absent,late'],
        ]);

        $records = $this->attendanceQuery($validated)->get();

        $pdf = Pdf::loadView('reports.attendance', [
            'records' => $records,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ]);

        return $pdf->download('attendance-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function weeklySummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? now()->subWeek()->startOfWeek()->toDateString();
        $to = $validated['to'] ?? now()->subWeek()->endOfWeek()->toDateString();

        $records = AttendanceRecord::with(['student.user', 'classSession.subject', 'classSession.section'])
            ->whereHas('classSession', fn ($q) => $q->whereBetween('session_date', [$from, $to]))
            ->get()
            ->groupBy(fn ($record) => $record->student_id);

        $summary = $records->map(function ($studentRecords) {
            return [
                'student' => $studentRecords->first()->student->load('user'),
                'present' => $studentRecords->where('status', 'present')->count(),
                'late' => $studentRecords->where('status', 'late')->count(),
                'absent' => $studentRecords->where('status', 'absent')->count(),
                'total' => $studentRecords->count(),
            ];
        })->values();

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'data' => $summary,
        ]);
    }

    private function attendanceQuery(array $filters)
    {
        return AttendanceRecord::with([
            'student.user',
            'classSession.subject',
            'classSession.section',
        ])
            ->when($filters['from'] ?? null, function ($query, $from) {
                $query->whereHas('classSession', fn ($q) => $q->whereDate('session_date', '>=', $from));
            })
            ->when($filters['to'] ?? null, function ($query, $to) {
                $query->whereHas('classSession', fn ($q) => $q->whereDate('session_date', '<=', $to));
            })
            ->when($filters['section_id'] ?? null, function ($query, $sectionId) {
                $query->whereHas('classSession', fn ($q) => $q->where('section_id', $sectionId));
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest();
    }
}
