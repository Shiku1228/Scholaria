<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SecurityAlertService;
use App\Services\SecurityReportService;
use App\Services\AuthenticationMonitor;
use App\Models\SecurityAudit;
use App\Models\ActivityLog;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityDashboardController extends Controller
{
    protected $securityAlertService;
    protected $securityReportService;
    protected $authenticationMonitor;

    public function __construct(
        SecurityAlertService $securityAlertService,
        SecurityReportService $securityReportService,
        AuthenticationMonitor $authenticationMonitor
    ) {
        $this->securityAlertService = $securityAlertService;
        $this->securityReportService = $securityReportService;
        $this->authenticationMonitor = $authenticationMonitor;
    }

    /**
     * Display security dashboard.
     */
    public function index(Request $request)
    {
        $timeframe = $request->get('timeframe', '24h');
        $refreshInterval = $request->get('refresh', '30');

        // Get real-time metrics
        $metrics = $this->getSecurityMetrics($timeframe);
        $alerts = $this->securityAlertService->getRecentAlerts(20);
        $trends = $this->getSecurityTrends();

        // Get activity log data for dashboard
        $activityLogData = $this->getActivityLogData($timeframe);

        return view('admin.security-dashboard', compact(
            'metrics',
            'alerts',
            'trends',
            'timeframe',
            'refreshInterval',
            'activityLogData'
        ));
    }

    /**
     * Get security metrics data.
     */
    public function metrics(Request $request)
    {
        $timeframe = $request->get('timeframe', '24h');
        
        return response()->json($this->getSecurityMetrics($timeframe));
    }

    /**
     * Get recent security alerts.
     */
    public function alerts(Request $request)
    {
        $limit = $request->get('limit', 50);
        
        return response()->json($this->securityAlertService->getRecentAlerts($limit));
    }

    /**
     * Get security trends data.
     */
    public function trends(Request $request)
    {
        $period = $request->get('period', '7d');
        
        return response()->json($this->getSecurityTrends($period));
    }

    /**
     * Generate and download security report.
     */
    public function report(Request $request)
    {
        $type = $request->get('type', 'weekly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        try {
            if ($type === 'weekly') {
                $report = $this->securityReportService->generateWeeklyReport();
            } elseif ($type === 'monthly') {
                $report = $this->securityReportService->generateMonthlyReport();
            } elseif ($startDate && $endDate) {
                $report = $this->securityReportService->generateCustomReport(
                    Carbon::parse($startDate),
                    Carbon::parse($endDate)
                );
            } else {
                return response()->json(['error' => 'Invalid report type or missing dates'], 400);
            }

            return response()->json([
                'report' => $report,
                'download_url' => route('admin.security-report-download', ['type' => $type])
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate report: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download security report.
     */
    public function downloadReport(Request $request)
    {
        $type = $request->get('type', 'weekly');

        try {
            if ($type === 'weekly') {
                $report = $this->securityReportService->generateWeeklyReport();
            } elseif ($type === 'monthly') {
                $report = $this->securityReportService->generateMonthlyReport();
            } else {
                abort(400, 'Invalid report type');
            }

            $content = $this->securityReportService->exportToPdf($report);
            $filename = "security-report-{$type}-" . now()->format('Y-m-d') . ".txt";

            return response($content)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate report: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get detailed security event information.
     */
    public function event(Request $request, $id)
    {
        $event = SecurityAudit::with(['user', 'resolvedBy'])->find($id);
        
        if (!$event) {
            return response()->json(['error' => 'Security event not found'], 404);
        }

        return response()->json($event);
    }

    /**
     * Resolve security event.
     */
    public function resolveEvent(Request $request, $id)
    {
        $event = SecurityAudit::findOrFail($id);
        
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $event->resolve(Auth::id(), $validated['resolution_notes']);

        return response()->json([
            'message' => 'Security event resolved successfully',
            'event' => $event->fresh()
        ]);
    }

    /**
     * Get failed login attempts data.
     */
    public function failedLogins(Request $request)
    {
        $timeframe = $request->get('timeframe', '24h');
        $ipAddress = $request->get('ip_address');
        $username = $request->get('username');

        $attempts = $this->authenticationMonitor->getFailedAttempts($ipAddress, $this->getTimeframeMinutes($timeframe));

        return response()->json($attempts);
    }

    /**
     * Get active sessions data.
     */
    public function sessions(Request $request)
    {
        $timeframe = $request->get('timeframe', '24h');
        
        $sessions = UserSession::with('user')
            ->where('is_active', true)
            ->where('created_at', '>=', now()->subMinutes($this->getTimeframeMinutes($timeframe)))
            ->orderBy('last_activity_at', 'desc')
            ->get()
            ->map(function ($session) {
                return $session->getSummary();
            });

        return response()->json($sessions);
    }

    /**
     * Get anomaly detection data.
     */
    public function anomalies(Request $request)
    {
        $timeframe = $request->get('timeframe', '24h');
        $minutes = $this->getTimeframeMinutes($timeframe);

        $bruteForceAttacks = SecurityAudit::where('event_type', 'suspicious_activity')
            ->where('event_data->pattern_type', 'brute_force')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $unusualLogins = SecurityAudit::where('event_type', 'suspicious_activity')
            ->where('event_data->pattern_type', 'unusual_login')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $sessionAnomalies = SecurityAudit::where('event_type', 'suspicious_activity')
            ->where('event_data->pattern_type', 'session')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'brute_force_attacks' => $bruteForceAttacks,
            'unusual_logins' => $unusualLogins,
            'session_anomalies' => $sessionAnomalies,
        ]);
    }

    /**
     * Get activity log data.
     */
    public function activityLog(Request $request)
    {
        $timeframe = $request->get('timeframe', '24h');
        $limit = $request->get('limit', 50);
        $page = $request->get('page', 1);
        $eventType = $request->get('event_type');
        $severity = $request->get('severity');
        $userId = $request->get('user_id');
        $ipAddress = $request->get('ip_address');

        $query = ActivityLog::with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply timeframe filter
        if ($timeframe) {
            $minutes = $this->getTimeframeMinutes($timeframe);
            $query->where('created_at', '>=', now()->subMinutes($minutes));
        }

        // Apply filters
        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }

        // Paginate results
        $offset = ($page - 1) * $limit;
        $total = $query->count();
        $activities = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'activities' => $activities,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit),
            ],
            'filters' => [
                'timeframe' => $timeframe,
                'event_type' => $eventType,
                'severity' => $severity,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
            ]
        ]);
    }

    /**
     * Get activity log data for dashboard.
     */
    private function getActivityLogData(string $timeframe): array
    {
        $minutes = $this->getTimeframeMinutes($timeframe);
        $cutoff = now()->subMinutes($minutes);

        $activities = ActivityLog::with(['user'])
            ->where('created_at', '>=', $cutoff)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'recent_activities' => $activities,
            'total_activities' => ActivityLog::where('created_at', '>=', $cutoff)->count(),
        ];
    }

    /**
     * Get security metrics for dashboard.
     */
    private function getSecurityMetrics(string $timeframe): array
    {
        $minutes = $this->getTimeframeMinutes($timeframe);
        $cutoff = now()->subMinutes($minutes);

        return [
            'total_security_events' => SecurityAudit::where('created_at', '>=', $cutoff)->count(),
            'critical_events' => SecurityAudit::where('severity', 'critical')->where('created_at', '>=', $cutoff)->count(),
            'high_events' => SecurityAudit::where('severity', 'high')->where('created_at', '>=', $cutoff)->count(),
            'medium_events' => SecurityAudit::where('severity', 'medium')->where('created_at', '>=', $cutoff)->count(),
            'low_events' => SecurityAudit::where('severity', 'low')->where('created_at', '>=', $cutoff)->count(),
            'unresolved_events' => SecurityAudit::where('requires_action', true)->where('created_at', '>=', $cutoff)->count(),
            'failed_logins_24h' => SecurityAudit::where('event_type', 'failed_login')->where('created_at', '>=', $cutoff)->count(),
            'suspicious_activities_24h' => SecurityAudit::where('event_type', 'suspicious_activity')->where('created_at', '>=', $cutoff)->count(),
            'active_sessions' => UserSession::where('is_active', true)->count(),
            'blocked_ips' => $this->getBlockedIpsCount(),
            'unique_ips_24h' => SecurityAudit::where('created_at', '>=', $cutoff)->distinct('ip_address')->count('ip_address'),
            'security_score' => $this->calculateSecurityScore($cutoff),
        ];
    }

    /**
     * Get security trends for dashboard.
     */
    private function getSecurityTrends(string $period = '7d'): array
    {
        $days = $period === '7d' ? 7 : ($period === '30d' ? 30 : 1);
        $startDate = now()->subDays($days);

        return [
            'security_events_trend' => $this->getDailyTrend('security_events', $startDate),
            'failed_logins_trend' => $this->getDailyTrend('failed_logins', $startDate),
            'suspicious_activities_trend' => $this->getDailyTrend('suspicious_activities', $startDate),
            'security_score_trend' => $this->getSecurityScoreTrend($startDate),
        ];
    }

    /**
     * Get daily trend data.
     */
    private function getDailyTrend(string $type, Carbon $startDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        for ($i = 0; $i < 7; $i++) {
            $date = $current->toDateString();
            
            switch ($type) {
                case 'security_events':
                    $count = SecurityAudit::whereDate('created_at', $date)->count();
                    break;
                case 'failed_logins':
                    $count = SecurityAudit::where('event_type', 'failed_login')->whereDate('created_at', $date)->count();
                    break;
                case 'suspicious_activities':
                    $count = SecurityAudit::where('event_type', 'suspicious_activity')->whereDate('created_at', $date)->count();
                    break;
                default:
                    $count = 0;
            }

            $trends[] = [
                'date' => $date,
                'count' => $count,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Get security score trend.
     */
    private function getSecurityScoreTrend(Carbon $startDate): array
    {
        $trends = [];
        $current = $startDate->copy();

        for ($i = 0; $i < 7; $i++) {
            $date = $current->toDateString();
            $score = $this->calculateSecurityScore($current->copy()->endOfDay());
            
            $trends[] = [
                'date' => $date,
                'score' => $score,
            ];

            $current->addDay();
        }

        return $trends;
    }

    /**
     * Calculate security score.
     */
    private function calculateSecurityScore(Carbon $cutoff): int
    {
        $criticalEvents = SecurityAudit::where('severity', 'critical')->where('created_at', '>=', $cutoff)->count();
        $highEvents = SecurityAudit::where('severity', 'high')->where('created_at', '>=', $cutoff)->count();
        $unresolvedEvents = SecurityAudit::where('requires_action', true)->where('created_at', '>=', $cutoff)->count();

        // Base score of 100
        $score = 100;

        // Deduct points for security issues
        $score -= ($criticalEvents * 20); // -20 points per critical event
        $score -= ($highEvents * 10);     // -10 points per high event
        $score -= ($unresolvedEvents * 5);   // -5 points per unresolved event

        return max(0, min(100, $score));
    }

    /**
     * Get blocked IPs count.
     */
    private function getBlockedIpsCount(): int
    {
        // This would integrate with your caching system
        return DB::table('cache')
            ->where('key', 'like', 'auth_blocked_ip:%')
            ->count();
    }

    /**
     * Convert timeframe to minutes.
     */
    private function getTimeframeMinutes(string $timeframe): int
    {
        return match ($timeframe) {
            '1h' => 60,
            '6h' => 360,
            '24h' => 1440,
            '7d' => 10080,
            '30d' => 43200,
            default => 1440,
        };
    }
}
