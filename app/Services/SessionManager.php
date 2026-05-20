<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Models\SecurityAudit;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SessionManager
{
    /**
     * Create a new user session.
     */
    public function createSession(User $user, string $sessionId = null): UserSession
    {
        $request = request();
        
        // Check for existing active sessions and enforce limits
        $this->enforceSessionLimit($user);
        
        $userAgent = $request->userAgent() ?? 'Unknown';
        $ipAddress = $request->ip() ?? '127.0.0.1';
        
        // Parse user agent to extract device info
        $deviceInfo = $this->parseUserAgent($userAgent);
        
        $sessionData = [
            'user_id' => $user->id,
            'session_id' => $sessionId ?? session()->getId(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'is_active' => true,
            'last_activity_at' => now(),
            'login_at' => now(),
            'session_data' => [
                'fingerprint' => $this->generateFingerprint($request),
                'location' => $this->getLocationFromIp($ipAddress),
            ],
        ];
        
        $session = UserSession::create($sessionData);
        
        // Log the session creation
        \App\Models\ActivityLog::log([
            'user_id' => $user->id,
            'session_id' => $session->session_id,
            'action' => 'login',
            'resource_type' => 'user_session',
            'resource_id' => $session->id,
            'request_data' => [
                'ip_address' => $ipAddress,
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
            ],
        ]);
        
        Log::info('User session created', [
            'user_id' => $user->id,
            'session_id' => $session->session_id,
            'ip_address' => $ipAddress,
        ]);
        
        return $session;
    }
    
    /**
     * Update session activity timestamp.
     */
    public function updateActivity(string $sessionId): bool
    {
        $session = UserSession::where('session_id', $sessionId)
            ->where('is_active', true)
            ->first();
            
        if (!$session) {
            return false;
        }
        
        $session->update([
            'last_activity_at' => now(),
        ]);
        
        return true;
    }
    
    /**
     * End a user session.
     */
    public function endSession(string $sessionId, string $reason = 'logout'): bool
    {
        $session = UserSession::where('session_id', $sessionId)
            ->where('is_active', true)
            ->first();
            
        if (!$session) {
            return false;
        }
        
        $session->update([
            'is_active' => false,
            'logout_at' => now(),
            'logout_reason' => $reason,
        ]);
        
        // Log the session end
        \App\Models\ActivityLog::log([
            'user_id' => $session->user_id,
            'session_id' => $session->session_id,
            'action' => 'logout',
            'resource_type' => 'user_session',
            'resource_id' => $session->id,
            'request_data' => [
                'reason' => $reason,
                'duration' => $session->login_at->diffInMinutes(now()),
            ],
        ]);
        
        Log::info('User session ended', [
            'user_id' => $session->user_id,
            'session_id' => $session->session_id,
            'reason' => $reason,
        ]);
        
        return true;
    }
    
    /**
     * Get active sessions for a user.
     */
    public function getActiveSessions(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return UserSession::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }
    
    /**
     * Get all active sessions in the system.
     */
    public function getAllActiveSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return UserSession::where('is_active', true)
            ->with('user')
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }
    
    /**
     * Check if session is valid and active.
     */
    public function isSessionValid(string $sessionId, int $userId): bool
    {
        $session = UserSession::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
            
        if (!$session) {
            return false;
        }
        
        // Check session timeout
        $timeout = config('security.session.timeout', 120);
        $lastActivity = $session->last_activity_at;
        
        if ($lastActivity->diffInMinutes(now()) > $timeout) {
            $this->endSession($sessionId, 'timeout');
            return false;
        }
        
        // Check IP consistency if required
        if (config('security.session.require_ip_consistency', true)) {
            $currentIp = Request::ip() ?? '127.0.0.1';
            if ($session->ip_address !== $currentIp) {
                $this->endSession($sessionId, 'ip_mismatch');
                SecurityAudit::logSuspiciousActivity([
                    'description' => 'Session IP address mismatch',
                    'user_id' => $userId,
                    'event_data' => [
                        'session_id' => $sessionId,
                        'original_ip' => $session->ip_address,
                        'current_ip' => $currentIp,
                    ],
                ]);
                return false;
            }
        }
        
        // Check user agent consistency if required
        if (config('security.session.require_user_agent_consistency', true)) {
            $currentUserAgent = Request::userAgent() ?? 'Unknown';
            if ($session->user_agent !== $currentUserAgent) {
                $this->endSession($sessionId, 'user_agent_mismatch');
                SecurityAudit::logSuspiciousActivity([
                    'description' => 'Session user agent mismatch',
                    'user_id' => $userId,
                    'event_data' => [
                        'session_id' => $sessionId,
                        'original_ua' => $session->user_agent,
                        'current_ua' => $currentUserAgent,
                    ],
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * End all sessions for a user.
     */
    public function endAllUserSessions(int $userId, string $reason = 'force_logout'): int
    {
        $sessions = UserSession::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
            
        $count = 0;
        foreach ($sessions as $session) {
            $this->endSession($session->session_id, $reason);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Clean up expired sessions.
     */
    public function cleanupExpiredSessions(): int
    {
        $timeout = config('security.session.timeout', 120);
        $cutoffTime = now()->subMinutes($timeout);
        
        $expiredSessions = UserSession::where('is_active', true)
            ->where('last_activity_at', '<', $cutoffTime)
            ->get();
            
        $count = 0;
        foreach ($expiredSessions as $session) {
            $this->endSession($session->session_id, 'timeout');
            $count++;
        }
        
        Log::info('Expired sessions cleaned up', ['count' => $count]);
        
        return $count;
    }
    
    /**
     * Enforce maximum concurrent sessions per user.
     */
    private function enforceSessionLimit(User $user): void
    {
        $maxSessions = config('security.session.max_concurrent_sessions', 3);
        $activeSessions = $this->getActiveSessions($user->id);
        
        if ($activeSessions->count() >= $maxSessions) {
            // End the oldest session
            $oldestSession = $activeSessions->last();
            $this->endSession($oldestSession->session_id, 'session_limit_exceeded');
        }
    }
    
    /**
     * Parse user agent string to extract device information.
     */
    private function parseUserAgent(?string $userAgent): array
    {
        $userAgent = $userAgent ?? '';
        $deviceType = 'desktop';
        $browser = 'unknown';
        $platform = 'unknown';
        
        // Simple user agent parsing (can be enhanced with a proper library)
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/Tablet/i', $userAgent)) {
            $deviceType = 'tablet';
        }
        
        if (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }
        
        if (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $platform = 'iOS';
        }
        
        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }
    
    /**
     * Generate a unique fingerprint for the session.
     */
    private function generateFingerprint($request): string
    {
        $data = [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
        ];
        
        return md5(implode('|', $data));
    }
    
    /**
     * Get location information from IP address.
     */
    private function getLocationFromIp(?string $ipAddress): array
    {
        // This is a placeholder - implement actual IP geolocation service
        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'latitude' => null,
            'longitude' => null,
        ];
    }
}
