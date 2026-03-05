<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Admin\AdminEnrollmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherCourseController;
use App\Http\Controllers\Teacher\TeacherStudentController;
use App\Http\Controllers\Teacher\TeacherEnrollmentController;
use App\Http\Controllers\Teacher\TeacherAssignmentController;
use App\Http\Controllers\Teacher\TeacherAnnouncementController;
use App\Http\Controllers\Teacher\TeacherSubmissionController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentSubmissionController;
use App\Http\Controllers\Student\StudentCourseController;
use App\Http\Controllers\Student\StudentAssignmentController;
use App\Http\Controllers\Student\StudentGradeController;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    if (method_exists($user, 'hasRole') && $user->hasRole('Admin')) {
        return redirect()->route('admin.dashboard');
    }

    if (method_exists($user, 'hasRole') && $user->hasRole('Teacher')) {
        return redirect()->route('teacher.dashboard');
    }

    return redirect()->route('student.dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::get('/logout', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:Admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::resource('/users', AdminUserController::class);

        Route::get('/courses/check-number', [AdminCourseController::class, 'checkNumber'])->name('courses.check-number');
        Route::resource('/courses', AdminCourseController::class)->except(['show']);

        Route::resource('/enrollments', AdminEnrollmentController::class)->except(['show']);
    });

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:Teacher'])
    ->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

        Route::get('/assignments', [TeacherAssignmentController::class, 'overview'])->name('assignments.overview');

        Route::get('/courses', [TeacherCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [TeacherCourseController::class, 'show'])->name('courses.show');

        Route::prefix('/courses/{course}')
            ->group(function () {
                Route::resource('/assignments', TeacherAssignmentController::class)->except(['destroy']);
                Route::delete('/assignments/{assignment}', [TeacherAssignmentController::class, 'destroy'])->name('assignments.destroy');

                Route::resource('/announcements', TeacherAnnouncementController::class)->except(['destroy']);
                Route::delete('/announcements/{announcement}', [TeacherAnnouncementController::class, 'destroy'])->name('announcements.destroy');

                Route::patch('/assignments/{assignment}/submissions/{submission}', [TeacherSubmissionController::class, 'update'])->name('submissions.update');
            });

        Route::get('/students', [TeacherStudentController::class, 'index'])->name('students.index');
        Route::get('/enrollments', [TeacherEnrollmentController::class, 'index'])->name('enrollments.index');

        Route::get('/announcements', [TeacherAnnouncementController::class, 'overview'])->name('announcements');
        Route::view('/messages', 'teacher.messages')->name('messages');
        Route::view('/settings', 'teacher.settings')->name('settings');
    });

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:Student'])
    ->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');
        Route::get('/assignments', [StudentAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');

        Route::get('/assignments/{assignment}/submit', [StudentSubmissionController::class, 'create'])->name('assignments.submit');
        Route::post('/assignments/{assignment}/submit', [StudentSubmissionController::class, 'store'])->name('assignments.submit.store');
    });
