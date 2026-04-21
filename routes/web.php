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
use App\Http\Controllers\Teacher\TeacherNotificationController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentSubmissionController;
use App\Http\Controllers\Student\StudentCourseController;
use App\Http\Controllers\Student\StudentAssignmentController;
use App\Http\Controllers\Student\StudentGradeController;
use App\Http\Controllers\Student\StudentAnnouncementController;
use App\Http\Controllers\Student\StudentNotificationController;

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

Route::match(['GET', 'POST'], '/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

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
        Route::post('/courses/{course}/cover', [TeacherCourseController::class, 'updateCover'])->name('courses.cover.update');
        Route::post('/courses/{course}/overview', [TeacherCourseController::class, 'updateOverview'])->name('courses.overview.update');
        Route::post('/courses/{course}/resources', [TeacherCourseController::class, 'uploadResource'])->name('courses.resources.store');
        Route::post('/courses/{course}/discussions', [TeacherCourseController::class, 'storeDiscussion'])->name('courses.discussions.store');
        Route::patch('/courses/{course}/discussions/{discussion}', [TeacherCourseController::class, 'updateDiscussion'])->name('courses.discussions.update');
        Route::delete('/courses/{course}/discussions/{discussion}', [TeacherCourseController::class, 'destroyDiscussion'])->name('courses.discussions.destroy');

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
        Route::post('/enrollments', [TeacherEnrollmentController::class, 'store'])->name('enrollments.store');

        Route::get('/announcements', [TeacherAnnouncementController::class, 'overview'])->name('announcements');
        Route::view('/messages', 'teacher.messages')->name('messages');
        Route::view('/settings', 'teacher.settings')->name('settings');
        Route::post('/notifications/read-all', [TeacherNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{notification}/open', [TeacherNotificationController::class, 'open'])->name('notifications.open');
    });

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:Student'])
    ->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
        Route::post('/courses/{course}/discussions', [StudentCourseController::class, 'storeDiscussion'])->name('courses.discussions.store');
        Route::patch('/courses/{course}/discussions/{discussion}', [StudentCourseController::class, 'updateDiscussion'])->name('courses.discussions.update');
        Route::delete('/courses/{course}/discussions/{discussion}', [StudentCourseController::class, 'destroyDiscussion'])->name('courses.discussions.destroy');
        Route::get('/assignments', [StudentAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/announcements', [StudentAnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');

        Route::get('/assignments/{assignment}/submit', [StudentSubmissionController::class, 'create'])->name('assignments.submit');
        Route::post('/assignments/{assignment}/submit', [StudentSubmissionController::class, 'store'])->name('assignments.submit.store');
        Route::post('/notifications/read-all', [StudentNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{notification}/open', [StudentNotificationController::class, 'open'])->name('notifications.open');
    });
