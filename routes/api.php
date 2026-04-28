<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JwtAuthController;

Route::post('/login', [JwtAuthController::class, 'login'])->name('login');
Route::post('/refresh', [JwtAuthController::class, 'refresh'])->name('refresh');
Route::post('/logout', [JwtAuthController::class, 'logout'])->name('logout');

Route::middleware('jwt')->group(function () {
    Route::get('/me', [JwtAuthController::class, 'me'])->name('me');
    Route::get('/validate', [JwtAuthController::class, 'validate'])->name('validate');
});
