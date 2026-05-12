<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class SecurityAudit extends Model
{
    use Notifiable;

    protected $fillable = [
        'event_type',
        'severity',
        'description',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'event_data',
        'fingerprint',
        'is_resolved',
        'resolved_by',
        'resolution_notes',
        'resolved_at',
        'requires_action',
        'action_taken_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'is_resolved' => 'boolean',
        'requires_action' => 'boolean',
        'resolved_at' => 'datetime',
        'action_taken_at' => 'datetime',
    ];

    /**
     * Get the user associated with the security event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who resolved the security event.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope to get unresolved events.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope to get events requiring action.
     */
    public function scopeRequiresAction($query)
    {
        return $query->where('requires_action', true);
    }

    /**
     * Scope to get events by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get events by type.
     */
    public function scopeByType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to get events from specific IP.
     */
    public function scopeFromIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Mark the event as resolved.
     */
    public function resolve(int $resolvedBy, string $notes = null): bool
    {
        return $this->update([
            'is_resolved' => true,
            'resolved_by' => $resolvedBy,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark the event as requiring action.
     */
    public function requireAction(): bool
    {
        return $this->update([
            'requires_action' => true,
            'action_taken_at' => now(),
        ]);
    }

    /**
     * Log a failed login attempt.
     */
    public static function logFailedLogin(array $data): self
    {
        return self::create([
            'event_type' => 'failed_login',
            'severity' => 'medium',
            'description' => 'Failed login attempt: ' . ($data['reason'] ?? 'Invalid credentials'),
            'user_id' => $data['user_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'event_data' => [
                'username' => $data['username'] ?? null,
                'reason' => $data['reason'] ?? 'Invalid credentials',
                'timestamp' => now()->toISOString(),
            ],
            'fingerprint' => $data['fingerprint'] ?? null,
            'requires_action' => self::shouldRequireAction($data['ip_address'] ?? request()->ip()),
        ]);
    }

    /**
     * Log suspicious activity.
     */
    public static function logSuspiciousActivity(array $data): self
    {
        return self::create([
            'event_type' => 'suspicious_activity',
            'severity' => $data['severity'] ?? 'high',
            'description' => $data['description'],
            'user_id' => $data['user_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'event_data' => $data['event_data'] ?? [],
            'fingerprint' => $data['fingerprint'] ?? null,
            'requires_action' => true,
        ]);
    }

    /**
     * Log a security breach.
     */
    public static function logSecurityBreach(array $data): self
    {
        return self::create([
            'event_type' => 'security_breach',
            'severity' => 'critical',
            'description' => $data['description'],
            'user_id' => $data['user_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'event_data' => $data['event_data'] ?? [],
            'fingerprint' => $data['fingerprint'] ?? null,
            'requires_action' => true,
        ]);
    }

    /**
     * Determine if action should be required based on failed attempts from IP.
     */
    private static function shouldRequireAction(string $ipAddress): bool
    {
        $threshold = config('security.intrusion_detection.failed_login_threshold', 5);
        $window = config('security.intrusion_detection.failed_login_window_minutes', 15);
        
        $recentFailures = self::where('event_type', 'failed_login')
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subMinutes($window))
            ->count();

        return $recentFailures >= $threshold;
    }

    /**
     * Get notification recipients for security alerts.
     */
    public function routeNotificationForMail($notification = null): array
    {
        return config('security.intrusion_detection.alert_recipients', []);
    }
}
