<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogging
{
    protected $activityLogger;

    public function __construct(ActivityLogger $activityLogger = null)
    {
        $this->activityLogger = $activityLogger ?: app(ActivityLogger::class);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only log authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $startTime = microtime(true);
        $response = $next($request);
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // in milliseconds

        // Log the activity
        $this->logActivity($request, $response, $duration);

        return $response;
    }

    /**
     * Log the activity with comprehensive data.
     */
    private function logActivity(Request $request, $response, float $duration): void
    {
        try {
            $action = $this->determineAction($request);
            $resourceType = $this->determineResourceType($request);
            $resourceId = $this->determineResourceId($request);

            // Prepare request data
            $requestData = $this->prepareRequestData($request, [
                'response_status' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
            ]);

            // Log based on action type
            match ($action) {
                'login', 'logout' => $this->activityLogger->logAuth($action, $requestData),
                'create', 'update', 'delete' => $this->activityLogger->logCrud($action, $resourceType, $resourceId, $requestData),
                'api_access' => $this->activityLogger->logApiAccess($request->path(), $request->method(), $requestData),
                'file_access' => $this->activityLogger->logFileAccess($requestData['file_type'] ?? 'unknown', $requestData['file_name'] ?? 'unknown', $requestData),
                default => $this->activityLogger->log([
                    'action' => $action,
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                    'request_data' => $requestData,
                ]),
            };

            // Check for anomalies
            $this->checkForAnomalies($request, $action);

        } catch (\Exception $e) {
            // Log error but don't break the request
            Log::error('Activity logging failed', [
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Determine the action from request.
     */
    private function determineAction(Request $request): string
    {
        $method = $request->method();
        $routeName = $request->route()?->getName();
        $path = $request->path();

        // Check route name first
        if ($routeName) {
            if (str_contains($routeName, 'login')) return 'login';
            if (str_contains($routeName, 'logout')) return 'logout';
            if (str_contains($routeName, 'register')) return 'register';
            if (str_contains($routeName, 'password')) return 'password_change';
            if (str_contains($routeName, 'profile')) return 'profile_update';
            if (str_contains($routeName, 'create') || str_contains($routeName, 'store')) return 'create';
            if (str_contains($routeName, 'update') || str_contains($routeName, 'edit')) return 'update';
            if (str_contains($routeName, 'delete') || str_contains($routeName, 'destroy')) return 'delete';
            if (str_contains($routeName, 'download')) return 'file_download';
            if (str_contains($routeName, 'upload')) return 'file_upload';
        }

        // Check path patterns
        if (str_contains($path, 'api/')) return 'api_access';
        if (str_contains($path, 'admin/')) return 'admin_access';
        if (str_contains($path, 'download')) return 'file_download';
        if (str_contains($path, 'upload')) return 'file_upload';

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
    private function determineResourceType(Request $request): ?string
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();

        // Extract from route name
        if ($routeName && preg_match('/^(\w+)\./', $routeName, $matches)) {
            $resourceType = $matches[1];
            if (in_array($resourceType, ['user', 'course', 'assignment', 'submission', 'announcement', 'grade', 'quiz'])) {
                return $resourceType;
            }
        }

        // Extract from path
        $pathParts = explode('/', $path);
        foreach ($pathParts as $part) {
            if (in_array($part, ['users', 'courses', 'assignments', 'submissions', 'announcements', 'grades', 'quizzes'])) {
                return rtrim($part, 's'); // Remove plural 's'
            }
        }

        return null;
    }

    /**
     * Determine resource ID from request.
     */
    private function determineResourceId(Request $request): ?int
    {
        $parameters = $request->route()?->parameters() ?? [];
        
        // Look for common ID parameters
        foreach (['id', 'user_id', 'course_id', 'assignment_id', 'submission_id'] as $param) {
            if (isset($parameters[$param]) && is_numeric($parameters[$param])) {
                return (int) $parameters[$param];
            }
        }

        return null;
    }

    /**
     * Prepare safe request data.
     */
    private function prepareRequestData(Request $request, array $additionalData = []): array
    {
        $data = array_merge([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'accept_language' => $request->header('accept-language'),
        ], $additionalData);

        // Include safe request parameters
        $safeParams = $request->except($this->getSensitiveFields());
        
        if (!empty($safeParams)) {
            $data['parameters'] = $safeParams;
        }

        // Include file information if uploaded
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file_info'] = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];
        }

        return $data;
    }

    /**
     * Get list of sensitive fields to exclude.
     */
    private function getSensitiveFields(): array
    {
        return config('security.audit.sensitive_fields', [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'ssn',
            'social_security_number',
            'bank_account',
            'cvv',
            'pin',
        ]);
    }

    /**
     * Check for anomalous activity patterns.
     */
    private function checkForAnomalies(Request $request, string $action): void
    {
        $userId = Auth::id();
        
        // Skip anomaly check for non-sensitive actions
        $nonSensitiveActions = ['view', 'list', 'api_access'];
        if (in_array($action, $nonSensitiveActions)) {
            return;
        }

        $anomalies = $this->activityLogger->detectAnomalies($userId);
        
        if (!empty($anomalies)) {
            foreach ($anomalies as $anomaly) {
                SecurityAudit::logSuspiciousActivity([
                    'description' => 'Anomalous activity detected: ' . $anomaly['description'],
                    'user_id' => $userId,
                    'event_data' => array_merge($anomaly, [
                        'current_action' => $action,
                        'current_ip' => $request->ip(),
                        'current_path' => $request->path(),
                    ]),
                ]);
            }
        }
    }
}
