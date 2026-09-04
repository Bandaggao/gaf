<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $token = $this->attendanceService->getOrCreateDailyToken($student);
        $svg = $this->attendanceService->qrCodeSvg($token);

        return response()->json([
            'token' => $token,
            'valid_for' => now()->toDateString(),
            'expires_at' => now()->endOfDay()->toIso8601String(),
            'qr_svg' => $svg,
            'student_name' => $student->user?->name,
            'student_number' => $student->student_number,
        ]);
    }
}
