<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration for the application.
    | Includes encryption settings, session management, and audit logging.
    |
    */

    'encryption' => [
        'algorithm' => env('SECURITY_ENCRYPTION_ALGORITHM', 'AES-256-CBC'),
        'key' => env('SECURITY_ENCRYPTION_KEY'),
        'iv' => env('SECURITY_ENCRYPTION_IV'),
    ],

    'session' => [
        'timeout' => env('SESSION_TIMEOUT_MINUTES', 120),
        'max_concurrent_sessions' => env('MAX_CONCURRENT_SESSIONS', 3),
        // Local/ngrok testing may need this disabled because the apparent
        // client IP can legitimately change behind a trusted proxy.
        'require_ip_consistency' => env('REQUIRE_IP_CONSISTENCY', true),
        // Keep user-agent checks enabled by default; only relax if your proxy
        // setup proves it causes false logouts too.
        'require_user_agent_consistency' => env('REQUIRE_USER_AGENT_CONSISTENCY', true),
    ],

    'audit' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
        'encrypt_sensitive_data' => env('ENCRYPT_SENSITIVE_AUDIT_DATA', true),
        'sensitive_fields' => [
            'password',
            'email',
            'phone',
            'ssn',
            'credit_card',
            'api_key',
            'token',
        ],
    ],

    'intrusion_detection' => [
        'enabled' => env('INTRUSION_DETECTION_ENABLED', true),
        'failed_login_threshold' => env('FAILED_LOGIN_THRESHOLD', 5),
        'failed_login_window_minutes' => env('FAILED_LOGIN_WINDOW_MINUTES', 15),
        'lockout_duration_minutes' => env('LOCKOUT_DURATION_MINUTES', 30),
        'suspicious_activity_threshold' => env('SUSPICIOUS_ACTIVITY_THRESHOLD', 10),
        'alert_recipients' => explode(',', env('SECURITY_ALERT_RECIPIENTS', '')),
    ],

    'transactions' => [
        'enabled' => env('TRANSACTION_LOGGING_ENABLED', true),
        'auto_commit' => env('TRANSACTION_AUTO_COMMIT', false),
        'retry_attempts' => env('TRANSACTION_RETRY_ATTEMPTS', 3),
        'timeout_seconds' => env('TRANSACTION_TIMEOUT_SECONDS', 30),
        'require_manual_review' => env('TRANSACTION_REQUIRE_MANUAL_REVIEW', false),
    ],

    'rate_limiting' => [
        'authentication' => [
            'requests_per_minute' => env('AUTH_RATE_LIMIT_PER_MINUTE', 5),
            'burst_size' => env('AUTH_RATE_LIMIT_BURST', 10),
        ],
        'api' => [
            'requests_per_minute' => env('API_RATE_LIMIT_PER_MINUTE', 60),
            'burst_size' => env('API_RATE_LIMIT_BURST', 100),
        ],
    ],

    'password_policy' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'max_age_days' => env('PASSWORD_MAX_AGE_DAYS', 90),
    ],

    'two_factor' => [
        'enabled' => env('TWO_FACTOR_ENABLED', true),
        'issuer' => env('TWO_FACTOR_ISSUER', env('APP_NAME')),
        'window' => env('TWO_FACTOR_WINDOW', 1),
        'digits' => env('TWO_FACTOR_DIGITS', 6),
    ],

    'backup' => [
        'enabled' => env('SECURITY_BACKUP_ENABLED', true),
        'frequency' => env('SECURITY_BACKUP_FREQUENCY', 'daily'),
        'retention_days' => env('SECURITY_BACKUP_RETENTION_DAYS', 30),
        'encrypt_backups' => env('ENCRYPT_SECURITY_BACKUPS', true),
    ],
];
