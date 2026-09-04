<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeachingAssignmentController extends Controller
{
    public function index(): JsonResponse
    {
        $assignments = TeachingAssignment::with([
            'teacher.user',
            'subject',
            'section',
        ])
            ->latest()
            ->get();

        return response()->json(['data' => $assignments]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $this->ensureUniqueAssignment($validated);

        $assignment = TeachingAssignment::create($validated);
        $this->syncTeacherSections($assignment->teacher_id);

        return response()->json([
            'message' => 'Teaching assignment created successfully.',
            'data' => $assignment->load(['teacher.user', 'subject', 'section']),
        ], 201);
    }

    public function update(Request $request, TeachingAssignment $teachingAssignment): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['sometimes', 'exists:teachers,id'],
            'subject_id' => ['sometimes', 'exists:subjects,id'],
            'section_id' => ['sometimes', 'exists:sections,id'],
        ]);

        $payload = array_merge([
            'teacher_id' => $teachingAssignment->teacher_id,
            'subject_id' => $teachingAssignment->subject_id,
            'section_id' => $teachingAssignment->section_id,
        ], $validated);

        $this->ensureUniqueAssignment($payload, $teachingAssignment->id);

        $previousTeacherId = $teachingAssignment->teacher_id;
        $teachingAssignment->update($validated);

        $this->syncTeacherSections($previousTeacherId);
        if ($teachingAssignment->teacher_id !== $previousTeacherId) {
            $this->syncTeacherSections($teachingAssignment->teacher_id);
        }

        return response()->json([
            'message' => 'Teaching assignment updated successfully.',
            'data' => $teachingAssignment->fresh()->load(['teacher.user', 'subject', 'section']),
        ]);
    }

    public function destroy(TeachingAssignment $teachingAssignment): JsonResponse
    {
        $teacherId = $teachingAssignment->teacher_id;
        $teachingAssignment->delete();
        $this->syncTeacherSections($teacherId);

        return response()->json(['message' => 'Teaching assignment deleted successfully.']);
    }

    public function storeBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.subject_id' => ['required', 'exists:subjects,id'],
            'assignments.*.section_id' => ['required', 'exists:sections,id'],
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($validated['assignments'] as $assignment) {
            $payload = [
                'teacher_id' => $validated['teacher_id'],
                'subject_id' => $assignment['subject_id'],
                'section_id' => $assignment['section_id'],
            ];

            $exists = TeachingAssignment::query()
                ->where('teacher_id', $payload['teacher_id'])
                ->where('subject_id', $payload['subject_id'])
                ->where('section_id', $payload['section_id'])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            TeachingAssignment::create($payload);
            $created++;
        }

        $this->syncTeacherSections($validated['teacher_id']);

        return response()->json([
            'message' => "Added {$created} class(es)".($skipped ? ", skipped {$skipped} duplicate(s)" : '').'.',
            'data' => ['created' => $created, 'skipped' => $skipped],
        ], 201);
    }

    public function syncForTeacher(Request $request, Teacher $teacher): JsonResponse
    {
        $validated = $request->validate([
            'assignments' => ['present', 'array'],
            'assignments.*.subject_id' => ['required', 'exists:subjects,id'],
            'assignments.*.section_id' => ['required', 'exists:sections,id'],
        ]);

        $uniqueAssignments = collect($validated['assignments'])
            ->unique(fn ($row) => $row['subject_id'].'-'.$row['section_id'])
            ->values();

        $teacher->teachingAssignments()->delete();

        foreach ($uniqueAssignments as $assignment) {
            TeachingAssignment::create([
                'teacher_id' => $teacher->id,
                'subject_id' => $assignment['subject_id'],
                'section_id' => $assignment['section_id'],
            ]);
        }

        $this->syncTeacherSections($teacher->id);

        $assignments = TeachingAssignment::with(['teacher.user', 'subject', 'section'])
            ->where('teacher_id', $teacher->id)
            ->get();

        return response()->json([
            'message' => 'Teacher classes updated successfully.',
            'data' => $assignments,
        ]);
    }

    private function ensureUniqueAssignment(array $payload, ?int $ignoreId = null): void
    {
        $exists = TeachingAssignment::query()
            ->where('teacher_id', $payload['teacher_id'])
            ->where('subject_id', $payload['subject_id'])
            ->where('section_id', $payload['section_id'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'assignment' => ['This teacher is already assigned to teach this subject in this section.'],
            ]);
        }
    }

    private function syncTeacherSections(int $teacherId): void
    {
        $teacher = Teacher::find($teacherId);

        if (! $teacher) {
            return;
        }

        $sectionIds = TeachingAssignment::query()
            ->where('teacher_id', $teacherId)
            ->pluck('section_id')
            ->unique()
            ->values()
            ->all();

        $teacher->sections()->sync($sectionIds);
    }
}
