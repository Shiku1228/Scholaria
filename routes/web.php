<?php

use Illuminate\Support\Facades\Route;
 use App\Http\Controllers\Admin\AdminDashboardController;
 use App\Http\Controllers\Admin\AdminUserController;
 use App\Http\Controllers\Auth\AuthenticatedSessionController;
 use App\Http\Controllers\Auth\PasswordResetLinkController;
 use App\Http\Controllers\Auth\NewPasswordController;
 use App\Http\Controllers\Teacher\TeacherDashboardController;
 use App\Http\Controllers\Student\StudentDashboardController;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $role = (string) (auth()->user()->role ?? 'student');

    return match ($role) {
        'admin' => redirect()->route('admin.dashboard'),
        'teacher' => redirect()->route('teacher.dashboard'),
        default => redirect()->route('student.dashboard'),
    };
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::resource('/users', AdminUserController::class);
    });

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher'])
    ->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    });

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    });
