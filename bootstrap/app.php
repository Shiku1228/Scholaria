<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            '2fa' => \App\Http\Middleware\TwoFactorMiddleware::class,
            'jwt' => \App\Http\Middleware\JwtMiddleware::class,
            'api' => \App\Http\Middleware\ApiMiddleware::class,
            'session.tracking' => \App\Http\Middleware\SessionTracking::class,
            'auth.monitoring' => \App\Http\Middleware\AuthenticationMonitoring::class,
            'check.permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
        
                
        // Add authentication monitoring to web middleware group
        $middleware->appendToGroup('web', \App\Http\Middleware\AuthenticationMonitoring::class);
        
        // Create API middleware group without CSRF
        $middleware->group('api', [
            \App\Http\Middleware\ApiMiddleware::class,
        ]);
        
        // Remove CSRF from API routes
        $middleware->remove(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, 'api');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
