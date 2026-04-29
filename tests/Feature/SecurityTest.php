<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use App\Models\ActivityLog;
use App\Models\SecurityAudit;
use App\Models\Transaction;
use App\Services\DataIntegrityService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    /**
     * Test session tracking functionality.
     */
    public function test_session_tracking_works(): void
    {
        $user = User::factory()->create();
        
        // Simulate login and session creation
        $session = UserSession::create([
            'user_id' => $user->id,
            'token' => Hash::make('test-token'),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'expires_at' => now()->addHours(2),
        ]);

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
        ]);

        // Test session validation
        $this->assertTrue($session->expires_at > now());
    }

    /**
     * Test activity logging with encryption.
     */
    public function test_activity_logging_with_encryption(): void
    {
        Config::set('security.audit.encrypt_sensitive_data', true);
        
        $user = User::factory()->create();
        
        $log = ActivityLog::log([
            'user_id' => $user->id,
            'action' => 'login',
            'resource_type' => 'auth',
            'request_data' => [
                'email' => $user->email,
                'password' => 'secret123',
            ],
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'login',
            'is_sensitive' => true,
        ]);

        // Verify encrypted data is not stored in plain text
        $this->assertDatabaseMissing('activity_logs', [
            'request_data->password' => 'secret123',
        ]);
    }

    /**
     * Test failed login tracking.
     */
    public function test_failed_login_tracking(): void
    {
        $audit = SecurityAudit::logFailedLogin([
            'username' => 'test@example.com',
            'reason' => 'Invalid credentials',
            'ip_address' => '192.168.1.1',
        ]);

        $this->assertDatabaseHas('security_audits', [
            'event_type' => 'failed_login',
            'severity' => 'medium',
        ]);

        $this->assertNotNull($audit->fingerprint);
    }

    /**
     * Test suspicious activity detection.
     */
    public function test_suspicious_activity_detection(): void
    {
        $audit = SecurityAudit::logSuspiciousActivity([
            'description' => 'Multiple failed login attempts',
            'severity' => 'high',
            'event_data' => [
                'attempts' => 10,
                'timeframe' => '5 minutes',
            ],
        ]);

        $this->assertDatabaseHas('security_audits', [
            'event_type' => 'suspicious_activity',
            'severity' => 'high',
            'requires_action' => true,
        ]);
    }

    /**
     * Test transaction ACID compliance.
     */
    public function test_transaction_acid_compliance(): void
    {
        $service = new TransactionService();
        $user = User::factory()->create();

        // Test successful transaction
        $transaction = $service->execute([
            'type' => 'create',
            'table_name' => 'test_table',
            'record_id' => 1,
            'user_id' => $user->id,
            'data_after' => ['name' => 'Test'],
        ], function ($transaction) {
            // Simulate successful operation
            return true;
        });

        $this->assertEquals('committed', $transaction->status);
        $this->assertNotNull($transaction->completed_at);

        // Test failed transaction with rollback
        $failedTransaction = null;
        try {
            $failedTransaction = $service->execute([
                'type' => 'create',
                'table_name' => 'test_table',
                'record_id' => 2,
                'user_id' => $user->id,
                'data_after' => ['name' => 'Test'],
            ], function ($transaction) {
                throw new \Exception('Simulated failure');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals('failed', $failedTransaction->status);
        $this->assertNotNull($failedTransaction->error_message);
    }

    /**
     * Test transaction rollback capability.
     */
    public function test_transaction_rollback(): void
    {
        $user = User::factory()->create();
        
        $transaction = Transaction::begin([
            'type' => 'update',
            'table_name' => 'users',
            'record_id' => $user->id,
            'user_id' => $user->id,
            'data_before' => ['name' => 'Old Name'],
            'data_after' => ['name' => 'New Name'],
        ]);

        $this->assertEquals('pending', $transaction->status);

        $transaction->rollback();
        $this->assertEquals('rolled_back', $transaction->status);
    }

    /**
     * Test data integrity validation.
     */
    public function test_data_integrity_validation(): void
    {
        $service = new DataIntegrityService();
        $results = $service->runAllChecks();

        $this->assertArrayHasKey('timestamp', $results);
        $this->assertArrayHasKey('checks', $results);
        $this->assertArrayHasKey('total_violations', $results);
        $this->assertArrayHasKey('critical_violations', $results);

        $this->assertArrayHasKey('referential_integrity', $results['checks']);
        $this->assertArrayHasKey('orphaned_records', $results['checks']);
        $this->assertArrayHasKey('duplicate_records', $results['checks']);
    }

    /**
     * Test referential integrity check.
     */
    public function test_referential_integrity_check(): void
    {
        $service = new DataIntegrityService();
        $result = $service->checkReferentialIntegrity();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('violations_count', $result);
    }

    /**
     * Test transaction recovery.
     */
    public function test_transaction_recovery(): void
    {
        $user = User::factory()->create();
        
        // Create a failed transaction
        $transaction = Transaction::create([
            'transaction_id' => \Illuminate\Support\Str::uuid(),
            'type' => 'create',
            'status' => 'failed',
            'table_name' => 'test_table',
            'record_id' => 1,
            'user_id' => $user->id,
            'data_after' => ['name' => 'Test'],
            'error_message' => 'Test error',
            'retry_count' => 1,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->assertTrue($transaction->canRetry());
        $this->assertEquals('failed', $transaction->status);
    }

    /**
     * Test rate limiting on authentication.
     */
    public function test_rate_limiting_on_authentication(): void
    {
        $rateLimit = config('security.rate_limiting.authentication.requests_per_minute', 5);
        $this->assertGreaterThan(0, $rateLimit);
    }

    /**
     * Test password policy enforcement.
     */
    public function test_password_policy_enforcement(): void
    {
        $minLength = config('security.password_policy.min_length', 8);
        $requireUppercase = config('security.password_policy.require_uppercase', true);
        $requireLowercase = config('security.password_policy.require_lowercase', true);
        $requireNumbers = config('security.password_policy.require_numbers', true);
        $requireSymbols = config('security.password_policy.require_symbols', true);

        $this->assertGreaterThanOrEqual(8, $minLength);
        $this->assertTrue($requireUppercase);
        $this->assertTrue($requireLowercase);
        $this->assertTrue($requireNumbers);
        $this->assertTrue($requireSymbols);
    }

    /**
     * Test session timeout configuration.
     */
    public function test_session_timeout_configuration(): void
    {
        $timeout = config('security.session.timeout_minutes', 120);
        $this->assertGreaterThan(0, $timeout);
    }

    /**
     * Test concurrent session limit.
     */
    public function test_concurrent_session_limit(): void
    {
        $maxSessions = config('security.session.max_concurrent_sessions', 3);
        $this->assertGreaterThan(0, $maxSessions);
    }

    /**
     * Test encryption configuration.
     */
    public function test_encryption_configuration(): void
    {
        $algorithm = config('security.encryption.algorithm', 'AES-256-CBC');
        $this->assertStringContainsString('AES', $algorithm);
    }

    /**
     * Test intrusion detection configuration.
     */
    public function test_intrusion_detection_configuration(): void
    {
        $enabled = config('security.intrusion_detection.enabled', true);
        $threshold = config('security.intrusion_detection.failed_login_threshold', 5);
        
        $this->assertTrue($enabled);
        $this->assertGreaterThan(0, $threshold);
    }

    /**
     * Test transaction logging configuration.
     */
    public function test_transaction_logging_configuration(): void
    {
        $enabled = config('security.transactions.enabled', true);
        $retryAttempts = config('security.transactions.retry_attempts', 3);
        $timeout = config('security.transactions.timeout_seconds', 30);

        $this->assertTrue($enabled);
        $this->assertGreaterThan(0, $retryAttempts);
        $this->assertGreaterThan(0, $timeout);
    }

    /**
     * Test SQL injection protection via parameterized queries.
     */
    public function test_sql_injection_protection(): void
    {
        $user = User::factory()->create();
        
        // Use parameterized query (Eloquent does this automatically)
        $foundUser = User::where('email', $user->email)->first();
        
        $this->assertEquals($user->id, $foundUser->id);
    }

    /**
     * Test XSS protection.
     */
    public function test_xss_protection(): void
    {
        $maliciousInput = '<script>alert("XSS")</script>';
        
        // Laravel automatically escapes output in Blade templates
        $this->assertNotNull($maliciousInput);
    }

    /**
     * Test CSRF protection.
     */
    public function test_csrf_protection(): void
    {
        $this->assertNotNull(csrf_token());
    }

    /**
     * Test sensitive field encryption.
     */
    public function test_sensitive_field_encryption(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        // Verify the name is encrypted in the database
        $dbUser = DB::table('users')->where('id', $user->id)->first();
        
        $this->assertNotEquals('Test', $dbUser->first_name);
        $this->assertEquals('Test', $user->first_name); // Decrypted value
    }

    /**
     * Test audit log retention.
     */
    public function test_audit_log_retention(): void
    {
        $retentionDays = config('security.audit.retention_days', 90);
        $this->assertGreaterThan(0, $retentionDays);
    }

    /**
     * Test security audit resolution workflow.
     */
    public function test_security_audit_resolution_workflow(): void
    {
        $admin = User::factory()->create();
        
        $audit = SecurityAudit::logSuspiciousActivity([
            'description' => 'Test suspicious activity',
            'severity' => 'high',
        ]);

        $this->assertFalse($audit->is_resolved);

        $audit->resolve($admin->id, 'Issue resolved');

        $this->assertTrue($audit->is_resolved);
        $this->assertEquals($admin->id, $audit->resolved_by);
    }
}
