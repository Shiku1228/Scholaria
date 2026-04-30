<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JwtAuthController;

Route::middleware('api')->group(function () {
    Route::post('/login', [JwtAuthController::class, 'login'])->name('login');
    Route::post('/refresh', [JwtAuthController::class, 'refresh'])->name('refresh');
    Route::post('/logout', [JwtAuthController::class, 'logout'])->name('logout');
    
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
});
