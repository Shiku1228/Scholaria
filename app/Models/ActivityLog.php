<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'action',
        'event_type',
        'description',
        'severity',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'request_data',
        'encrypted_data',
        'encryption_key',
        'is_sensitive',
        'created_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'encrypted_data' => 'encrypted',
        'is_sensitive' => 'boolean',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the resource that was acted upon.
     */
    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Encrypt sensitive data before storing.
     */
    public function setEncryptedDataAttribute($value)
    {
        if ($value && config('security.audit.encrypt_sensitive_data')) {
            $this->attributes['encrypted_data'] = Crypt::encrypt($value);
            $this->attributes['encryption_key'] = 'default'; // Can be enhanced with key rotation
        }
    }

    /**
     * Decrypt sensitive data when retrieving.
     */
    public function getEncryptedDataAttribute($value)
    {
        if ($value && config('security.audit.encrypt_sensitive_data')) {
            return Crypt::decrypt($value);
        }
        return $value;
    }

    /**
     * Scope to get sensitive logs only.
     */
    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope to get logs by action type.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get logs by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get logs within date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if the activity involves sensitive fields.
     */
    public static function isSensitiveAction(array $requestData): bool
    {
        $sensitiveFields = config('security.audit.sensitive_fields', []);
        $dataKeys = array_keys($requestData);
        
        return !empty(array_intersect($dataKeys, $sensitiveFields));
    }

    /**
     * Generate human-readable description for activity.
     */
    public static function generateDescription(array $data): string
    {
        $action = $data['action'] ?? 'unknown';
        $resourceType = $data['resource_type'] ?? 'resource';
        
        return match($action) {
            'login' => 'User logged in to the system',
            'logout' => 'User logged out from the system',
            'view' => "Viewed {$resourceType} details",
            'list' => "Listed {$resourceType}s",
            'create' => "Created new {$resourceType}",
            'update' => "Updated {$resourceType}",
            'delete' => "Deleted {$resourceType}",
            'download' => "Downloaded {$resourceType}",
            'upload' => "Uploaded {$resourceType}",
            'access' => "Accessed {$resourceType}",
            default => "Performed {$action} on {$resourceType}",
        };
    }

    /**
     * Determine severity level based on action.
     */
    public static function determineSeverity(string $action): string
    {
        return match($action) {
            'login', 'logout' => 'info',
            'view', 'list' => 'low',
            'create', 'update' => 'medium',
            'delete' => 'high',
            'download', 'upload' => 'medium',
            'access' => 'low',
            default => 'info',
        };
    }

    /**
     * Log an activity.
     */
    public static function log(array $data): self
    {
        $isSensitive = self::isSensitiveAction($data['request_data'] ?? []);
        
        $logData = [
            'user_id' => $data['user_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'action' => $data['action'],
            'event_type' => $data['event_type'] ?? $data['action'],
            'description' => $data['description'] ?? self::generateDescription($data),
            'severity' => $data['severity'] ?? self::determineSeverity($data['action']),
            'resource_type' => $data['resource_type'] ?? null,
            'resource_id' => $data['resource_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'request_data' => $isSensitive ? null : ($data['request_data'] ?? null),
            'encrypted_data' => $isSensitive ? ($data['request_data'] ?? null) : null,
            'is_sensitive' => $isSensitive,
            'created_at' => now(),
        ];

        return self::create($logData);
    }
}
