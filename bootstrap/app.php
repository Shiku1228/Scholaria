<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust ngrok / reverse-proxy forwarded headers so Laravel keeps the
        // original HTTPS scheme and client metadata during local tunnel testing.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            '2fa' => \App\Http\Middleware\TwoFactorMiddleware::class,
            'jwt' => \App\Http\Middleware\JwtMiddleware::class,
            'api' => \App\Http\Middleware\ApiMiddleware::class,
            'session.tracking' => \App\Http\Middleware\SessionTracking::class,
            'auth.monitoring' => \App\Http\Middleware\AuthenticationMonitoring::class,
            'is_admin' => \App\Http\Middleware\CheckIsAdmin::class,
        ]);
        
                
        // Add authentication monitoring to web middleware group
        $middleware->appendToGroup('web', \App\Http\Middleware\AuthenticationMonitoring::class);
        
        // Create API middleware group without CSRF
        $middleware->group('api', [
            \App\Http\Middleware\ApiMiddleware::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        // Remove CSRF from API routes
        $middleware->remove(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, 'api');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Redirect role-based 403s to the user's correct dashboard instead of showing a blank error
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException|\Illuminate\Auth\Access\AuthorizationException|\Spatie\Permission\Exceptions\UnauthorizedException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $user = $request->user();

            if (!$user) {
                return redirect()->route('login');
            }

            if (method_exists($user, 'hasRole')) {
                if ($user->hasRole(['Admin', 'Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin', 'Catalog Admin'])) {
                    return redirect()->route('admin.dashboard')->with('error', 'You do not have access to that page.');
                }
                if ($user->hasRole('Teacher')) {
                    return redirect()->route('teacher.dashboard')->with('error', 'You do not have access to that page.');
                }
                if ($user->hasRole('Student')) {
                    return redirect()->route('student.dashboard')->with('error', 'You do not have access to that page.');
                }
            }

            return redirect()->route('login');
        });
    })->create();
