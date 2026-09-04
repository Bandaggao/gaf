<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->student;

        if (! $student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $student->load(['section', 'enrollments.teachingAssignment.subject', 'enrollments.teachingAssignment.section', 'enrollments.teachingAssignment.teacher.user']);

        $enrollments = $student->enrollments->map(fn ($enrollment) => [
            'id' => $enrollment->id,
            'enrollment_type' => $enrollment->enrollment_type,
            'subject' => $enrollment->teachingAssignment?->subject?->name,
            'teacher_name' => $enrollment->teachingAssignment?->teacher?->user?->name,
            'block' => $enrollment->teachingAssignment?->section?->name,
        ]);

        $classmates = $student->section
            ? $student->section->students()->count()
            : 0;

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'student' => [
                'student_number' => $student->student_number,
                'grade_level' => $student->grade_level,
                'student_type' => $student->student_type,
                'parent_email' => $student->parent_email,
                'section' => $student->section,
            ],
            'classmates' => $classmates,
            'enrollments' => $enrollments,
        ]);
    }
}
