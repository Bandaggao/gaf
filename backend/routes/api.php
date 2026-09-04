<?php

use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\EnrollmentController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\SectionController;
use App\Http\Controllers\Api\Admin\StudentController;
use App\Http\Controllers\Api\Admin\SubjectController;
use App\Http\Controllers\Api\Admin\TeachingAssignmentController;
use App\Http\Controllers\Api\Admin\TeacherController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Gate\GateScanController;
use App\Http\Controllers\Api\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Api\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Api\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Api\Student\QrController;
use App\Http\Controllers\Api\Teacher\ClassSessionController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Api\Teacher\ProfileController as TeacherProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Hardware gate scanner — authenticated by shared API key (X-Gate-Key header)
Route::middleware('gate.key')->prefix('gate')->group(function () {
    Route::post('/scan', [GateScanController::class, 'scan']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        Route::apiResource('students', StudentController::class);
        Route::apiResource('teachers', TeacherController::class);
        Route::apiResource('sections', SectionController::class);
        Route::apiResource('subjects', SubjectController::class);
        Route::apiResource('teaching-assignments', TeachingAssignmentController::class);
        Route::post('/teaching-assignments/bulk', [TeachingAssignmentController::class, 'storeBulk']);
        Route::put('/teachers/{teacher}/teaching-assignments', [TeachingAssignmentController::class, 'syncForTeacher']);

        Route::get('/enrollment-options', [EnrollmentController::class, 'options']);
        Route::post('/enrollments/bulk', [EnrollmentController::class, 'bulk']);
        Route::post('/enrollments/import', [EnrollmentController::class, 'import']);
        Route::get('/students/{student}/enrollments', [EnrollmentController::class, 'index']);
        Route::post('/students/{student}/enrollments', [EnrollmentController::class, 'store']);
        Route::delete('/students/{student}/enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);

        Route::get('/reports/attendance', [ReportController::class, 'attendance']);
        Route::get('/reports/attendance/pdf', [ReportController::class, 'attendancePdf']);
        Route::get('/reports/weekly-summary', [ReportController::class, 'weeklySummary']);
    });

    Route::middleware('role:teacher')->prefix('teacher')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index']);
        Route::get('/profile', [TeacherProfileController::class, 'show']);

        Route::get('/session-options', [ClassSessionController::class, 'formOptions']);

        Route::get('/sessions', [ClassSessionController::class, 'index']);
        Route::post('/sessions', [ClassSessionController::class, 'store']);
        Route::get('/sessions/{classSession}', [ClassSessionController::class, 'show']);
        Route::get('/sessions/{classSession}/roster', [ClassSessionController::class, 'roster']);
        Route::post('/sessions/{classSession}/attendance', [ClassSessionController::class, 'updateAttendance']);
        Route::post('/sessions/{classSession}/close', [ClassSessionController::class, 'close']);
        Route::delete('/sessions/{classSession}', [ClassSessionController::class, 'destroy']);
    });

    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index']);
        Route::get('/profile', [StudentProfileController::class, 'show']);
        Route::get('/qr', [QrController::class, 'show']);
    });

    Route::middleware('role:parent')->prefix('parent')->group(function () {
        Route::get('/dashboard', [ParentDashboardController::class, 'index']);
    });
});
