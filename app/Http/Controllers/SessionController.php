<?php

namespace App\Http\Controllers;

use App\Services\SessionManager;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    protected $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
        $this->middleware('auth');
    }

    /**
     * Get current user's active sessions.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $sessions = $this->sessionManager->getActiveSessions($user->id);
        
        $sessionData = $sessions->map(function ($session) {
            return $session->getSummary();
        });

        return response()->json([
            'sessions' => $sessionData,
            'current_session_id' => session()->getId(),
        ]);
    }

    /**
     * Get all active sessions (admin only).
     */
    public function all(): JsonResponse
    {
        $this->authorize('view-sessions', UserSession::class);
        
        $sessions = $this->sessionManager->getAllActiveSessions();
        
        $sessionData = $sessions->map(function ($session) {
            return $session->getSummary();
        });

        return response()->json([
            'sessions' => $sessionData,
            'total_count' => $sessions->count(),
        ]);
    }

    /**
     * End a specific session.
     */
    public function end(Request $request, string $sessionId): JsonResponse
    {
        $user = Auth::user();
        
        // Users can only end their own sessions (except admins)
        if (!$user->hasPermissionTo('manage-sessions')) {
            $session = UserSession::where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->first();
                
            if (!$session) {
                return response()->json([
                    'error' => 'Session not found or access denied',
                ], 404);
            }
        }

        $success = $this->sessionManager->endSession($sessionId, 'manual_termination');
        
        if ($success) {
            return response()->json([
                'message' => 'Session ended successfully',
            ]);
        }

        return response()->json([
            'error' => 'Failed to end session',
        ], 500);
    }

    /**
     * End all user's sessions except current.
     */
    public function endAllOthers(): JsonResponse
    {
        $user = Auth::user();
        $currentSessionId = session()->getId();
        
        // Get all active sessions except current
        $sessions = UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('session_id', '!=', $currentSessionId)
            ->get();

        $endedCount = 0;
        foreach ($sessions as $session) {
            if ($this->sessionManager->endSession($session->session_id, 'bulk_termination')) {
                $endedCount++;
            }
        }

        return response()->json([
            'message' => "Ended {$endedCount} sessions",
            'ended_count' => $endedCount,
        ]);
    }

    /**
     * Force logout user from all sessions (admin only).
     */
    public function forceLogout(Request $request, int $userId): JsonResponse
    {
        $this->authorize('manage-sessions', UserSession::class);
        
        $endedCount = $this->sessionManager->endAllUserSessions($userId, 'admin_force_logout');
        
        return response()->json([
            'message' => "User {$userId} logged out from {$endedCount} sessions",
            'ended_count' => $endedCount,
        ]);
    }

    /**
     * Get session statistics.
     */
    public function stats(): JsonResponse
    {
        $this->authorize('view-sessions', UserSession::class);
        
        $totalActive = UserSession::where('is_active', true)->count();
        
        $byDeviceType = UserSession::where('is_active', true)
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->get()
            ->pluck('count', 'device_type');

        $byBrowser = UserSession::where('is_active', true)
            ->selectRaw('browser, COUNT(*) as count')
            ->groupBy('browser')
            ->get()
            ->pluck('count', 'browser');

        $recentActivity = UserSession::where('is_active', true)
            ->where('last_activity_at', '>=', now()->subMinutes(30))
            ->count();

        return response()->json([
            'total_active_sessions' => $totalActive,
            'recently_active' => $recentActivity,
            'by_device_type' => $byDeviceType,
            'by_browser' => $byBrowser,
        ]);
    }

    /**
     * Get session details.
     */
    public function show(string $sessionId): JsonResponse
    {
        $user = Auth::user();
        
        $session = UserSession::where('session_id', $sessionId)
            ->with('user')
            ->first();

        if (!$session) {
            return response()->json([
                'error' => 'Session not found',
            ], 404);
        }

        // Users can only view their own sessions (except admins)
        if (!$user->hasPermissionTo('view-sessions') && $session->user_id !== $user->id) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        return response()->json([
            'session' => $session->getSummary(),
        ]);
    }

    /**
     * Clean up expired sessions (admin only).
     */
    public function cleanup(): JsonResponse
    {
        $this->authorize('manage-sessions', UserSession::class);
        
        $cleanedCount = $this->sessionManager->cleanupExpiredSessions();
        
        return response()->json([
            'message' => "Cleaned up {$cleanedCount} expired sessions",
            'cleaned_count' => $cleanedCount,
        ]);
    }

    /**
     * Get real-time session updates.
     */
    public function realtime(): JsonResponse
    {
        $user = Auth::user();
        
        // Update current session activity
        $this->sessionManager->updateActivity(session()->getId());
        
        // Get current session info
        $currentSession = UserSession::where('session_id', session()->getId())
            ->with('user')
            ->first();

        if (!$currentSession) {
            return response()->json([
                'error' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'current_session' => $currentSession->getSummary(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
