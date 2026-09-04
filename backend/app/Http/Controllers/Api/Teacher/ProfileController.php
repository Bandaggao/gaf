<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;

        if (! $teacher) {
            return response()->json(['message' => 'Teacher profile not found.'], 404);
        }

        $assignments = $teacher->teachingAssignments()
            ->with(['subject', 'section'])
            ->get()
            ->map(fn ($assignment) => [
                'id' => $assignment->id,
                'subject' => $assignment->subject?->name,
                'section' => $assignment->section?->name,
                'grade_level' => $assignment->section?->grade_level,
            ]);

        $sections = $teacher->sections()
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['sections.id', 'sections.name', 'sections.grade_level']);

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'sections' => $sections,
            'assignments' => $assignments,
        ]);
    }
}
