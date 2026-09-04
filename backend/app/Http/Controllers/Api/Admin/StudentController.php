<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function index(): JsonResponse
    {
        $students = Student::with(['user', 'section', 'enrollments.teachingAssignment.subject'])
            ->withCount('enrollments')
            ->latest()
            ->get();

        return response()->json(['data' => $students]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'student_number' => ['required', 'string', 'unique:students,student_number'],
            'grade_level' => ['required', 'string', 'max:50'],
            'section_id' => ['required', 'exists:sections,id'],
            'student_type' => ['nullable', 'in:regular,irregular'],
            'parent_email' => ['required', 'email'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'student',
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => $validated['student_number'],
            'grade_level' => $validated['grade_level'],
            'section_id' => $validated['section_id'],
            'student_type' => $validated['student_type'] ?? 'regular',
            'parent_email' => $validated['parent_email'],
            'qr_token' => Str::uuid()->toString(),
        ]);

        return response()->json([
            'message' => 'Student created successfully.',
            'data' => $student->load(['user', 'section', 'enrollments.teachingAssignment.subject']),
        ], 201);
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'data' => $student->load(['user', 'section', 'attendanceRecords.classSession.subject']),
        ]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$student->user_id],
            'password' => ['sometimes', 'string', 'min:8'],
            'student_number' => ['sometimes', 'string', 'unique:students,student_number,'.$student->id],
            'grade_level' => ['sometimes', 'string', 'max:50'],
            'section_id' => ['sometimes', 'exists:sections,id'],
            'student_type' => ['sometimes', 'in:regular,irregular'],
            'parent_email' => ['sometimes', 'email'],
        ]);

        if (isset($validated['name']) || isset($validated['email']) || isset($validated['password'])) {
            $student->user->update(array_filter([
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'] ?? null,
            ]));
        }

        $student->update(collect($validated)->only([
            'student_number', 'grade_level', 'section_id', 'student_type', 'parent_email',
        ])->filter()->all());

        return response()->json([
            'message' => 'Student updated successfully.',
            'data' => $student->fresh()->load(['user', 'section', 'enrollments.teachingAssignment.subject']),
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $user = $student->user;
        $student->delete();
        $user?->delete();

        return response()->json(['message' => 'Student deleted successfully.']);
    }
}
