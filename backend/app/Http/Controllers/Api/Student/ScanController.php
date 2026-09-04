<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ScanController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $student = $request->user()->student;

        if (! $student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        try {
            $record = $this->attendanceService->recordScan($student, $validated['token']);

            return response()->json([
                'message' => 'Attendance recorded successfully.',
                'data' => $record->load(['classSession.subject', 'classSession.section']),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Invalid or expired QR code.'], 422);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
