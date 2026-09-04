<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('gafs-awatch-api')->plainTextToken;

        return response()->json([
            'user' => $this->loadUserProfile($user),
            'token' => $token,
            'dashboard_route' => $user->dashboardRoute(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->loadUserProfile($request->user()),
            'dashboard_route' => $request->user()->dashboardRoute(),
        ]);
    }

    private function loadUserProfile(User $user): User
    {
        return match ($user->role) {
            'student' => $user->load('student.section'),
            'teacher' => $user->load(['teacher.sections', 'teacher.teachingAssignments.subject', 'teacher.teachingAssignments.section']),
            'parent' => $user->load(['parentModel.students.user', 'parentModel.students.section']),
            default => $user,
        };
    }
}
