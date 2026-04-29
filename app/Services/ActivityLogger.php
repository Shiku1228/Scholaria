<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ActivityLogger
{
    /**
     * Log user activity.
     */
    public function log(array $data): ActivityLog
    {
        $logData = $this->prepareLogData($data);
        
        return ActivityLog::create($logData);
    }

    /**
     * Log authentication activity.
     */
    public function logAuth(string $action, array $data = []): ActivityLog
    {
        return $this->log([
            'user_id' => $data['user_id'] ?? Auth::id(),
            'action' => $action,
            'resource_type' => 'auth',
            'request_data' => array_merge([
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ], $data),
        ]);
    }

    /**
     * Log CRUD operations.
     */
    public function logCrud(string $action, string $resourceType, int $resourceId, array $data = []): ActivityLog
    {
        return $this->log([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'request_data' => array_merge([
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ], $data),
        ]);
    }

    /**
     * Log API access.
     */
    public function logApiAccess(string $endpoint, string $method, array $data = []): ActivityLog
    {
        return $this->log([
            'user_id' => Auth::id(),
            'action' => 'api_access',
            'resource_type' => 'api',
            'request_data' => array_merge([
                'endpoint' => $endpoint,
                'method' => $method,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ], $data),
        ]);
    }

    /**
     * Log file access/download.
     */
    public function logFileAccess(string $fileType, string $fileName, array $data = []): ActivityLog
    {
        return $this->log([
            'user_id' => Auth::id(),
            'action' => 'file_access',
            'resource_type' => 'file',
            'request_data' => array_merge([
                'file_type' => $fileType,
                'file_name' => $fileName,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ], $data),
        ]);
    }

    /**
     * Log security events.
     */
    public function logSecurity(string $event, array $data = []): ActivityLog
    {
        return $this->log([
            'user_id' => $data['user_id'] ?? Auth::id(),
            'action' => $event,
            'resource_type' => 'security',
            'request_data' => array_merge([
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ], $data),
        ]);
    }

    /**
     * Get recent activities for a user.
     */
    public function getRecentActivities(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::where('user_id', $userId)
            ->with('resource')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by resource.
     */
    public function getActivitiesByResource(string $resourceType, int $resourceId, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities within date range.
     */
    public function getActivitiesByDateRange(Carbon $startDate, Carbon $endDate, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = ActivityLog::whereBetween('created_at', [$startDate, $endDate]);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (isset($filters['is_sensitive'])) {
            $query->where('is_sensitive', $filters['is_sensitive']);
        }

        return $query->with('user')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get activity statistics.
     */
    public function getActivityStats(Carbon $startDate, Carbon $endDate): array
    {
        $activities = ActivityLog::whereBetween('created_at', [$startDate, $endDate]);

        $totalActivities = $activities->count();
        $uniqueUsers = $activities->distinct('user_id')->count('user_id');
        $sensitiveActivities = $activities->where('is_sensitive', true)->count();

        $byAction = $activities->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'action');

        $byResourceType = $activities->selectRaw('resource_type, COUNT(*) as count')
            ->groupBy('resource_type')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'resource_type');

        $byHour = $activities->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');

        return [
            'total_activities' => $totalActivities,
            'unique_users' => $uniqueUsers,
            'sensitive_activities' => $sensitiveActivities,
            'by_action' => $byAction,
            'by_resource_type' => $byResourceType,
            'by_hour' => $byHour,
        ];
    }

    /**
     * Clean up old activity logs.
     */
    public function cleanupOldLogs(int $retentionDays = 90): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)
            ->delete();

        return $deletedCount;
    }

    /**
     * Export activity logs to CSV.
     */
    public function exportToCsv(Carbon $startDate, Carbon $endDate, array $filters = []): string
    {
        $activities = $this->getActivitiesByDateRange($startDate, $endDate, $filters);
        
        $csv = "ID,User,Action,Resource Type,Resource ID,IP Address,User Agent,Created At\n";
        
        foreach ($activities as $activity) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s\n",
                $activity->id,
                $activity->user ? $activity->user->name : 'N/A',
                $activity->action,
                $activity->resource_type ?? 'N/A',
                $activity->resource_id ?? 'N/A',
                $activity->ip_address,
                str_replace(["\n", "\r", ","], [" ", " ", ";"], $activity->user_agent),
                $activity->created_at->format('Y-m-d H:i:s')
            );
        }
        
        return $csv;
    }

    /**
     * Prepare log data with security and encryption.
     */
    private function prepareLogData(array $data): array
    {
        $requestData = $data['request_data'] ?? [];
        
        // Check if data contains sensitive information
        $isSensitive = ActivityLog::isSensitiveAction($requestData);
        
        $logData = [
            'user_id' => $data['user_id'] ?? null,
            'session_id' => session()->getId(),
            'action' => $data['action'],
            'resource_type' => $data['resource_type'] ?? null,
            'resource_id' => $data['resource_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? Request::ip(),
            'user_agent' => $data['user_agent'] ?? Request::userAgent(),
            'is_sensitive' => $isSensitive,
            'created_at' => now(),
        ];

        if ($isSensitive && config('security.audit.encrypt_sensitive_data')) {
            $logData['encrypted_data'] = $requestData;
        } else {
            $logData['request_data'] = $requestData;
        }

        return $logData;
    }

    /**
     * Detect anomalous activity patterns.
     */
    public function detectAnomalies(int $userId): array
    {
        $recentActivities = $this->getRecentActivities($userId, 100);
        $anomalies = [];

        // Check for unusual login times
        $logins = $recentActivities->where('action', 'login');
        $currentHour = now()->hour;
        
        $unusualTimeLogins = $logins->filter(function ($activity) use ($currentHour) {
            $loginHour = $activity->created_at->hour;
            return $loginHour < 6 || $loginHour > 22; // Unusual hours
        });

        if ($unusualTimeLogins->count() > 0) {
            $anomalies[] = [
                'type' => 'unusual_login_time',
                'count' => $unusualTimeLogins->count(),
                'description' => 'Login activity during unusual hours',
            ];
        }

        // Check for multiple IP addresses
        $uniqueIps = $recentActivities->pluck('ip_address')->unique();
        if ($uniqueIps->count() > 3) {
            $anomalies[] = [
                'type' => 'multiple_ips',
                'count' => $uniqueIps->count(),
                'description' => 'Activity from multiple IP addresses',
            ];
        }

        // Check for rapid successive actions
        $lastHourActivities = $recentActivities->filter(function ($activity) {
            return $activity->created_at->diffInMinutes(now()) <= 60;
        });

        if ($lastHourActivities->count() > 100) {
            $anomalies[] = [
                'type' => 'high_activity_volume',
                'count' => $lastHourActivities->count(),
                'description' => 'Unusually high activity volume',
            ];
        }

        return $anomalies;
    }
}
