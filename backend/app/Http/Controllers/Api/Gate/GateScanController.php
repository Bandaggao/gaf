<?php

namespace App\Http\Controllers\Api\Gate;

use App\Http\Controllers\Controller;
use App\Models\GateEntry;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GateScanController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = $validated['token'];
        $today = now()->toDateString();

        // Find student by daily QR token (valid for today only)
        $student = Student::query()
            ->where('daily_qr_token', $token)
            ->where('daily_qr_date', $today)
            ->with('user')
            ->first();

        if (! $student) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Invalid or expired QR code.',
            ], 422);
        }

        // Idempotent: check if already scanned today
        $existing = GateEntry::query()
            ->where('student_id', $student->id)
            ->where('scan_date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'already_scanned',
                'message' => 'Already recorded for today.',
                'data' => [
                    'student_name' => $student->user?->name,
                    'student_number' => $student->student_number,
                    'first_scanned_at' => $existing->scanned_at,
                ],
            ]);
        }

        $entry = GateEntry::create([
            'student_id' => $student->id,
            'scan_date' => $today,
            'scanned_at' => now(),
            'qr_token_used' => $token,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Gate entry recorded.',
            'data' => [
                'student_name' => $student->user?->name,
                'student_number' => $student->student_number,
                'scanned_at' => $entry->scanned_at,
            ],
        ], 201);
    }
}
