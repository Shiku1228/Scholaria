<?php

namespace App\Http\Middleware;

use App\Services\AuthenticationMonitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthenticationMonitoring
{
    protected $authMonitor;

    public function __construct(AuthenticationMonitor $authMonitor)
    {
        $this->authMonitor = $authMonitor;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only monitor authentication routes
        if (!$this->isAuthenticationRoute($request)) {
            return $next($request);
        }

        // Check if IP is blocked
        $ipAddress = $request->ip();
        if ($this->authMonitor->isIpBlocked($ipAddress)) {
            Log::warning('Blocked IP attempted authentication', [
                'ip_address' => $ipAddress,
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Too many failed attempts. Please try again later.',
                'retry_after' => config('security.intrusion_detection.lockout_duration_minutes', 30) * 60,
            ], 429);
        }

        $response = $next($request);

        // Monitor authentication results
        if ($this->isLoginAttempt($request)) {
            $this->monitorLoginAttempt($request, $response);
        }

        return $response;
    }

    /**
     * Check if this is an authentication route.
     */
    private function isAuthenticationRoute(Request $request): bool
    {
        $authRoutes = [
            'login',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            '2fa.verify',
            '2fa.challenge',
        ];

        $routeName = $request->route()?->getName();
        $path = $request->path();

        // Check route name
        if ($routeName && str_contains($routeName, 'login')) {
            return true;
        }

        // Check path patterns
        foreach ($authRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is a login attempt.
     */
    private function isLoginAttempt(Request $request): bool
    {
        return $request->isMethod('POST') && 
               (str_contains($request->path(), 'login') || 
                str_contains($request->path(), '2fa'));
    }

    /**
     * Monitor login attempt results.
     */
    private function monitorLoginAttempt(Request $request, $response): void
    {
        $credentials = [
            'login' => $request->input('login', $request->input('email', $request->input('username'))),
            'password' => $request->input('password'),
        ];

        // Check if authentication failed
        if ($this->isAuthenticationFailure($request, $response)) {
            $reason = $this->getFailureReason($response);
            $this->authMonitor->logFailedLogin($credentials, $reason);
        }

        // Check if authentication succeeded
        elseif ($this->isAuthenticationSuccess($request, $response)) {
            $user = Auth::user();
            if ($user) {
                $this->authMonitor->logSuccessfulLogin($user);
            }
        }
    }

    /**
     * Check if authentication failed.
     */
    private function isAuthenticationFailure(Request $request, $response): bool
    {
        // Check response status
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        // Check for validation errors
        if (session()->has('errors')) {
            return true;
        }

        // Check for error messages in session
        if (session()->has('error')) {
            return true;
        }

        return false;
    }

    /**
     * Check if authentication succeeded.
     */
    private function isAuthenticationSuccess(Request $request, $response): bool
    {
        // Check if user is authenticated after the request
        return Auth::check() && $response->getStatusCode() < 400;
    }

    /**
     * Get failure reason from response or session.
     */
    private function getFailureReason($response): string
    {
        // Check session for error message
        if (session()->has('error')) {
            return session('error');
        }

        // Check validation errors
        if (session()->has('errors')) {
            $errors = session('errors');
            if ($errors instanceof \Illuminate\Support\ViewErrorBag) {
                $defaultBag = $errors->getBag('default');
                if ($defaultBag->has('email')) {
                    return 'Invalid email';
                }
                if ($defaultBag->has('password')) {
                    return 'Invalid password';
                }
                return 'Validation error';
            }
        }

        // Check response status
        $status = $response->getStatusCode();
        return match ($status) {
            401 => 'Unauthorized',
            403 => 'Forbidden',
            422 => 'Validation failed',
            429 => 'Rate limited',
            default => 'Authentication failed',
        };
    }
}
