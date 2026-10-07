<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Jobs\SendAttendanceEmail;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassSessionController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sessions = ClassSession::with(['subject', 'section', 'teachingAssignment', 'attendanceRecords'])
            ->where('teacher_id', $request->user()->id)
            ->latest('session_date')
            ->latest('start_time')
            ->get();

        return response()->json(['data' => $sessions]);
    }

    public function formOptions(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return response()->json(['classes' => []]);
        }

        $classes = TeachingAssignment::with(['subject', 'section'])
            ->where('teacher_id', $teacher->id)
            ->get()
            ->map(fn (TeachingAssignment $assignment) => [
                'id' => $assignment->id,
                'subject_id' => $assignment->subject_id,
                'subject_name' => $assignment->subject?->name,
                'section_id' => $assignment->section_id,
                'section_name' => $assignment->section?->name,
                'grade_level' => $assignment->section?->grade_level,
                'label' => sprintf(
                    '%s — %s',
                    $assignment->subject?->name,
                    $assignment->section?->name,
                ),
            ]);

        return response()->json(['classes' => $classes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teaching_assignment_id' => ['required', 'exists:teaching_assignments,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return response()->json(['message' => 'Teacher profile not found.'], 404);
        }

        $assignment = TeachingAssignment::query()
            ->where('id', $validated['teaching_assignment_id'])
            ->where('teacher_id', $teacher->id)
            ->first();

        if (! $assignment) {
            return response()->json(['message' => 'You are not assigned to this class.'], 422);
        }

        $expiresAt = Carbon::parse($validated['session_date'].' '.$validated['end_time']);

        $session = ClassSession::create([
            'teacher_id' => $request->user()->id,
            'teaching_assignment_id' => $assignment->id,
            'subject_id' => $assignment->subject_id,
            'section_id' => $assignment->section_id,
            'session_date' => $validated['session_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'session_qr_token' => (string) Str::uuid(),
            'expires_at' => $expiresAt,
            'absent_processed' => false,
        ]);

        return response()->json([
            'message' => 'Class session created successfully.',
            'data' => $session->load(['subject', 'section', 'teachingAssignment']),
        ], 201);
    }

    public function show(Request $request, ClassSession $classSession): JsonResponse
    {
        $this->authorizeSession($request, $classSession);

        return response()->json([
            'data' => $classSession->load(['subject', 'section', 'teachingAssignment', 'attendanceRecords.student.user']),
        ]);
    }

    /**
     * Return the enrolled student roster for a session, annotated with
     * gate entry and attendance status.
     */
    public function roster(Request $request, ClassSession $classSession): JsonResponse
    {
        $this->authorizeSession($request, $classSession);

        if (($classSession->isClosed() || $classSession->isExpired()) && ! $classSession->absent_processed) {
            $this->attendanceService->finalizeSessionAttendance($classSession);
        }

        $roster = $this->attendanceService->getSessionRoster($classSession);

        $summary = [
            'total' => $roster->count(),
            'at_school' => $roster->filter(fn ($r) => $r->gate_entry_today)->count(),
            'present' => $roster->filter(fn ($r) => $r->attendance_status === 'present')->count(),
            'absent' => $roster->filter(fn ($r) => $r->attendance_status === 'absent')->count(),
            'not_yet_marked' => $roster->filter(fn ($r) => $r->attendance_status === null)->count(),
        ];

        return response()->json([
            'data' => $roster->values(),
            'summary' => $summary,
        ]);
    }

    /**
     * Teacher manually sets attendance for a student in this session.
     * status: present | absent  (late removed from manual flow)
     */
    public function updateAttendance(Request $request, ClassSession $classSession): JsonResponse
    {
        $this->authorizeSession($request, $classSession);

        if ($classSession->isExpired() && ! $classSession->isClosed()) {
            return response()->json(['message' => 'Attendance cannot be changed for a finished session.'], 422);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'status' => ['required', 'in:present,absent,late'],
        ]);

        // Ensure student is enrolled in this session's teaching assignment
        $isEnrolled = $classSession->teachingAssignment
            ?->students()
            ->where('students.id', $validated['student_id'])
            ->exists();

        if (! $isEnrolled) {
            return response()->json(['message' => 'Student is not enrolled in this class.'], 422);
        }

        $record = AttendanceRecord::query()
            ->where('student_id', $validated['student_id'])
            ->where('class_session_id', $classSession->id)
            ->first();
        $shouldNotifyAbsent = $validated['status'] === 'absent'
            && $record?->status !== 'absent';

        $record = AttendanceRecord::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'class_session_id' => $classSession->id,
            ],
            [
                'status' => $validated['status'],
                'scanned_at' => $validated['status'] !== 'absent' ? now() : null,
            ]
        );

        if ($shouldNotifyAbsent) {
            $student = Student::with('user')->findOrFail($validated['student_id']);
            SendAttendanceEmail::dispatch($student, $classSession, 'absent');
        }

        return response()->json([
            'message' => 'Attendance updated.',
            'data' => $record->load('student.user'),
        ]);
    }

    public function close(Request $request, ClassSession $classSession): JsonResponse
    {
        $this->authorizeSession($request, $classSession);

        if ($classSession->isClosed()) {
            return response()->json(['message' => 'Session is already closed.'], 422);
        }

        $classSession->update(['closed_at' => now()]);

        // Auto-finalize attendance for students not yet marked
        $this->attendanceService->finalizeSessionAttendance($classSession->fresh());

        return response()->json([
            'message' => 'Class session closed successfully.',
            'data' => $classSession->fresh()->load(['subject', 'section', 'attendanceRecords.student.user']),
        ]);
    }

    public function destroy(Request $request, ClassSession $classSession): JsonResponse
    {
        $this->authorizeSession($request, $classSession);

        if ($classSession->isActive()) {
            return response()->json(['message' => 'Active sessions cannot be deleted.'], 422);
        }

        $classSession->delete();

        return response()->json(['message' => 'Class session deleted successfully.']);
    }

    private function authorizeSession(Request $request, ClassSession $session): void
    {
        if ($session->teacher_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not have access to this session.');
        }
    }
}
