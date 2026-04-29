<?php

namespace App\Services;

use App\Models\SecurityAudit;
use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthenticationMonitor
{
    /**
     * Log a failed login attempt.
     */
    public function logFailedLogin(array $credentials, string $reason = 'Invalid credentials'): SecurityAudit
    {
        $request = Request::capture();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        
        // Try to find user by email/username for additional context
        $user = $this->findUserFromCredentials($credentials);
        
        // Generate device fingerprint
        $fingerprint = $this->generateFingerprint($request);
        
        // Check if this IP should be blocked
        $this->checkIpBlocking($ipAddress);
        
        // Create security audit record
        $audit = SecurityAudit::logFailedLogin([
            'user_id' => $user?->id,
            'username' => $credentials['email'] ?? $credentials['username'] ?? 'unknown',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'fingerprint' => $fingerprint,
            'reason' => $reason,
            'event_data' => [
                'credentials_provided' => $this->getCredentialsProvided($credentials),
                'geo_location' => $this->getLocationFromIp($ipAddress),
                'device_info' => $this->parseUserAgent($userAgent),
                'attempt_count' => $this->getIpAttemptCount($ipAddress),
                'user_exists' => $user ? true : false,
                'user_active' => $user?->deleted_at === null,
            ],
        ]);
        
        // Check for suspicious patterns
        $this->detectSuspiciousPatterns($audit);
        
        // Update rate limiting cache
        $this->updateRateLimitCache($ipAddress, $fingerprint);
        
        Log::warning('Failed login attempt detected', [
            'ip_address' => $ipAddress,
            'username' => $credentials['email'] ?? $credentials['username'] ?? 'unknown',
            'reason' => $reason,
            'user_exists' => $user ? true : false,
        ]);
        
        return $audit;
    }
    
    /**
     * Log a successful login for security monitoring.
     */
    public function logSuccessfulLogin(User $user): void
    {
        $request = Request::capture();
        $ipAddress = $request->ip();
        $fingerprint = $this->generateFingerprint($request);
        
        // Check for suspicious login patterns
        $this->checkSuspiciousLoginPatterns($user, $ipAddress, $fingerprint);
        
        // Clear failed login cache for this IP
        $this->clearFailedLoginCache($ipAddress);
        
        Log::info('Successful login', [
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'fingerprint' => substr($fingerprint, 0, 8) . '...', // Log partial fingerprint for security
        ]);
    }
    
    /**
     * Check if an IP should be blocked due to too many failed attempts.
     */
    public function isIpBlocked(string $ipAddress): bool
    {
        $cacheKey = "auth_blocked_ip:{$ipAddress}";
        return Cache::has($cacheKey);
    }
    
    /**
     * Get failed login attempts for an IP address.
     */
    public function getFailedAttempts(string $ipAddress, int $minutes = 15): array
    {
        $threshold = config('security.intrusion_detection.failed_login_threshold', 5);
        $window = config('security.intrusion_detection.failed_login_window_minutes', 15);
        
        $attempts = SecurityAudit::where('event_type', 'failed_login')
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subMinutes($window))
            ->orderBy('created_at', 'desc')
            ->get();
            
        return [
            'attempts' => $attempts,
            'count' => $attempts->count(),
            'threshold_reached' => $attempts->count() >= $threshold,
            'should_block' => $attempts->count() >= $threshold * 2, // Block at double threshold
            'recent_attempts' => $attempts->take(5),
        ];
    }
    
    /**
     * Get failed login attempts by username/email.
     */
    public function getFailedAttemptsByUsername(string $username, int $hours = 24): array
    {
        $attempts = SecurityAudit::where('event_type', 'failed_login')
            ->whereJsonContains('event_data->username', $username)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->get();
            
        $uniqueIps = $attempts->pluck('ip_address')->unique();
        $uniqueFingerprints = $attempts->pluck('fingerprint')->unique()->filter();
        
        return [
            'attempts' => $attempts,
            'count' => $attempts->count(),
            'unique_ips' => $uniqueIps->count(),
            'unique_fingerprints' => $uniqueFingerprints->count(),
            'suspicious' => $uniqueIps->count() > 3 || $uniqueFingerprints->count() > 2,
        ];
    }
    
    /**
     * Detect brute force attack patterns.
     */
    public function detectBruteForceAttack(string $ipAddress): array
    {
        $attempts = $this->getFailedAttempts($ipAddress, 60); // Check last hour
        
        $patterns = [];
        
        // Check for rapid successive attempts
        if ($attempts['count'] >= 20) {
            $patterns[] = [
                'type' => 'high_volume',
                'severity' => 'high',
                'description' => 'High volume of failed attempts detected',
                'count' => $attempts['count'],
            ];
        }
        
        // Check for attempts across multiple usernames
        $usernames = $attempts['attempts']->pluck('event_data.username')->unique();
        if ($usernames->count() >= 5) {
            $patterns[] = [
                'type' => 'username_rotation',
                'severity' => 'high',
                'description' => 'Failed attempts across multiple usernames',
                'username_count' => $usernames->count(),
            ];
        }
        
        // Check for consistent timing (automated attacks)
        $timeGaps = $this->calculateTimeGaps($attempts['attempts']);
        if ($this->isAutomatedPattern($timeGaps)) {
            $patterns[] = [
                'type' => 'automated_attack',
                'severity' => 'critical',
                'description' => 'Automated attack pattern detected',
                'time_gaps' => $timeGaps,
            ];
        }
        
        return $patterns;
    }
    
    /**
     * Generate security metrics for dashboard.
     */
    public function getSecurityMetrics(): array
    {
        $last24Hours = now()->subHours(24);
        $last7Days = now()->subDays(7);
        
        return [
            'failed_logins_24h' => SecurityAudit::where('event_type', 'failed_login')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'failed_logins_7d' => SecurityAudit::where('event_type', 'failed_login')
                ->where('created_at', '>=', $last7Days)
                ->count(),
            'blocked_ips' => Cache::get('auth_blocked_ips_count', 0),
            'suspicious_activities_24h' => SecurityAudit::where('event_type', 'suspicious_activity')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'unique_ips_24h' => SecurityAudit::where('created_at', '>=', $last24Hours)
                ->distinct('ip_address')
                ->count('ip_address'),
            'top_failed_ips' => $this->getTopFailedIps(10),
            'recent_security_events' => SecurityAudit::whereIn('event_type', ['failed_login', 'suspicious_activity', 'security_breach'])
                ->where('created_at', '>=', $last24Hours)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
    
    /**
     * Find user from credentials.
     */
    private function findUserFromCredentials(array $credentials): ?User
    {
        $email = $credentials['email'] ?? null;
        $username = $credentials['username'] ?? null;
        
        if ($email) {
            return User::where('email', $email)->first();
        }
        
        if ($username) {
            return User::where('email', $username)
                ->orWhere('username', $username)
                ->first();
        }
        
        return null;
    }
    
    /**
     * Generate device fingerprint.
     */
    private function generateFingerprint($request): string
    {
        $data = [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->header('Accept'),
        ];
        
        return md5(implode('|', $data));
    }
    
    /**
     * Get location from IP (placeholder implementation).
     */
    private function getLocationFromIp(string $ipAddress): array
    {
        // In production, integrate with a real IP geolocation service
        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'latitude' => null,
            'longitude' => null,
            'isp' => 'Unknown',
        ];
    }
    
    /**
     * Parse user agent string.
     */
    private function parseUserAgent(string $userAgent): array
    {
        $deviceType = 'desktop';
        $browser = 'unknown';
        $os = 'unknown';
        
        // Simple parsing - enhance with proper library in production
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
            $deviceType = 'mobile';
        }
        
        if (preg_match('/Chrome/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/Safari/i', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/Edge/i', $userAgent)) $browser = 'Edge';
        
        if (preg_match('/Windows/i', $userAgent)) $os = 'Windows';
        elseif (preg_match('/Mac/i', $userAgent)) $os = 'macOS';
        elseif (preg_match('/Linux/i', $userAgent)) $os = 'Linux';
        elseif (preg_match('/Android/i', $userAgent)) $os = 'Android';
        elseif (preg_match('/iOS/i', $userAgent)) $os = 'iOS';
        
        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }
    
    /**
     * Get credentials provided in the attempt.
     */
    private function getCredentialsProvided(array $credentials): array
    {
        $provided = [];
        
        if (isset($credentials['email'])) $provided[] = 'email';
        if (isset($credentials['username'])) $provided[] = 'username';
        if (isset($credentials['password'])) $provided[] = 'password';
        
        return $provided;
    }
    
    /**
     * Check IP blocking and update if necessary.
     */
    private function checkIpBlocking(string $ipAddress): void
    {
        $attempts = $this->getFailedAttempts($ipAddress);
        
        if ($attempts['should_block']) {
            $lockoutDuration = config('security.intrusion_detection.lockout_duration_minutes', 30);
            $cacheKey = "auth_blocked_ip:{$ipAddress}";
            
            Cache::put($cacheKey, true, $lockoutDuration * 60);
            
            // Update blocked IP counter
            Cache::increment('auth_blocked_ips_count');
            
            SecurityAudit::logSuspiciousActivity([
                'description' => 'IP address blocked due to excessive failed login attempts',
                'event_data' => [
                    'ip_address' => $ipAddress,
                    'attempt_count' => $attempts['count'],
                    'block_duration_minutes' => $lockoutDuration,
                ],
            ]);
        }
    }
    
    /**
     * Update rate limiting cache.
     */
    private function updateRateLimitCache(string $ipAddress, string $fingerprint): void
    {
        $window = config('security.intrusion_detection.failed_login_window_minutes', 15);
        
        // IP-based cache
        $ipCacheKey = "auth_attempts_ip:{$ipAddress}";
        Cache::put($ipCacheKey, now()->timestamp, $window * 60);
        
        // Fingerprint-based cache
        $fpCacheKey = "auth_attempts_fp:{$fingerprint}";
        Cache::put($fpCacheKey, now()->timestamp, $window * 60);
    }
    
    /**
     * Clear failed login cache for successful login.
     */
    private function clearFailedLoginCache(string $ipAddress): void
    {
        Cache::forget("auth_attempts_ip:{$ipAddress}");
        Cache::forget("auth_blocked_ip:{$ipAddress}");
    }
    
    /**
     * Get IP attempt count from cache.
     */
    private function getIpAttemptCount(string $ipAddress): int
    {
        $cacheKey = "auth_attempts_ip:{$ipAddress}";
        return Cache::get($cacheKey, 0);
    }
    
    /**
     * Detect suspicious patterns in failed login.
     */
    private function detectSuspiciousPatterns(SecurityAudit $audit): void
    {
        $patterns = $this->detectBruteForceAttack($audit->ip_address);
        
        foreach ($patterns as $pattern) {
            if ($pattern['severity'] === 'critical') {
                SecurityAudit::logSecurityBreach([
                    'description' => 'Critical security threat detected: ' . $pattern['description'],
                    'event_data' => array_merge($pattern, [
                        'original_audit_id' => $audit->id,
                    ]),
                ]);
            }
        }
    }
    
    /**
     * Check for suspicious login patterns.
     */
    private function checkSuspiciousLoginPatterns(User $user, string $ipAddress, string $fingerprint): void
    {
        // Check if this is a new location for the user
        $recentLogins = SecurityAudit::where('event_type', 'failed_login')
            ->where('user_id', $user->id)
            ->where('ip_address', '!=', $ipAddress)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
            
        if ($recentLogins > 0) {
            SecurityAudit::logSuspiciousActivity([
                'description' => 'Login from new IP after failed attempts from different location',
                'user_id' => $user->id,
                'event_data' => [
                    'new_ip' => $ipAddress,
                    'previous_failed_ips' => $recentLogins,
                ],
            ]);
        }
    }
    
    /**
     * Calculate time gaps between attempts.
     */
    private function calculateTimeGaps($attempts): array
    {
        $gaps = [];
        $timestamps = $attempts->pluck('created_at')->sort()->values();
        
        for ($i = 1; $i < $timestamps->count(); $i++) {
            $gap = $timestamps[$i-1]->diffInSeconds($timestamps[$i]);
            $gaps[] = $gap;
        }
        
        return $gaps;
    }
    
    /**
     * Check if time gaps indicate automated attacks.
     */
    private function isAutomatedPattern(array $timeGaps): bool
    {
        if (count($timeGaps) < 5) return false;
        
        // Check for consistent timing (low variance)
        $average = array_sum($timeGaps) / count($timeGaps);
        $variance = array_sum(array_map(function($gap) use ($average) {
            return pow($gap - $average, 2);
        }, $timeGaps)) / count($timeGaps);
        
        // Low variance with short average time indicates automation
        return $variance < 5 && $average < 10;
    }
    
    /**
     * Get top IPs with failed login attempts.
     */
    private function getTopFailedIps(int $limit = 10): array
    {
        return SecurityAudit::where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('ip_address, COUNT(*) as count')
            ->groupBy('ip_address')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
