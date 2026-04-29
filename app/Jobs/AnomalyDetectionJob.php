<?php

namespace App\Jobs;

use App\Services\AnomalyDetectionService;
use App\Services\AuthenticationMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnomalyDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(AnomalyDetectionService $anomalyService, AuthenticationMonitor $authMonitor): void
    {
        $startTime = now();
        Log::info('Starting anomaly detection job', ['start_time' => $startTime]);

        try {
            // Run comprehensive anomaly detection
            $anomalies = $anomalyService->detectAnomalies();
            
            // Log summary of detected anomalies
            $this->logAnomalySummary($anomalies);
            
            // Process critical anomalies immediately
            $this->processCriticalAnomalies($anomalies['critical'] ?? []);
            
            // Update security metrics cache
            $this->updateSecurityMetrics($anomalyService, $authMonitor);
            
            // Generate alerts if needed
            $this->generateAlerts($anomalies);

            $duration = $startTime->diffInSeconds(now());
            
            Log::info('Anomaly detection job completed', [
                'duration_seconds' => $duration,
                'total_anomalies' => $anomalies['summary']['total'] ?? 0,
                'critical_anomalies' => count($anomalies['critical'] ?? []),
                'high_anomalies' => count($anomalies['high'] ?? []),
                'medium_anomalies' => count($anomalies['medium'] ?? []),
            ]);

        } catch (\Exception $e) {
            Log::error('Anomaly detection job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_seconds' => $startTime->diffInSeconds(now()),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Log anomaly summary.
     */
    private function logAnomalySummary(array $anomalies): void
    {
        $summary = $anomalies['summary'] ?? [];
        
        if ($summary['total'] > 0) {
            Log::warning('Security anomalies detected', [
                'total_anomalies' => $summary['total'],
                'by_severity' => $summary['by_severity'],
                'by_type' => $summary['by_type'],
            ]);

            // Log critical anomalies separately for immediate attention
            if (!empty($anomalies['critical'])) {
                foreach ($anomalies['critical'] as $anomaly) {
                    Log::critical('Critical security anomaly', [
                        'type' => $anomaly['type'],
                        'description' => $anomaly['description'],
                        'ip_address' => $anomaly['ip_address'] ?? null,
                        'user_id' => $anomaly['user_id'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Process critical anomalies immediately.
     */
    private function processCriticalAnomalies(array $criticalAnomalies): void
    {
        foreach ($criticalAnomalies as $anomaly) {
            try {
                // Create security audit record for critical anomalies
                \App\Models\SecurityAudit::logSecurityBreach([
                    'description' => $anomaly['description'],
                    'user_id' => $anomaly['user_id'] ?? null,
                    'event_data' => $anomaly['event_data'] ?? [],
                    'requires_action' => true,
                ]);

                // Take immediate action based on anomaly type
                $this->takeImmediateAction($anomaly);

            } catch (\Exception $e) {
                Log::error('Failed to process critical anomaly', [
                    'anomaly' => $anomaly,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Take immediate action on critical anomalies.
     */
    private function takeImmediateAction(array $anomaly): void
    {
        switch ($anomaly['type']) {
            case 'brute_force_ip':
                // Block the IP immediately
                $this->blockIpAddress($anomaly['ip_address']);
                break;

            case 'multi_location_sessions':
                // End suspicious sessions
                $this->endUserSessions($anomaly['user_id']);
                break;

            case 'privilege_escalation':
                // Notify administrators immediately
                $this->notifyAdministrators($anomaly);
                break;
        }
    }

    /**
     * Block an IP address.
     */
    private function blockIpAddress(string $ipAddress): void
    {
        $cacheKey = "auth_blocked_ip:{$ipAddress}";
        $lockoutDuration = config('security.intrusion_detection.lockout_duration_minutes', 30);
        
        \Cache::put($cacheKey, true, $lockoutDuration * 60);
        
        Log::warning('IP address blocked due to critical anomaly', [
            'ip_address' => $ipAddress,
            'duration_minutes' => $lockoutDuration,
        ]);
    }

    /**
     * End all user sessions.
     */
    private function endUserSessions(int $userId): void
    {
        $sessionManager = app(\App\Services\SessionManager::class);
        $endedCount = $sessionManager->endAllUserSessions($userId, 'security_violation');
        
        Log::warning('User sessions terminated due to security anomaly', [
            'user_id' => $userId,
            'sessions_ended' => $endedCount,
        ]);
    }

    /**
     * Notify administrators.
     */
    private function notifyAdministrators(array $anomaly): void
    {
        $recipients = config('security.intrusion_detection.alert_recipients', []);
        
        if (empty($recipients)) {
            return;
        }

        // In production, implement actual email/SMS notification
        Log::critical('Administrator notification required', [
            'anomaly' => $anomaly,
            'recipients' => $recipients,
        ]);
    }

    /**
     * Update security metrics cache.
     */
    private function updateSecurityMetrics(AnomalyDetectionService $anomalyService, AuthenticationMonitor $authMonitor): void
    {
        try {
            $metrics = array_merge(
                $authMonitor->getSecurityMetrics(),
                [
                    'anomaly_trends' => $anomalyService->getAnomalyTrends(7),
                    'top_sources' => $anomalyService->getTopAnomalySources(10),
                ]
            );

            // Cache metrics for dashboard
            \Cache::put('security_metrics', $metrics, 300); // 5 minutes

        } catch (\Exception $e) {
            Log::error('Failed to update security metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate alerts for anomalies.
     */
    private function generateAlerts(array $anomalies): void
    {
        $alertThresholds = config('security.alerts.thresholds', [
            'critical_per_hour' => 1,
            'high_per_hour' => 5,
            'total_per_hour' => 10,
        ]);

        $lastHour = now()->subHour();
        $recentCritical = count(array_filter($anomalies['critical'] ?? [], function ($a) use ($lastHour) {
            return isset($a['created_at']) && $a['created_at'] >= $lastHour;
        }));

        $recentHigh = count(array_filter($anomalies['high'] ?? [], function ($a) use ($lastHour) {
            return isset($a['created_at']) && $a['created_at'] >= $lastHour;
        }));

        $totalRecent = ($recentCritical + $recentHigh + count($anomalies['medium'] ?? []));

        // Generate alerts based on thresholds
        if ($recentCritical >= $alertThresholds['critical_per_hour']) {
            $this->createAlert('critical', "Critical anomalies detected: {$recentCritical} in the last hour");
        }

        if ($recentHigh >= $alertThresholds['high_per_hour']) {
            $this->createAlert('high', "High severity anomalies detected: {$recentHigh} in the last hour");
        }

        if ($totalRecent >= $alertThresholds['total_per_hour']) {
            $this->createAlert('medium', "Total anomalies detected: {$totalRecent} in the last hour");
        }
    }

    /**
     * Create a security alert.
     */
    private function createAlert(string $severity, string $message): void
    {
        Log::alert("Security Alert [{$severity}]: {$message}");

        // Store alert for dashboard
        \Cache::push("security_alerts", [
            'severity' => $severity,
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Anomaly detection job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['security', 'anomaly-detection', 'monitoring'];
    }
}
