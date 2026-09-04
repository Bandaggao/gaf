<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = Section::withCount('students')->latest()->get();

        return response()->json(['data' => $sections]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'grade_level' => ['required', 'string', 'max:50'],
        ]);

        $section = Section::create($validated);

        return response()->json([
            'message' => 'Section created successfully.',
            'data' => $section,
        ], 201);
    }

    public function show(Section $section): JsonResponse
    {
        return response()->json([
            'data' => $section->load(['students.user', 'teachers.user']),
        ]);
    }

    public function update(Request $request, Section $section): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'grade_level' => ['sometimes', 'string', 'max:50'],
        ]);

        $section->update($validated);

        return response()->json([
            'message' => 'Section updated successfully.',
            'data' => $section->fresh(),
        ]);
    }

    public function destroy(Section $section): JsonResponse
    {
        $section->delete();

        return response()->json(['message' => 'Section deleted successfully.']);
    }
}
