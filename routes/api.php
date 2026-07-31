<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentResultController;

/*
|--------------------------------------------------------------------------
| API Routes — School Management System
|--------------------------------------------------------------------------
|
| This file is the source of truth for the contract defined in the
| Android app's SchoolApiService.kt. Every route below has a matching
| Retrofit method — keep them in sync.
|
| Auth: all routes except /auth/login require a valid Sanctum bearer
| token, sent by the app as:
|     Authorization: Bearer <token>
| (see SessionManager.kt -> getAuthHeader())
|
*/

// ───────────── Auth (public) ─────────────
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/password/forgot', [PasswordResetController::class, 'sendResetCode']);
Route::post('/auth/password/reset', [PasswordResetController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // ───────────── Student dashboard & features (Tasks 3-6) ─────────────
    Route::get('/students/{id}/summary', [StudentController::class, 'summary']);
    Route::get('/students/{id}/exams', [StudentController::class, 'examOptions']);
    Route::get('/students/{id}/results', [StudentController::class, 'results']);
    Route::get('/students/{id}/fees', [StudentController::class, 'fees']);
    Route::get('/students/{id}/attendance', [StudentController::class, 'attendance']);

    // ───────────── Staff dashboard & features (Tasks 7-9) ─────────────
    Route::get('/staff/{id}/summary', [StaffController::class, 'summary']);
    Route::post('/classes/{classId}/attendance', [StaffController::class, 'submitAttendance']);
    Route::post('/exams/{examId}/results', [StaffController::class, 'submitExamResults']);

    // Helper endpoint (not in original SchoolApiService.kt — see TODOs
    // in MarkAttendanceScreen.kt / EnterResultsScreen.kt)
    Route::get('/classes/{classId}/students', [StaffController::class, 'studentsInClass']);

    // ───────────── Announcements (Task 11) ─────────────
    Route::get('/announcements', [AnnouncementController::class, 'index']);

    // ───────────── Student Exam Results ─────────────

    // Dropdown population endpoint
    Route::get('/exams/published', [StudentResultController::class, 'getAvailableExams']);

    // Fetch individual exam performance card details
    Route::get('/exams/{exam_id}/results', [StudentResultController::class, 'getExamResults']);

    // Staff Dashboard Route
    Route::get('/staff/dashboard', [DashboardController::class, 'getDashboardData']);

    // Student Attendance Calendar Route
    Route::get('/student/attendance-calendar', [AttendanceController::class, 'getStudentCalendar']);

    // Student Fee Summary and History Route
    Route::get('/student/fees-summary', [FeeController::class, 'getFeeSummaryAndHistory']);

    // Student Search Endpoint
    Route::get('/students/search', [StaffController::class, 'search']);

    // GET /api/exams
    Route::get('/exams', [StaffController::class, 'exams']);
    Route::get('/subjects', [StaffController::class, 'subjects']);
    Route::get('/classes/{classId}/students', [StaffController::class, 'studentsInClass']);
});
