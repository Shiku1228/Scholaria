<?php

namespace App\Services;

use App\Models\SecurityAudit;
use App\Models\ActivityLog;
use App\Models\UserSession;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnomalyDetectionService
{
    /**
     * Run comprehensive anomaly detection.
     */
    public function detectAnomalies(): array
    {
        $anomalies = [];

        // Check for various anomaly types
        $anomalies = array_merge($anomalies, $this->detectBruteForceAttacks());
        $anomalies = array_merge($anomalies, $this->detectUnusualLoginPatterns());
        $anomalies = array_merge($anomalies, $this->detectSessionAnomalies());
        $anomalies = array_merge($anomalies, $this->detectGeographicAnomalies());
        $anomalies = array_merge($anomalies, $this->detectTimeBasedAnomalies());
        $anomalies = array_merge($anomalies, $this->detectVelocityAnomalies());
        $anomalies = array_merge($anomalies, $this->detectPrivilegeEscalation());
        $anomalies = array_merge($anomalies, $this->detectDataAccessAnomalies());

        // Process and categorize anomalies
        return $this->processAnomalies($anomalies);
    }

    /**
     * Detect brute force attack patterns.
     */
    public function detectBruteForceAttacks(): array
    {
        $anomalies = [];
        $threshold = config('security.intrusion_detection.failed_login_threshold', 5);
        $window = config('security.intrusion_detection.failed_login_window_minutes', 15);

        // Check IP-based brute force
        $suspiciousIps = SecurityAudit::where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subMinutes($window))
            ->selectRaw('ip_address, COUNT(*) as attempt_count')
            ->groupBy('ip_address')
            ->having('attempt_count', '>=', $threshold)
            ->get();

        foreach ($suspiciousIps as $ip) {
            $anomalies[] = [
                'type' => 'brute_force_ip',
                'severity' => $ip->attempt_count >= $threshold * 2 ? 'critical' : 'high',
                'description' => "Brute force attack detected from IP {$ip->ip_address}",
                'ip_address' => $ip->ip_address,
                'attempt_count' => $ip->attempt_count,
                'event_data' => [
                    'ip_address' => $ip->ip_address,
                    'attempt_count' => $ip->attempt_count,
                    'threshold' => $threshold,
                ],
            ];
        }

        // Check username-based brute force
        $suspiciousUsernames = SecurityAudit::where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('event_data->username as username, COUNT(*) as attempt_count, COUNT(DISTINCT ip_address) as unique_ips')
            ->groupBy('event_data->username')
            ->having('attempt_count', '>=', 10)
            ->having('unique_ips', '>=', 3)
            ->get();

        foreach ($suspiciousUsernames as $username) {
            $anomalies[] = [
                'type' => 'brute_force_username',
                'severity' => 'high',
                'description' => "Brute force attack against username {$username->username}",
                'username' => $username->username,
                'attempt_count' => $username->attempt_count,
                'unique_ips' => $username->unique_ips,
                'event_data' => [
                    'username' => $username->username,
                    'attempt_count' => $username->attempt_count,
                    'unique_ips' => $username->unique_ips,
                ],
            ];
        }

        return $anomalies;
    }

    /**
     * Detect unusual login patterns.
     */
    public function detectUnusualLoginPatterns(): array
    {
        $anomalies = [];

        // Check for logins from unusual times
        $unusualTimeLogins = SecurityAudit::where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereRaw('HOUR(created_at) NOT BETWEEN 8 AND 22') // Unusual hours
            ->count();

        if ($unusualTimeLogins > 10) {
            $anomalies[] = [
                'type' => 'unusual_time_login',
                'severity' => 'medium',
                'description' => 'High number of login attempts during unusual hours',
                'count' => $unusualTimeLogins,
                'event_data' => [
                    'unusual_hour_attempts' => $unusualTimeLogins,
                ],
            ];
        }

        // Check for rapid successive attempts
        $rapidAttempts = SecurityAudit::where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->where('ip_address', '!=', '')
            ->selectRaw('ip_address, COUNT(*) as attempts')
            ->groupBy('ip_address')
            ->having('attempts', '>=', 20)
            ->get();

        foreach ($rapidAttempts as $attempt) {
            $anomalies[] = [
                'type' => 'rapid_attempts',
                'severity' => 'high',
                'description' => "Rapid successive failed attempts from {$attempt->ip_address}",
                'ip_address' => $attempt->ip_address,
                'attempts' => $attempt->attempts,
                'event_data' => [
                    'ip_address' => $attempt->ip_address,
                    'attempts_5min' => $attempt->attempts,
                ],
            ];
        }

        return $anomalies;
    }

    /**
     * Detect session anomalies.
     */
    public function detectSessionAnomalies(): array
    {
        $anomalies = [];

        // Check for users with excessive concurrent sessions
        $concurrentSessions = UserSession::where('is_active', true)
            ->selectRaw('user_id, COUNT(*) as session_count')
            ->groupBy('user_id')
            ->having('session_count', '>', 5)
            ->with('user')
            ->get();

        foreach ($concurrentSessions as $session) {
            $anomalies[] = [
                'type' => 'excessive_sessions',
                'severity' => 'medium',
                'description' => "User {$session->user->name} has {$session->session_count} concurrent sessions",
                'user_id' => $session->user_id,
                'session_count' => $session->session_count,
                'event_data' => [
                    'user_id' => $session->user_id,
                    'session_count' => $session->session_count,
                    'user_name' => $session->user->name,
                ],
            ];
        }

        // Check for sessions from multiple geographic locations
        $multiLocationSessions = UserSession::where('is_active', true)
            ->selectRaw('user_id, COUNT(DISTINCT ip_address) as unique_ips')
            ->groupBy('user_id')
            ->having('unique_ips', '>', 3)
            ->with('user')
            ->get();

        foreach ($multiLocationSessions as $session) {
            $anomalies[] = [
                'type' => 'multi_location_sessions',
                'severity' => 'high',
                'description' => "User {$session->user->name} has active sessions from {$session->unique_ips} different IPs",
                'user_id' => $session->user_id,
                'unique_ips' => $session->unique_ips,
                'event_data' => [
                    'user_id' => $session->user_id,
                    'unique_ips' => $session->unique_ips,
                    'user_name' => $session->user->name,
                ],
            ];
        }

        return $anomalies;
    }

    /**
     * Detect geographic anomalies.
     */
    public function detectGeographicAnomalies(): array
    {
        $anomalies = [];

        // Check for logins from multiple countries in short time
        $multiCountryLogins = SecurityAudit::where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('event_data->geo_location->country')
            ->selectRaw('event_data->geo_location->country as country, COUNT(*) as count')
            ->groupBy('event_data->geo_location->country')
            ->having('count', '>=', 5)
            ->get();

        if ($multiCountryLogins->count() > 5) {
            $anomalies[] = [
                'type' => 'multi_country_attacks',
                'severity' => 'high',
                'description' => 'Failed login attempts from multiple countries detected',
                'country_count' => $multiCountryLogins->count(),
                'event_data' => [
                    'countries' => $multiCountryLogins->pluck('country'),
                    'country_count' => $multiCountryLogins->count(),
                ],
            ];
        }

        return $anomalies;
    }

    /**
     * Detect time-based anomalies.
     */
    public function detectTimeBasedAnomalies(): array
    {
        $anomalies = [];

        // Check for unusual activity patterns
        $hourlyActivity = ActivityLog::where('created_at', '>=', now()->subHours(24))
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->get();

        $avgActivity = $hourlyActivity->avg('count');
        $maxActivity = $hourlyActivity->max('count');

        if ($maxActivity > $avgActivity * 3) {
            $peakHour = $hourlyActivity->where('count', $maxActivity)->first();
            $anomalies[] = [
                'type' => 'unusual_activity_spike',
                'severity' => 'medium',
                'description' => "Unusual activity spike detected at hour {$peakHour->hour}",
                'hour' => $peakHour->hour,
                'count' => $peakHour->count,
                'average' => $avgActivity,
                'event_data' => [
                    'hour' => $peakHour->hour,
                    'count' => $peakHour->count,
                    'average' => $avgActivity,
                    'spike_ratio' => round($maxActivity / $avgActivity, 2),
                ],
            ];
        }

        return $anomalies;
    }

    /**
     * Detect velocity anomalies.
     */
    public function detectVelocityAnomalies(): array
    {
        $anomalies = [];

        // Check for high-velocity actions
        $highVelocityUsers = ActivityLog::where('created_at', '>=', now()->subMinutes(10))
            ->selectRaw('user_id, COUNT(*) as action_count')
            ->groupBy('user_id')
            ->having('action_count', '>', 100)
            ->with('user')
            ->get();

        foreach ($highVelocityUsers as $user) {
            $anomalies[] = [
                'type' => 'high_velocity_actions',
                'severity' => 'medium',
                'description' => "User {$user->user->name} performed {$user->action_count} actions in 10 minutes",
                'user_id' => $user->user_id,
                'action_count' => $user->action_count,
                'event_data' => [
                    'user_id' => $user->user_id,
                    'action_count' => $user->action_count,
                    'user_name' => $user->user->name,
                    'time_window' => '10 minutes',
                ],
            ];
        }

        return $anomalies;
    }

    /**
     * Detect privilege escalation attempts.
     */
    public function detectPrivilegeEscalation(): array
    {
        $anomalies = [];

        // Check for failed admin access attempts
        $failedAdminAttempts = SecurityAudit::where('event_type', 'suspicious_activity')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereJsonContains('event_data->resource_type', 'admin')
            ->count();

        if ($failedAdminAttempts > 10) {
            $anomalies[] = [
                'type' => 'privilege_escalation',
                'severity' => 'high',
                'description' => 'Multiple failed attempts to access admin resources',
                'count' => $failedAdminAttempts,
                'event_data' => [
                    'failed_admin_attempts' => $failedAdminAttempts,
                ],
            ];
        }

        return $anomalies;
    }

    /**
     * Detect data access anomalies.
     */
    public function detectDataAccessAnomalies(): array
    {
        $anomalies = [];

        // Check for unusual data export patterns
        $dataExports = ActivityLog::where('action', 'file_download')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('user_id, COUNT(*) as download_count')
            ->groupBy('user_id')
            ->having('download_count', '>', 50)
            ->with('user')
            ->get();

        foreach ($dataExports as $export) {
            $anomalies[] = [
                'type' => 'unusual_data_export',
                'severity' => 'medium',
                'description' => "User {$export->user->name} downloaded {$export->download_count} files in 24 hours",
                'user_id' => $export->user_id,
                'download_count' => $export->download_count,
                'event_data' => [
                    'user_id' => $export->user_id,
                    'download_count' => $export->download_count,
                    'user_name' => $export->user->name,
                ],
            ];
        }

        return $anomalies;
    }

    /**
     * Process and categorize anomalies.
     */
    private function processAnomalies(array $anomalies): array
    {
        $processed = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => [],
            'summary' => [
                'total' => count($anomalies),
                'by_severity' => [
                    'critical' => 0,
                    'high' => 0,
                    'medium' => 0,
                    'low' => 0,
                ],
                'by_type' => [],
            ],
        ];

        foreach ($anomalies as $anomaly) {
            $severity = $anomaly['severity'];
            $type = $anomaly['type'];

            $processed[$severity][] = $anomaly;
            $processed['summary']['by_severity'][$severity]++;
            
            if (!isset($processed['summary']['by_type'][$type])) {
                $processed['summary']['by_type'][$type] = 0;
            }
            $processed['summary']['by_type'][$type]++;
        }

        // Sort anomalies by severity and timestamp
        foreach (['critical', 'high', 'medium', 'low'] as $severity) {
            usort($processed[$severity], function ($a, $b) {
                return $b['created_at'] ?? now() <=> ($a['created_at'] ?? now());
            });
        }

        return $processed;
    }

    /**
     * Get anomaly trends over time.
     */
    public function getAnomalyTrends(int $days = 7): array
    {
        $trends = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            
            $dayAnomalies = SecurityAudit::where('event_type', 'suspicious_activity')
                ->whereDate('created_at', $date)
                ->count();

            $dayFailedLogins = SecurityAudit::where('event_type', 'failed_login')
                ->whereDate('created_at', $date)
                ->count();

            $trends[$date] = [
                'date' => $date,
                'suspicious_activities' => $dayAnomalies,
                'failed_logins' => $dayFailedLogins,
                'total' => $dayAnomalies + $dayFailedLogins,
            ];
        }

        return $trends;
    }

    /**
     * Get top anomaly sources.
     */
    public function getTopAnomalySources(int $limit = 10): array
    {
        return [
            'top_ips' => SecurityAudit::whereIn('event_type', ['failed_login', 'suspicious_activity'])
                ->where('created_at', '>=', now()->subHours(24))
                ->selectRaw('ip_address, COUNT(*) as count')
                ->groupBy('ip_address')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get()
                ->toArray(),

            'top_users' => SecurityAudit::whereIn('event_type', ['failed_login', 'suspicious_activity'])
                ->whereNotNull('user_id')
                ->where('created_at', '>=', now()->subHours(24))
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->with('user')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get()
                ->toArray(),
        ];
    }
}
