<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(): JsonResponse
    {
        $teachers = Teacher::with(['user', 'sections', 'teachingAssignments.subject', 'teachingAssignments.section'])
            ->latest()
            ->get();

        return response()->json(['data' => $teachers]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'teacher',
        ]);

        $teacher = Teacher::create(['user_id' => $user->id]);

        return response()->json([
            'message' => 'Teacher created successfully.',
            'data' => $teacher->load(['user', 'sections']),
        ], 201);
    }

    public function show(Teacher $teacher): JsonResponse
    {
        return response()->json([
            'data' => $teacher->load(['user', 'sections']),
        ]);
    }

    public function update(Request $request, Teacher $teacher): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$teacher->user_id],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if (isset($validated['name']) || isset($validated['email']) || isset($validated['password'])) {
            $teacher->user->update(array_filter([
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'] ?? null,
            ]));
        }

        return response()->json([
            'message' => 'Teacher updated successfully.',
            'data' => $teacher->fresh()->load(['user', 'sections']),
        ]);
    }

    public function destroy(Teacher $teacher): JsonResponse
    {
        $user = $teacher->user;
        $teacher->delete();
        $user?->delete();

        return response()->json(['message' => 'Teacher deleted successfully.']);
    }
}
