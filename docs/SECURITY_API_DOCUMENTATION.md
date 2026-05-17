# Security & Data Integrity API Documentation

## Overview

This document provides comprehensive API documentation for the Security & Data Integrity implementation in Scholaria.

## Table of Contents

1. [Transaction Service](#transaction-service)
2. [Data Integrity Service](#data-integrity-service)
3. [Transaction Recovery Service](#transaction-recovery-service)
4. [Performance Monitor Service](#performance-monitor-service)
5. [Security Audit Model](#security-audit-model)
6. [Activity Log Model](#activity-log-model)
7. [Transaction Model](#transaction-model)

---

## Transaction Service

### Overview
The TransactionService provides ACID-compliant transaction management with automatic rollback capabilities.

### Namespace
`App\Services\TransactionService`

### Methods

#### execute()
Execute a transaction with ACID compliance and automatic rollback.

```php
$service = new TransactionService();
$transaction = $service->execute([
    'type' => 'create', // create, update, delete, rollback, commit
    'table_name' => 'users',
    'record_id' => 1,
    'user_id' => auth()->id(),
    'data_before' => $oldData, // Optional
    'data_after' => $newData, // Optional
    'changed_fields' => ['name', 'email'], // Optional
    'reason' => 'User profile update', // Optional
], function ($transaction) {
    // Your database operation here
    // Return true to commit, false to rollback
    return true;
});
```

**Parameters:**
- `$transactionData` (array): Transaction metadata
  - `type` (string): Transaction type (create, update, delete, rollback, commit)
  - `table_name` (string): The table being modified
  - `record_id` (int): The record being modified
  - `user_id` (int): ID of user initiating the transaction
  - `data_before` (array|null): Before state (optional)
  - `data_after` (array|null): After state (optional)
  - `changed_fields` (array|null): List of modified fields (optional)
  - `reason` (string|null): Reason for transaction (optional)
- `$operation` (Closure): The database operation to execute

**Returns:** `Transaction` model instance

**Throws:** `Exception` if operation fails

---

#### executeNested()
Execute a nested transaction (savepoint).

```php
$service = new TransactionService();
$parentTransaction = Transaction::begin([...]);

$childTransaction = $service->executeNested([
    'type' => 'update',
    'table_name' => 'enrollments',
    'record_id' => 1,
    'user_id' => auth()->id(),
], function ($transaction) {
    // Nested operation
    return true;
}, $parentTransaction);
```

**Parameters:**
- `$transactionData` (array): Transaction metadata
- `$operation` (Closure): The database operation to execute
- `$parentTransaction` (Transaction|null): Parent transaction for nesting

**Returns:** `Transaction` model instance

---

#### retry()
Retry a failed transaction.

```php
$service = new TransactionService();
$failedTransaction = Transaction::where('status', 'failed')->first();

if ($failedTransaction->canRetry()) {
    $recoveredTransaction = $service->retry($failedTransaction, function ($transaction) {
        // Retry logic
        return true;
    });
}
```

**Parameters:**
- `$transaction` (Transaction): The failed transaction to retry
- `$operation` (Closure): The operation to retry

**Returns:** `Transaction` model instance

**Throws:** `Exception` if transaction cannot be retried

---

#### rollbackCommitted()
Rollback a committed transaction (compensating transaction).

```php
$service = new TransactionService();
$committedTransaction = Transaction::where('status', 'committed')->first();

$rollbackTransaction = $service->rollbackCommitted($committedTransaction, function ($transaction) {
    // Compensating operation to undo changes
    DB::table($transaction->table_name)
        ->where('id', $transaction->record_id)
        ->update($transaction->data_before);
});
```

**Parameters:**
- `$transaction` (Transaction): The transaction to rollback
- `$compensatingOperation` (Closure|null): Optional compensating operation

**Returns:** `Transaction` model instance for the rollback

---

#### getStatistics()
Get transaction statistics.

```php
$service = new TransactionService();
$stats = $service->getStatistics();
```

**Returns:** Array with keys:
- `total` (int): Total transaction count
- `pending` (int): Pending transaction count
- `committed` (int): Committed transaction count
- `rolled_back` (int): Rolled back transaction count
- `failed` (int): Failed transaction count
- `requires_review` (int): Transactions requiring manual review
- `avg_duration` (float|null): Average transaction duration in seconds

---

#### cleanupOldTransactions()
Clean up old completed transactions.

```php
$service = new TransactionService();
$deleted = $service->cleanupOldTransactions(90); // Keep last 90 days
```

**Parameters:**
- `$daysToKeep` (int): Number of days to keep transactions (default: 90)

**Returns:** Number of deleted transactions

---

## Data Integrity Service

### Overview
The DataIntegrityService provides automated checks for referential integrity and constraint violations.

### Namespace
`App\Services\DataIntegrityService`

### Methods

#### runAllChecks()
Run all data integrity checks.

```php
$service = new DataIntegrityService();
$results = $service->runAllChecks();
```

**Returns:** Array with keys:
- `timestamp` (string): Check timestamp
- `checks` (array): Results of individual checks
- `total_violations` (int): Total number of violations
- `critical_violations` (int): Number of critical violations

**Individual Checks:**
- `referential_integrity`: Checks foreign key relationships
- `orphaned_records`: Checks for records with deleted parents
- `duplicate_records`: Checks for duplicate entries
- `foreign_key_constraints`: Validates FK constraints
- `unique_constraints`: Validates unique constraints
- `data_consistency`: Checks data consistency across tables

---

#### checkReferentialIntegrity()
Check referential integrity across all tables.

```php
$service = new DataIntegrityService();
$result = $service->checkReferentialIntegrity();
```

**Returns:** Array with keys:
- `status` (string): 'passed' or 'failed'
- `severity` (string): 'info', 'medium', or 'critical'
- `violations_count` (int): Number of violations
- `violations` (array): List of violation descriptions

---

#### fixOrphanedRecords()
Fix orphaned records (with confirmation).

```php
$service = new DataIntegrityService();
$deleted = $service->fixOrphanedRecords('enrollments', [1, 2, 3]);
```

**Parameters:**
- `$table` (string): The table to fix
- `$orphanedIds` (array): The IDs of orphaned records

**Returns:** Number of records deleted

**Throws:** `Exception` if fix fails

---

#### getSummary()
Get data integrity report summary.

```php
$service = new DataIntegrityService();
$summary = $service->getSummary();
```

**Returns:** Array with keys:
- `timestamp` (string): Report timestamp
- `total_violations` (int): Total violations
- `critical_violations` (int): Critical violations
- `status` (string): 'healthy', 'warning', or 'critical'
- `checks_passed` (int): Number of passed checks
- `checks_failed` (int): Number of failed checks

---

## Transaction Recovery Service

### Overview
The TransactionRecoveryService provides capabilities for recovering failed transactions.

### Namespace
`App\Services\TransactionRecoveryService`

### Methods

#### getRecoverableTransactions()
Get all recoverable failed transactions.

```php
$service = new TransactionRecoveryService();
$transactions = $service->getRecoverableTransactions();
```

**Returns:** Collection of Transaction models

---

#### recover()
Attempt to recover a failed transaction.

```php
$service = new TransactionRecoveryService();
$failedTransaction = Transaction::where('status', 'failed')->first();

$recoveredTransaction = $service->recover($failedTransaction, function ($transaction) {
    // Custom recovery logic
    DB::table($transaction->table_name)
        ->where('id', $transaction->record_id)
        ->update($transaction->data_after);
    return true;
});
```

**Parameters:**
- `$transaction` (Transaction): The failed transaction to recover
- `$recoveryOperation` (Closure|null): Optional custom recovery operation

**Returns:** `Transaction` model instance

**Throws:** `Exception` if recovery fails

---

#### recoverAll()
Recover all recoverable transactions.

```php
$service = new TransactionRecoveryService();
$results = $service->recoverAll();
```

**Returns:** Array with keys:
- `total` (int): Total recoverable transactions
- `recovered` (int): Successfully recovered count
- `failed` (int): Failed recovery count
- `skipped` (int): Skipped count
- `details` (array): Detailed results for each transaction

---

#### cleanupStaleTransactions()
Clean up stale pending transactions.

```php
$service = new TransactionRecoveryService();
$count = $service->cleanupStaleTransactions();
```

**Returns:** Number of transactions cleaned up

---

#### rollbackToBeforeState()
Rollback a transaction to its before state.

```php
$service = new TransactionRecoveryService();
$transaction = Transaction::where('status', 'committed')->first();

$rollbackTransaction = $service->rollbackToBeforeState($transaction);
```

**Parameters:**
- `$transaction` (Transaction): The transaction to rollback

**Returns:** The rollback Transaction model instance

**Throws:** `Exception` if rollback fails

---

#### getRecoveryReport()
Get transaction recovery report.

```php
$service = new TransactionRecoveryService();
$report = $service->getRecoveryReport();
```

**Returns:** Array with keys:
- `timestamp` (string): Report timestamp
- `statistics` (array): Recovery statistics
- `recoverable_transactions` (array): List of recoverable transactions
- `stale_transactions` (array): List of stale transactions

---

## Performance Monitor Service

### Overview
The PerformanceMonitorService provides performance monitoring and optimization for security operations.

### Namespace
`App\Services\PerformanceMonitorService`

### Methods

#### measure()
Measure execution time of a closure.

```php
$service = new PerformanceMonitorService();
$result = $service->measure('operation_name', function () {
    // Your operation here
    return $result;
});
```

**Parameters:**
- `$operationName` (string): Name of the operation
- `$operation` (Closure): The operation to measure

**Returns:** Result of the operation

---

#### benchmarkSecurityOperations()
Benchmark security operations.

```php
$service = new PerformanceMonitorService();
$results = $service->benchmarkSecurityOperations();
```

**Returns:** Array with benchmark results for:
- session_validation
- activity_logging
- transaction_creation
- encryption
- decryption

---

#### checkPerformanceImpact()
Check if security features impact performance within acceptable limits.

```php
$service = new PerformanceMonitorService();
$impact = $service->checkPerformanceImpact();
```

**Returns:** Array with keys:
- `overall_status` (string): 'passed' or 'failed'
- `performance_threshold` (string): '95%'
- `all_operations_passed` (bool): Whether all operations passed
- `issues` (array): List of performance issues
- `comparison` (array): Detailed comparison with baseline

---

#### getPerformanceReport()
Get performance report.

```php
$service = new PerformanceMonitorService();
$report = $service->getPerformanceReport();
```

**Returns:** Array with keys:
- `timestamp` (string): Report timestamp
- `daily_summary` (array): Daily performance summary
- `baseline_comparison` (array): Comparison with baseline
- `performance_impact` (array): Performance impact assessment
- `recommendations` (array): Optimization recommendations

---

## Security Audit Model

### Overview
The SecurityAudit model tracks security events and breaches.

### Namespace
`App\Models\SecurityAudit`

### Static Methods

#### logFailedLogin()
Log a failed login attempt.

```php
$audit = SecurityAudit::logFailedLogin([
    'username' => 'user@example.com',
    'reason' => 'Invalid credentials',
    'ip_address' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0...',
]);
```

---

#### logSuspiciousActivity()
Log suspicious activity.

```php
$audit = SecurityAudit::logSuspiciousActivity([
    'description' => 'Multiple failed login attempts',
    'severity' => 'high',
    'event_data' => ['attempts' => 10],
]);
```

---

#### logSecurityBreach()
Log a security breach.

```php
$audit = SecurityAudit::logSecurityBreach([
    'description' => 'Unauthorized access attempt',
    'event_data' => ['endpoint' => '/api/admin'],
]);
```

---

### Instance Methods

#### resolve()
Mark the event as resolved.

```php
$audit->resolve($adminUserId, 'Issue investigated and resolved');
```

---

#### requireAction()
Mark the event as requiring action.

```php
$audit->requireAction();
```

---

## Activity Log Model

### Overview
The ActivityLog model tracks user activities with encryption support.

### Namespace
`App\Models\ActivityLog`

### Static Methods

#### log()
Log an activity.

```php
$log = ActivityLog::log([
    'user_id' => auth()->id(),
    'action' => 'login',
    'resource_type' => 'auth',
    'request_data' => ['email' => 'user@example.com'],
]);
```

---

### Instance Methods

Sensitive data is automatically encrypted if the action involves sensitive fields.

---

## Transaction Model

### Overview
The Transaction model tracks all database operations with before/after states.

### Namespace
`App\Models\Transaction`

### Static Methods

#### begin()
Create a new transaction.

```php
$transaction = Transaction::begin([
    'type' => 'create',
    'table_name' => 'users',
    'record_id' => 1,
    'user_id' => auth()->id(),
    'data_after' => ['name' => 'John Doe'],
]);
```

---

#### execute()
Execute a transaction with rollback capability.

```php
$transaction = Transaction::execute([
    'type' => 'update',
    'table_name' => 'users',
    'record_id' => 1,
    'user_id' => auth()->id(),
    'data_before' => ['name' => 'Old'],
    'data_after' => ['name' => 'New'],
], function ($transaction) {
    // Operation
    return true;
});
```

---

#### getChangedFields()
Get changed fields between before and after data.

```php
$changed = Transaction::getChangedFields($beforeData, $afterData);
```

---

### Instance Methods

#### commit()
Mark transaction as committed.

```php
$transaction->commit();
```

---

#### rollback()
Mark transaction as rolled back.

```php
$transaction->rollback();
```

---

#### fail()
Mark transaction as failed.

```php
$transaction->fail('Error message');
```

---

#### canRetry()
Check if the transaction can be retried.

```php
if ($transaction->canRetry()) {
    // Retry logic
}
```

---

#### isStale()
Check if transaction is stale (timeout).

```php
if ($transaction->isStale()) {
    // Handle stale transaction
}
```

---

## Configuration

### Security Configuration

All security features can be configured in `config/security.php`:

```php
return [
    'transactions' => [
        'enabled' => env('TRANSACTION_LOGGING_ENABLED', true),
        'auto_commit' => env('TRANSACTION_AUTO_COMMIT', false),
        'retry_attempts' => env('TRANSACTION_RETRY_ATTEMPTS', 3),
        'timeout_seconds' => env('TRANSACTION_TIMEOUT_SECONDS', 30),
    ],
    
    'audit' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
        'encrypt_sensitive_data' => env('ENCRYPT_SENSITIVE_AUDIT_DATA', true),
        'sensitive_fields' => ['password', 'email', 'phone', 'ssn'],
    ],
    
    'intrusion_detection' => [
        'enabled' => env('INTRUSION_DETECTION_ENABLED', true),
        'failed_login_threshold' => env('FAILED_LOGIN_THRESHOLD', 5),
        'failed_login_window_minutes' => env('FAILED_LOGIN_WINDOW_MINUTES', 15),
    ],
];
```

---

## Best Practices

1. **Always use TransactionService** for critical database operations
2. **Enable transaction logging** on all important models using the `LogsTransactions` trait
3. **Run data integrity checks** regularly (daily or weekly)
4. **Monitor performance** to ensure security features don't degrade system performance
5. **Review failed transactions** periodically and recover if needed
6. **Keep audit logs** encrypted and secure
7. **Set appropriate retention periods** for logs based on compliance requirements
8. **Use nested transactions** for complex operations that need partial rollback capability

---

## Error Handling

All services throw exceptions on failure. Always wrap calls in try-catch blocks:

```php
try {
    $service = new TransactionService();
    $transaction = $service->execute($data, $operation);
} catch (Exception $e) {
    Log::error('Transaction failed', ['error' => $e->getMessage()]);
    // Handle error
}
```

---

## Testing

Run security tests:

```bash
php artisan test --filter=SecurityTest
```

Run data integrity checks:

```bash
php artisan tinker
>>> $service = new App\Services\DataIntegrityService();
>>> $service->runAllChecks();
```

Monitor performance:

```bash
php artisan tinker
>>> $service = new App\Services\PerformanceMonitorService();
>>> $service->getPerformanceReport();
```
