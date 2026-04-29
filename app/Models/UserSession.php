<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'is_active',
        'last_activity_at',
        'login_at',
        'logout_at',
        'logout_reason',
        'session_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'session_data' => 'array',
    ];

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get sessions by device type.
     */
    public function scopeByDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope to get sessions by browser.
     */
    public function scopeByBrowser($query, string $browser)
    {
        return $query->where('browser', $browser);
    }

    /**
     * Scope to get sessions from specific IP.
     */
    public function scopeFromIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope to get sessions active within last N minutes.
     */
    public function scopeRecentlyActive($query, int $minutes = 5)
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Check if session is expired.
     */
    public function isExpired(): bool
    {
        $timeout = config('security.session.timeout', 120);
        return $this->last_activity_at->diffInMinutes(now()) > $timeout;
    }

    /**
     * Get session duration in minutes.
     */
    public function getDuration(): int
    {
        $endTime = $this->logout_at ?: now();
        return $this->login_at->diffInMinutes($endTime);
    }

    /**
     * Get session duration in human readable format.
     */
    public function getDurationHuman(): string
    {
        $duration = $this->getDuration();
        
        if ($duration < 60) {
            return $duration . ' minutes';
        } elseif ($duration < 1440) {
            return round($duration / 60, 1) . ' hours';
        } else {
            return round($duration / 1440, 1) . ' days';
        }
    }

    /**
     * Get session status with color.
     */
    public function getStatusWithColor(): array
    {
        if (!$this->is_active) {
            return ['status' => 'Inactive', 'color' => 'red'];
        }

        if ($this->isExpired()) {
            return ['status' => 'Expired', 'color' => 'orange'];
        }

        $minutesSinceActivity = $this->last_activity_at->diffInMinutes(now());
        
        if ($minutesSinceActivity < 5) {
            return ['status' => 'Active', 'color' => 'green'];
        } elseif ($minutesSinceActivity < 30) {
            return ['status' => 'Idle', 'color' => 'yellow'];
        } else {
            return ['status' => 'Inactive', 'color' => 'orange'];
        }
    }

    /**
     * Get location information from session data.
     */
    public function getLocation(): array
    {
        return $this->session_data['location'] ?? [
            'country' => 'Unknown',
            'city' => 'Unknown',
        ];
    }

    /**
     * Get device fingerprint from session data.
     */
    public function getFingerprint(): ?string
    {
        return $this->session_data['fingerprint'] ?? null;
    }

    /**
     * Check if this is the current user's session.
     */
    public function isCurrentSession(): bool
    {
        return $this->session_id === session()->getId();
    }

    /**
     * Format the login time for display.
     */
    public function getFormattedLoginAt(): string
    {
        return $this->login_at->format('M j, Y H:i:s');
    }

    /**
     * Format the last activity time for display.
     */
    public function getFormattedLastActivity(): string
    {
        return $this->last_activity_at->diffForHumans();
    }

    /**
     * Get a summary of the session for display.
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'user' => $this->user->name ?? 'Unknown',
            'device' => "{$this->browser} on {$this->platform}",
            'device_type' => $this->device_type,
            'ip_address' => $this->ip_address,
            'location' => $this->getLocation()['city'] . ', ' . $this->getLocation()['country'],
            'status' => $this->getStatusWithColor(),
            'duration' => $this->getDurationHuman(),
            'login_time' => $this->getFormattedLoginAt(),
            'last_activity' => $this->getFormattedLastActivity(),
            'is_current' => $this->isCurrentSession(),
        ];
    }
}
