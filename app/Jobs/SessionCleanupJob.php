<?php

namespace App\Jobs;

use App\Services\SessionManager;
use App\Services\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SessionCleanupJob implements ShouldQueue
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
    public function handle(SessionManager $sessionManager, ActivityLogger $activityLogger): void
    {
        $startTime = now();
        Log::info('Starting session cleanup job', ['start_time' => $startTime]);

        try {
            // Clean up expired sessions
            $expiredSessionsCount = $sessionManager->cleanupExpiredSessions();
            
            // Clean up old activity logs
            $retentionDays = config('security.audit.retention_days', 90);
            $oldLogsCount = $activityLogger->cleanupOldLogs($retentionDays);

            // Clean up resolved security audits older than retention period
            $resolvedAuditsCount = $this->cleanupResolvedAudits($retentionDays);

            // Clean up old completed transactions
            $completedTransactionsCount = $this->cleanupCompletedTransactions($retentionDays);

            $duration = $startTime->diffInSeconds(now());
            
            Log::info('Session cleanup job completed', [
                'duration_seconds' => $duration,
                'expired_sessions_cleaned' => $expiredSessionsCount,
                'old_logs_cleaned' => $oldLogsCount,
                'resolved_audits_cleaned' => $resolvedAuditsCount,
                'completed_transactions_cleaned' => $completedTransactionsCount,
            ]);

            // Log the cleanup activity
            $activityLogger->log([
                'action' => 'system_cleanup',
                'resource_type' => 'system',
                'request_data' => [
                    'expired_sessions' => $expiredSessionsCount,
                    'old_logs' => $oldLogsCount,
                    'resolved_audits' => $resolvedAuditsCount,
                    'completed_transactions' => $completedTransactionsCount,
                    'duration_seconds' => $duration,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Session cleanup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_seconds' => $startTime->diffInSeconds(now()),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Clean up resolved security audits.
     */
    private function cleanupResolvedAudits(int $retentionDays): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        return \App\Models\SecurityAudit::where('is_resolved', true)
            ->where('resolved_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Clean up completed transactions.
     */
    private function cleanupCompletedTransactions(int $retentionDays): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        return \App\Models\Transaction::whereIn('status', ['committed', 'rolled_back'])
            ->where('completed_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Session cleanup job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Send notification to administrators about the failure
        $this->notifyAdminsOfFailure($exception);
    }

    /**
     * Notify administrators of cleanup failure.
     */
    private function notifyAdminsOfFailure(\Throwable $exception): void
    {
        $recipients = config('security.intrusion_detection.alert_recipients', []);
        
        if (empty($recipients)) {
            return;
        }

        $message = "Session cleanup job failed: {$exception->getMessage()}";
        
        // In a real implementation, you would send email/SMS notifications
        Log::error('Admin notification needed', [
            'message' => $message,
            'recipients' => $recipients,
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['cleanup', 'security', 'sessions'];
    }
}
