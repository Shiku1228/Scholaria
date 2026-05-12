<?php

namespace App\Http\Middleware;

use App\Services\SessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SessionTracking
{
    protected $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only track authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $sessionId = session()->getId();

            // Create session if it doesn't exist
            if (!$this->hasActiveSession($user->id, $sessionId)) {
                $this->sessionManager->createSession($user, $sessionId);
            } else {
                // Update session activity
                $this->sessionManager->updateActivity($sessionId);
            }

            // Validate session security
            if (!$this->sessionManager->isSessionValid($sessionId, $user->id)) {
                Auth::logout();
                return redirect()->route('login')
                    ->with('error', 'Session expired or invalid. Please login again.');
            }
        }

        $response = $next($request);

        // Log the request after processing
        if (Auth::check()) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Check if user has an active session.
     */
    private function hasActiveSession(int $userId, string $sessionId): bool
    {
        return \App\Models\UserSession::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Log the request for activity tracking.
     */
    private function logRequest(Request $request, $response): void
    {
        $user = Auth::user();
        
        // Skip logging for certain routes to reduce noise
        $skipRoutes = [
            'heartbeat',
            'ping',
            'metrics',
            'health-check',
        ];

        if (in_array($request->route()->getName(), $skipRoutes)) {
            return;
        }

        // Log the activity
        \App\Models\ActivityLog::log([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'action' => $this->getActionFromRequest($request),
            'resource_type' => $this->getResourceTypeFromRequest($request),
            'resource_id' => $this->getResourceIdFromRequest($request),
            'request_data' => $this->getSafeRequestData($request),
        ]);
    }

    /**
     * Determine action type from request.
     */
    private function getActionFromRequest(Request $request): string
    {
        $method = $request->method();
        $routeName = $request->route()->getName();

        // Map common route patterns to actions
        if ($routeName) {
            if (str_contains($routeName, 'login')) return 'login';
            if (str_contains($routeName, 'logout')) return 'logout';
            if (str_contains($routeName, 'create') || str_contains($routeName, 'store')) return 'create';
            if (str_contains($routeName, 'update') || str_contains($routeName, 'edit')) return 'update';
            if (str_contains($routeName, 'delete') || str_contains($routeName, 'destroy')) return 'delete';
            if (str_contains($routeName, 'show') || str_contains($routeName, 'view')) return 'view';
            if (str_contains($routeName, 'index') || str_contains($routeName, 'list')) return 'list';
        }

        // Fallback to HTTP method
        return match ($method) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'access',
        };
    }

    /**
     * Determine resource type from request.
     */
    private function getResourceTypeFromRequest(Request $request): ?string
    {
        $routeName = $request->route()->getName();
        
        if ($routeName) {
            // Extract resource type from route name
            if (preg_match('/(\w+)\.(create|store|update|edit|show|delete|destroy|index|list)/', $routeName, $matches)) {
                return $matches[1];
            }
        }

        // Fallback to route parameters
        $parameters = $request->route()->parameters();
        foreach ($parameters as $key => $value) {
            if (in_array($key, ['user', 'course', 'assignment', 'submission', 'announcement'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Get resource ID from request.
     */
    private function getResourceIdFromRequest(Request $request): ?int
    {
        $parameters = $request->route()->parameters();
        
        // Look for common ID parameters
        foreach (['id', 'user_id', 'course_id', 'assignment_id'] as $param) {
            if (isset($parameters[$param]) && is_numeric($parameters[$param])) {
                return (int) $parameters[$param];
            }
        }

        return null;
    }

    /**
     * Get safe request data (excluding sensitive information).
     */
    private function getSafeRequestData(Request $request): array
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Include safe request parameters
        $safeParams = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'ssn',
        ]);

        if (!empty($safeParams)) {
            $data['parameters'] = $safeParams;
        }

        return $data;
    }
}
