<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(): JsonResponse
    {
        $subjects = Subject::withCount('teachingAssignments')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $subjects]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subjects,name'],
        ]);

        $subject = Subject::create($validated);

        return response()->json([
            'message' => 'Subject created successfully.',
            'data' => $subject,
        ], 201);
    }

    public function show(Subject $subject): JsonResponse
    {
        return response()->json([
            'data' => $subject->load(['teachingAssignments.teacher.user', 'teachingAssignments.section']),
        ]);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:subjects,name,'.$subject->id],
        ]);

        $subject->update($validated);

        return response()->json([
            'message' => 'Subject updated successfully.',
            'data' => $subject->fresh(),
        ]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $subject->delete();

        return response()->json(['message' => 'Subject deleted successfully.']);
    }
}
