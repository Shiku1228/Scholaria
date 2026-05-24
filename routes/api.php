<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JwtAuthController;
use App\Http\Controllers\Api\Student\StudentDashboardApiController;
use App\Http\Controllers\Api\Student\StudentCourseApiController;
use App\Http\Controllers\Api\Student\StudentTaskApiController;
use App\Http\Controllers\Api\Student\StudentAssignmentApiController;
use App\Http\Controllers\Api\Student\StudentSubmissionApiController;
use App\Http\Controllers\Api\Student\StudentExamApiController;
use App\Http\Controllers\Api\Student\StudentQuizApiController;

Route::middleware('api')->group(function () {
    Route::post('/login', [JwtAuthController::class, 'login'])->name('api.login');
    Route::get('/login', [JwtAuthController::class, 'login'])->name('api.login.get');
    Route::post('/logout', [JwtAuthController::class, 'logout'])->name('api.logout');
    Route::get('/logout', [JwtAuthController::class, 'logout'])->name('logout.get');
    Route::post('/refresh', [JwtAuthController::class, 'refresh'])->name('refresh');
    
    Route::middleware('jwt')->group(function () {
        Route::get('/me', [JwtAuthController::class, 'me'])->name('me');
        Route::get('/validate', [JwtAuthController::class, 'validate'])->name('validate');
    });
    
    // Test endpoint for Android app
    Route::get('/test', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Laravel API is working!',
            'timestamp' => now()->toDateTimeString()
        ]);
    })->name('test');
    
    // Simple login test without validation
    Route::post('/login-test', function () {
        return response()->json([
            'success' => true,
            'token' => 'test-token-123',
            'user' => [
                'id' => 1,
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]
        ]);
    });

    Route::middleware('jwt')->prefix('student')->group(function () {
        Route::get('/dashboard', [StudentDashboardApiController::class, 'index'])->name('api.student.dashboard');
        Route::get('/courses', [StudentCourseApiController::class, 'index'])->name('api.student.courses.index');
        Route::get('/courses/{course}', [StudentCourseApiController::class, 'show'])->name('api.student.courses.show');

        Route::get('/tasks', [StudentTaskApiController::class, 'index'])->name('api.student.tasks.index');

        Route::get('/assignments', [StudentAssignmentApiController::class, 'index'])->name('api.student.assignments.index');
        Route::get('/assignments/{assignment}', [StudentAssignmentApiController::class, 'show'])->name('api.student.assignments.show');
        Route::get('/assignments/{assignment}/submit', [StudentSubmissionApiController::class, 'create'])->name('api.student.assignments.submit');
        Route::post('/assignments/{assignment}/submit', [StudentSubmissionApiController::class, 'store'])->name('api.student.assignments.submit.store');

        Route::get('/exams', [StudentExamApiController::class, 'index'])->name('api.student.exams.index');
        Route::get('/exams/{exam}', [StudentExamApiController::class, 'show'])->name('api.student.exams.show');
        Route::post('/exams/{exam}/start', [StudentExamApiController::class, 'start'])->name('api.student.exams.start');
        Route::post('/exams/{exam}/submit', [StudentExamApiController::class, 'submit'])->name('api.student.exams.submit');

        Route::get('/quizzes', [StudentQuizApiController::class, 'index'])->name('api.student.quizzes.index');
        Route::get('/quizzes/{quiz}', [StudentQuizApiController::class, 'show'])->name('api.student.quizzes.show');
        Route::post('/quizzes/{quiz}/start', [StudentQuizApiController::class, 'start'])->name('api.student.quizzes.start');
        Route::post('/quizzes/{quiz}/submit', [StudentQuizApiController::class, 'submit'])->name('api.student.quizzes.submit');
    });
});
