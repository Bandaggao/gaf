<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    public function index(Student $student): JsonResponse
    {
        $enrollments = $student->enrollments()
            ->with(['teachingAssignment.subject', 'teachingAssignment.section', 'teachingAssignment.teacher.user'])
            ->get();

        return response()->json(['data' => $enrollments]);
    }

    public function store(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'teaching_assignment_ids' => ['required', 'array', 'min:1'],
            'teaching_assignment_ids.*' => ['exists:teaching_assignments,id'],
            'enrollment_type' => ['nullable', 'in:regular,irregular'],
        ]);

        $type = $validated['enrollment_type'] ?? ($student->student_type === 'irregular' ? 'irregular' : 'regular');

        $syncData = collect($validated['teaching_assignment_ids'])
            ->mapWithKeys(fn ($id) => [$id => ['enrollment_type' => $type]])
            ->all();

        $student->teachingAssignments()->sync($syncData);

        $enrollments = $student->enrollments()
            ->with(['teachingAssignment.subject', 'teachingAssignment.section', 'teachingAssignment.teacher.user'])
            ->get();

        return response()->json([
            'message' => 'Enrollment saved successfully.',
            'data' => $enrollments,
        ]);
    }

    public function destroy(Student $student, StudentEnrollment $enrollment): JsonResponse
    {
        if ($enrollment->student_id !== $student->id) {
            abort(404);
        }

        $enrollment->delete();

        return response()->json(['message' => 'Enrollment removed successfully.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $section = Section::findOrFail($validated['section_id']);
        $assignments = TeachingAssignment::where('section_id', $section->id)->pluck('id');
        $students = Student::where('section_id', $section->id)->get();

        $count = 0;

        DB::transaction(function () use ($students, $assignments, &$count) {
            foreach ($students as $student) {
                foreach ($assignments as $assignmentId) {
                    $enrollment = StudentEnrollment::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'teaching_assignment_id' => $assignmentId,
                        ],
                        ['enrollment_type' => 'regular'],
                    );

                    if ($enrollment->wasRecentlyCreated) {
                        $count++;
                    }
                }
            }
        });

        return response()->json([
            'message' => "Bulk enrollment completed. {$count} new enrollment(s) created.",
            'data' => [
                'section' => $section->name,
                'students' => $students->count(),
                'classes' => $assignments->count(),
                'new_enrollments' => $count,
            ],
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            throw ValidationException::withMessages(['file' => ['CSV file is empty.']]);
        }

        $header = array_map(fn ($col) => strtolower(trim($col)), $header);
        $required = ['student_number', 'subject', 'teacher_email', 'block_name'];
        $missing = array_diff($required, $header);

        if ($missing) {
            throw ValidationException::withMessages([
                'file' => ['CSV must include columns: '.implode(', ', $required)],
            ]);
        }

        $results = ['imported' => 0, 'skipped' => 0, 'errors' => []];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $data = array_combine($header, array_pad($row, count($header), ''));

            $student = Student::where('student_number', trim($data['student_number']))->first();
            if (! $student) {
                $results['errors'][] = "Row {$rowNumber}: Student not found ({$data['student_number']}).";
                continue;
            }

            $teacherUser = User::where('email', trim($data['teacher_email']))->where('role', 'teacher')->first();
            if (! $teacherUser) {
                $results['errors'][] = "Row {$rowNumber}: Teacher not found ({$data['teacher_email']}).";
                continue;
            }

            $teacher = Teacher::where('user_id', $teacherUser->id)->first();
            $section = Section::where('name', trim($data['block_name']))->first();

            if (! $section) {
                $results['errors'][] = "Row {$rowNumber}: Program block not found ({$data['block_name']}).";
                continue;
            }

            $assignment = TeachingAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->where('section_id', $section->id)
                ->whereHas('subject', fn ($q) => $q->where('name', trim($data['subject'])))
                ->first();

            if (! $assignment) {
                $results['errors'][] = "Row {$rowNumber}: Class not found for {$data['subject']} / {$data['teacher_email']} / {$data['block_name']}.";
                continue;
            }

            $enrollment = StudentEnrollment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'teaching_assignment_id' => $assignment->id,
                ],
                ['enrollment_type' => $student->student_type === 'irregular' ? 'irregular' : 'regular'],
            );

            if ($enrollment->wasRecentlyCreated) {
                $results['imported']++;
            } else {
                $results['skipped']++;
            }
        }

        fclose($handle);

        return response()->json([
            'message' => 'CSV import completed.',
            'data' => $results,
        ]);
    }

    public function options(): JsonResponse
    {
        $assignments = TeachingAssignment::with(['subject', 'section', 'teacher.user'])
            ->orderBy('section_id')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'label' => sprintf(
                    '%s — %s (%s)',
                    $a->subject?->name,
                    $a->teacher?->user?->name,
                    $a->section?->name,
                ),
                'subject_id' => $a->subject_id,
                'section_id' => $a->section_id,
                'teacher_id' => $a->teacher_id,
            ]);

        return response()->json(['data' => $assignments]);
    }
}
