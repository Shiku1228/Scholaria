<?php

namespace App\Services;

use App\Models\SecurityAudit;
use App\Models\ActivityLog;
use App\Models\UserSession;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SecurityReportService
{
    /**
     * Generate weekly security report.
     */
    public function generateWeeklyReport(): array
    {
        $startDate = now()->startOfWeek()->subWeek()->startOfDay();
        $endDate = now()->startOfWeek()->startOfDay()->subSecond();

        return $this->generateReport($startDate, $endDate, 'weekly');
    }

    /**
     * Generate monthly security report.
     */
    public function generateMonthlyReport(): array
    {
        $startDate = now()->startOfMonth()->subMonth()->startOfDay();
        $endDate = now()->startOfMonth()->startOfDay()->subSecond();

        return $this->generateReport($startDate, $endDate, 'monthly');
    }

    /**
     * Generate custom date range report.
     */
    public function generateCustomReport(Carbon $startDate, Carbon $endDate): array
    {
        return $this->generateReport($startDate, $endDate, 'custom');
    }

    /**
     * Generate comprehensive security report.
     */
    private function generateReport(Carbon $startDate, Carbon $endDate, string $period): array
    {
        Log::info("Generating {$period} security report", [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        $report = [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'generated_at' => now()->toDateTimeString(),
            'summary' => $this->generateSummary($startDate, $endDate),
            'authentication' => $this->generateAuthenticationReport($startDate, $endDate),
            'sessions' => $this->generateSessionReport($startDate, $endDate),
            'activities' => $this->generateActivityReport($startDate, $endDate),
            'anomalies' => $this->generateAnomalyReport($startDate, $endDate),
            'trends' => $this->generateTrendsReport($startDate, $endDate),
            'recommendations' => $this->generateRecommendations($startDate, $endDate),
        ];

        // Store report for historical analysis
        $this->storeReport($report);

        return $report;
    }

    /**
     * Generate executive summary.
     */
    private function generateSummary(Carbon $startDate, Carbon $endDate): array
    {
        $totalDays = $startDate->diffInDays($endDate) + 1;
        
        return [
            'total_days' => $totalDays,
            'total_security_events' => SecurityAudit::whereBetween('created_at', [$startDate, $endDate])->count(),
            'critical_events' => SecurityAudit::where('severity', 'critical')->whereBetween('created_at', [$startDate, $endDate])->count(),
            'high_events' => SecurityAudit::where('severity', 'high')->whereBetween('created_at', [$startDate, $endDate])->count(),
            'medium_events' => SecurityAudit::where('severity', 'medium')->whereBetween('created_at', [$startDate, $endDate])->count(),
            'low_events' => SecurityAudit::where('severity', 'low')->whereBetween('created_at', [$startDate, $endDate])->count(),
            'resolved_events' => SecurityAudit::where('is_resolved', true)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'pending_action' => SecurityAudit::where('requires_action', true)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'average_events_per_day' => round(SecurityAudit::whereBetween('created_at', [$startDate, $endDate])->count() / $totalDays, 2),
            'security_score' => $this->calculateSecurityScore($startDate, $endDate),
        ];
    }

    /**
     * Generate authentication report.
     */
    private function generateAuthenticationReport(Carbon $startDate, Carbon $endDate): array
    {
        $failedLogins = SecurityAudit::where('event_type', 'failed_login')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $successfulLogins = SecurityAudit::where('event_type', 'successful_login')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $uniqueIps = $failedLogins->pluck('ip_address')->unique();
        $uniqueUsernames = $failedLogins->pluck('event_data.username')->unique()->filter();

        return [
            'total_login_attempts' => $failedLogins->count() + $successfulLogins->count(),
            'failed_attempts' => $failedLogins->count(),
            'successful_attempts' => $successfulLogins->count(),
            'success_rate' => $this->calculateSuccessRate($failedLogins->count(), $successfulLogins->count()),
            'unique_ips_attempted' => $uniqueIps->count(),
            'unique_usernames_attempted' => $uniqueUsernames->count(),
            'top_failed_ips' => $this->getTopFailedIps($failedLogins, 10),
            'top_failed_usernames' => $this->getTopFailedUsernames($failedLogins, 10),
            'failed_login_trends' => $this->getFailedLoginTrends($startDate, $endDate),
            'blocked_ips_count' => $this->getBlockedIpsCount(),
        ];
    }

    /**
     * Generate session report.
     */
    private function generateSessionReport(Carbon $startDate, Carbon $endDate): array
    {
        $sessions = UserSession::whereBetween('created_at', [$startDate, $endDate])->get();
        $activeSessions = UserSession::where('is_active', true)->get();

        return [
            'total_sessions_created' => $sessions->count(),
            'sessions_active' => $activeSessions->count(),
            'sessions_expired' => $sessions->where('logout_at', '!=', null)->count(),
            'average_session_duration' => $this->calculateAverageSessionDuration($sessions),
            'unique_users' => $sessions->pluck('user_id')->unique()->count(),
            'top_device_types' => $this->getTopDeviceTypes($sessions),
            'top_browsers' => $this->getTopBrowsers($sessions),
            'top_platforms' => $this->getTopPlatforms($sessions),
            'concurrent_sessions_peak' => $this->getPeakConcurrentSessions($startDate, $endDate),
            'suspicious_sessions' => $this->getSuspiciousSessions($sessions),
        ];
    }

    /**
     * Generate activity report.
     */
    private function generateActivityReport(Carbon $startDate, Carbon $endDate): array
    {
        $activities = ActivityLog::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total_activities' => $activities->count(),
            'sensitive_activities' => $activities->where('is_sensitive', true)->count(),
            'unique_users_active' => $activities->pluck('user_id')->unique()->count(),
            'top_actions' => $this->getTopActions($activities),
            'top_resources' => $topResources = $this->getTopResources($activities),
            'activities_by_hour' => $this->getActivitiesByHour($activities),
            'encrypted_data_count' => $activities->whereNotNull('encrypted_data')->count(),
            'activity_velocity' => $this->calculateActivityVelocity($activities),
        ];
    }

    /**
     * Generate anomaly report.
     */
    private function generateAnomalyReport(Carbon $startDate, Carbon $endDate): array
    {
        $anomalies = SecurityAudit::where('event_type', 'suspicious_activity')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total_anomalies' => $anomalies->count(),
            'anomalies_by_type' => $this->getAnomaliesByType($anomalies),
            'anomalies_by_severity' => $this->getAnomaliesBySeverity($anomalies),
            'resolved_anomalies' => $anomalies->where('is_resolved', true)->count(),
            'unresolved_anomalies' => $anomalies->where('is_resolved', false)->count(),
            'anomaly_trends' => $this->getAnomalyTrends($startDate, $endDate),
            'top_anomaly_sources' => $this->getTopAnomalySources($anomalies),
            'anomaly_rate' => $this->calculateAnomalyRate($startDate, $endDate),
        ];
    }

    /**
     * Generate trends report.
     */
    private function generateTrendsReport(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'security_events_trend' => $this->getSecurityEventsTrend($startDate, $endDate),
            'failed_logins_trend' => $this->getFailedLoginsTrend($startDate, $endDate),
            'session_activity_trend' => $this->getSessionActivityTrend($startDate, $endDate),
            'anomaly_detection_trend' => $this->getAnomalyDetectionTrend($startDate, $endDate),
            'security_score_trend' => $this->getSecurityScoreTrend($startDate, $endDate),
        ];
    }

    /**
     * Generate recommendations.
     */
    private function generateRecommendations(Carbon $startDate, Carbon $endDate): array
    {
        $recommendations = [];
        
        $summary = $this->generateSummary($startDate, $endDate);
        
        // High failure rate recommendation
        if ($summary['success_rate'] < 90) {
            $recommendations[] = [
                'type' => 'authentication',
                'priority' => 'high',
                'title' => 'Improve Authentication Security',
                'description' => 'Login success rate is only ' . $summary['success_rate'] . '%. Consider implementing stronger password policies, MFA, or account lockout policies.',
                'actions' => [
                    'Review password complexity requirements',
                    'Implement multi-factor authentication',
                    'Consider account lockout after failed attempts',
                    'Monitor for credential stuffing attacks',
                ],
            ];
        }

        // High anomaly rate recommendation
        $anomalyRate = $this->calculateAnomalyRate($startDate, $endDate);
        if ($anomalyRate > 10) {
            $recommendations[] = [
                'type' => 'anomaly_detection',
                'priority' => 'medium',
                'title' => 'Review Anomaly Detection Thresholds',
                'description' => 'Anomaly detection rate is ' . $anomalyRate . '%. Consider adjusting sensitivity thresholds to reduce false positives.',
                'actions' => [
                    'Review anomaly detection rules',
                    'Adjust pattern recognition thresholds',
                    'Investigate recurring anomaly patterns',
                    'Update machine learning models if applicable',
                ],
            ];
        }

        // Session management recommendation
        $avgDuration = $this->calculateAverageSessionDuration(UserSession::whereBetween('created_at', [$startDate, $endDate])->get());
        if ($avgDuration > 480) { // More than 8 hours average
            $recommendations[] = [
                'type' => 'session_management',
                'priority' => 'low',
                'title' => 'Review Session Timeout Policies',
                'description' => 'Average session duration is ' . round($avgDuration / 60, 1) . ' hours. Consider reducing session timeout for better security.',
                'actions' => [
                    'Review session timeout configuration',
                    'Implement idle session detection',
                    'Consider shorter session lifetimes for sensitive areas',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate success rate.
     */
    private function calculateSuccessRate(int $failures, int $successes): float
    {
        $total = $failures + $successes;
        return $total > 0 ? round(($successes / $total) * 100, 2) : 0;
    }

    /**
     * Calculate average session duration.
     */
    private function calculateAverageSessionDuration(Collection $sessions): float
    {
        $durations = $sessions->map(function ($session) {
            return $session->login_at && $session->logout_at 
                ? $session->login_at->diffInMinutes($session->logout_at)
                : $session->login_at->diffInMinutes(now());
        })->filter()->values();
        // Deduct points for issues
        $score -= ($criticalEvents * 20); // -20 points per critical event
        $score -= ($highEvents * 10);     // -10 points per high event
        $score -= ($unresolvedEvents * 5);   // -5 points per unresolved event

            }

    /**
     * Get top failed IPs.
     */
    private function getTopFailedIps(Collection $failedLogins, int $limit = 10): array
    {
        return $failedLogins
            ->selectRaw('ip_address, COUNT(*) as count')
            ->groupBy('ip_address')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get top failed usernames.
     */
    private function getTopFailedUsernames(Collection $failedLogins, int $limit = 10): array
    {
        return $failedLogins
            ->selectRaw('event_data->username as username, COUNT(*) as count')
            ->groupBy('event_data->username')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get failed login trends.
     */
    private function getFailedLoginTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayTrends = SecurityAudit::where('event_type', 'failed_login')
                ->whereDate('created_at', $current->toDateString())
                ->count();

            $trends[] = [
                'date' => $current->toDateString(),
                'count' => $dayTrends,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Get blocked IPs count.
     */
    private function getBlockedIpsCount(): int
    {
        // This would integrate with your caching system
        return Cache::get('auth_blocked_ips_count', 0);
    }

    /**
     * Get top device types.
     */
    private function getTopDeviceTypes(Collection $sessions): array
    {
        return $sessions
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get top browsers.
     */
    private function getTopBrowsers(Collection $sessions): array
    {
        return $sessions
            ->selectRaw('browser, COUNT(*) as count')
            ->groupBy('browser')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get top platforms.
     */
    private function getTopPlatforms(Collection $sessions): array
    {
        return $sessions
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get peak concurrent sessions.
     */
    private function getPeakConcurrentSessions(Carbon $startDate, Carbon $endDate): int
    {
        $maxConcurrent = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $concurrent = UserSession::where('is_active', true)
                ->whereDate('created_at', $current->toDateString())
                ->count();

            $maxConcurrent = max($maxConcurrent, $concurrent);
            $current->addDay();
        }

        return $maxConcurrent;
    }

    /**
     * Get suspicious sessions.
     */
    private function getSuspiciousSessions(Collection $sessions): array
    {
        return $sessions
            ->where(function ($session) {
                // Multiple IPs for same user
                $sameUserSessions = UserSession::where('user_id', $session->user_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $session->id)
                    ->pluck('ip_address')
                    ->unique();

                return $sameUserSessions->count() > 2;
            })
            ->count();
    }

    /**
     * Get top actions.
     */
    private function getTopActions(Collection $activities): array
    {
        return $activities
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get top resources.
     */
    private function getTopResources(Collection $activities): array
    {
        return $activities
            ->selectRaw('resource_type, COUNT(*) as count')
            ->groupBy('resource_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get activities by hour.
     */
    private function getActivitiesByHour(Collection $activities): array
    {
        return $activities
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    /**
     * Calculate activity velocity.
     */
    private function calculateActivityVelocity(Collection $activities): array
    {
        $hourlyCounts = $this->getActivitiesByHour($activities);
        
        if (empty($hourlyCounts)) {
            return ['average' => 0, 'peak' => 0, 'peak_hour' => null];
        }

        $counts = array_column($hourlyCounts, 'count');
        $average = array_sum($counts) / count($counts);
        $peak = max($counts);
        $peakHour = array_search($peak, $counts);

        return [
            'average' => round($average, 2),
            'peak' => $peak,
            'peak_hour' => $peakHour !== false ? (int) $peakHour : null,
        ];
    }

    /**
     * Get anomalies by type.
     */
    private function getAnomaliesByType(Collection $anomalies): array
    {
        return $anomalies
            ->selectRaw('event_data->pattern_type as type, COUNT(*) as count')
            ->groupBy('event_data->pattern_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get anomalies by severity.
     */
    private function getAnomaliesBySeverity(Collection $anomalies): array
    {
        return $anomalies
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->orderByRaw('CASE severity WHEN "critical" THEN 1 WHEN "high" THEN 2 WHEN "medium" THEN 3 WHEN "low" THEN 4 ELSE 5 END')
            ->get()
            ->toArray();
    }

    /**
     * Get anomaly trends.
     */
    private function getAnomalyTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayAnomalies = SecurityAudit::where('event_type', 'suspicious_activity')
                ->whereDate('created_at', $current->toDateString())
                ->count();

            $trends[] = [
                'date' => $current->toDateString(),
                'count' => $dayAnomalies,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Get top anomaly sources.
     */
    private function getTopAnomalySources(Collection $anomalies): array
    {
        return [
            'top_ips' => $anomalies
                ->selectRaw('ip_address, COUNT(*) as count')
                ->groupBy('ip_address')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
            'top_users' => $anomalies
                ->whereNotNull('user_id')
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->with('user')
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Get security events trend.
     */
    private function getSecurityEventsTrend(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayEvents = SecurityAudit::whereDate('created_at', $current->toDateString())
                ->count();

            $trends[] = [
                'date' => $current->toDateString(),
                'count' => $dayEvents,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Get failed logins trend.
     */
    private function getFailedLoginsTrend(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayFailedLogins = SecurityAudit::where('event_type', 'failed_login')
                ->whereDate('created_at', $current->toDateString())
                ->count();

            $trends[] = [
                'date' => $current->toDateString(),
                'count' => $dayFailedLogins,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Get session activity trend.
     */
    private function getSessionActivityTrend(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $daySessions = UserSession::whereDate('created_at', $current->toDateString())
                ->count();

            $trends[] = [
                'date' => $current->toDateString(),
                'count' => $daySessions,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Get anomaly detection trend.
     */
    private function getAnomalyDetectionTrend(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayAnomalies = SecurityAudit::where('event_type', 'suspicious_activity')
                ->whereDate('created_at', $current->toDateString())
                ->count();

            $trends[] = [
                'date' => $current->toDateString(),
                'count' => $dayAnomalies,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Get security score trend.
     */
    private function getSecurityScoreTrend(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $score = $this->calculateSecurityScore($current, $current->copy()->endOfDay());

            $trends[] = [
                'date' => $current->toDateString(),
                'score' => $score,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Store report for historical analysis.
     */
    private function storeReport(array $report): void
    {
        try {
            // Store in database if you have a security_reports table
            // SecurityReport::create($report);
            
            // For now, store in cache
            $cacheKey = 'security_report_' . $report['period'] . '_' . $report['start_date'];
            Cache::put($cacheKey, $report, 86400); // 24 hours
            
            Log::info('Security report stored', [
                'period' => $report['period'],
                'cache_key' => $cacheKey,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store security report', [
                'error' => $e->getMessage(),
                'period' => $report['period'],
            ]);
        }
    }

    /**
     * Get historical reports.
     */
    public function getHistoricalReports(int $limit = 10): array
    {
        $reports = [];
        
        // Get recent reports from cache
        for ($i = 0; $i < $limit; $i++) {
            $weeklyKey = 'security_report_weekly_' . now()->subWeeks($i)->startOfWeek()->toDateString();
            $monthlyKey = 'security_report_monthly_' . now()->subMonths($i)->startOfMonth()->toDateString();
            
            if (Cache::has($weeklyKey)) {
                $reports[] = Cache::get($weeklyKey);
            } elseif (Cache::has($monthlyKey)) {
                $reports[] = Cache::get($monthlyKey);
            }
        }

        return array_filter($reports);
    }

    /**
     * Export report to PDF.
     */
    public function exportToPdf(array $report): string
    {
        // In production, use a PDF generation library like DomPDF
        // For now, return formatted text
        return $this->formatReportAsText($report);
    }

    /**
     * Format report as text.
     */
    private function formatReportAsText(array $report): string
    {
        $text = "SECURITY REPORT - " . strtoupper($report['period']) . "\n";
        $text .= "Generated: " . $report['generated_at'] . "\n";
        $text .= "Period: {$report['start_date']} to {$report['end_date']}\n\n";

        // Summary
        $text .= "EXECUTIVE SUMMARY\n";
        $text .= "================\n";
        $text .= "Total Security Events: {$report['summary']['total_security_events']}\n";
        $text .= "Critical Events: {$report['summary']['critical_events']}\n";
        $text .= "High Events: {$report['summary']['high_events']}\n";
        $text .= "Medium Events: {$report['summary']['medium_events']}\n";
        $text .= "Low Events: {$report['summary']['low_events']}\n";
        $text .= "Resolved Events: {$report['summary']['resolved_events']}\n";
        $text .= "Pending Action: {$report['summary']['pending_action']}\n";
        $text .= "Security Score: {$report['summary']['security_score']}/100\n";
        $text .= "Average Events/Day: {$report['summary']['average_events_per_day']}\n\n";

        // Authentication
        $text .= "AUTHENTICATION\n";
        $text .= "===============\n";
        $text .= "Total Login Attempts: {$report['authentication']['total_login_attempts']}\n";
        $text .= "Failed Attempts: {$report['authentication']['failed_attempts']}\n";
        $text .= "Successful Attempts: {$report['authentication']['successful_attempts']}\n";
        $text .= "Success Rate: {$report['authentication']['success_rate']}%\n";
        $text .= "Unique IPs Attempted: {$report['authentication']['unique_ips_attempted']}\n";
        $text .= "Unique Usernames Attempted: {$report['authentication']['unique_usernames_attempted']}\n";
        $text .= "Blocked IPs Count: {$report['authentication']['blocked_ips_count']}\n\n";

        // Sessions
        $text .= "SESSIONS\n";
        $text .= "========\n";
        $text .= "Total Sessions Created: {$report['sessions']['total_sessions_created']}\n";
        $text .= "Currently Active: {$report['sessions']['sessions_active']}\n";
        $text .= "Expired Sessions: {$report['sessions']['sessions_expired']}\n";
        $text .= "Average Session Duration: " . round($report['sessions']['average_session_duration'] / 60, 1) . " minutes\n";
        $text .= "Unique Users: {$report['sessions']['unique_users']}\n";
        $text .= "Peak Concurrent Sessions: {$report['sessions']['concurrent_sessions_peak']}\n";
        $text .= "Suspicious Sessions: {$report['sessions']['suspicious_sessions']}\n\n";

        // Activities
        $text .= "ACTIVITIES\n";
        $text .= "==========\n";
        $text .= "Total Activities: {$report['activities']['total_activities']}\n";
        $text .= "Sensitive Activities: {$report['activities']['sensitive_activities']}\n";
        $text .= "Unique Users Active: {$report['activities']['unique_users_active']}\n";
        $text .= "Encrypted Data Count: {$report['activities']['encrypted_data_count']}\n";
        
        // Anomalies
        $text .= "ANOMALIES\n";
        $text .= "==========\n";
        $text .= "Total Anomalies: {$report['anomalies']['total_anomalies']}\n";
        $text .= "Resolved Anomalies: {$report['anomalies']['resolved_anomalies']}\n";
        $text .= "Unresolved Anomalies: {$report['anomalies']['unresolved_anomalies']}\n";
        $text .= "Anomaly Rate: " . $this->calculateAnomalyRate($report['start_date'], $report['end_date']) . "%\n\n";

        // Recommendations
        $text .= "RECOMMENDATIONS\n";
        $text .= "================\n";
        foreach ($report['recommendations'] as $rec) {
            $text .= "• [{$rec['priority']}] {$rec['title']}\n";
            $text .= "  {$rec['description']}\n";
            $text .= "  Actions: " . implode(', ', $rec['actions']) . "\n\n";
        }

        return $text;
    }
}
