# Admin Training Guide - Security & Data Integrity

## Overview

This training guide provides comprehensive instructions for administrators on using the Security & Data Integrity features in Scholaria.

## Table of Contents

1. [Introduction](#introduction)
2. [Session Management](#session-management)
3. [Activity Monitoring](#activity-monitoring)
4. [Security Auditing](#security-auditing)
5. [Transaction Management](#transaction-management)
6. [Data Integrity](#data-integrity)
7. [Transaction Recovery](#transaction-recovery)
8. [Performance Monitoring](#performance-monitoring)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## Introduction

### What is Security & Data Integrity?

The Security & Data Integrity system provides:
- Real-time session tracking for all users
- Comprehensive activity logging with encryption
- Automated security breach detection and alerts
- ACID-compliant transaction management
- Data integrity validation
- Transaction recovery capabilities
- Performance monitoring for security features

### Why is it Important?

- **Compliance**: Meets GDPR and other regulatory requirements
- **Security**: Detects and prevents unauthorized access
- **Data Integrity**: Ensures data consistency and accuracy
- **Accountability**: Complete audit trail of all operations
- **Recovery**: Ability to recover from errors and failures

---

## Session Management

### Viewing Active Sessions

1. Navigate to **Admin Dashboard** → **Security** → **Sessions**
2. View all active user sessions with:
   - User information
   - IP address
   - User agent
   - Session start time
   - Expiration time
   - Last activity

### Terminating Sessions

1. In the Sessions view, locate the session you want to terminate
2. Click the **Terminate** button next to the session
3. Confirm the termination action
4. The session will be immediately invalidated

**Note**: Terminating a session will force the user to log in again.

### Viewing Session History

1. Navigate to **Admin Dashboard** → **Security** → **Sessions**
2. Click on a user's name to view their session history
3. View all past sessions including:
   - Login/logout times
   - IP addresses used
   - Devices used
   - Session duration

### Session Security Settings

Configure session security in `config/security.php`:

```php
'session' => [
    'timeout' => 120, // Session timeout in minutes
    'max_concurrent_sessions' => 3, // Max sessions per user
    'require_ip_consistency' => true, // Require same IP
    'require_user_agent_consistency' => true, // Require same device
],
```

**Best Practices**:
- Set appropriate session timeout (recommended: 60-120 minutes)
- Limit concurrent sessions for sensitive operations
- Enable IP and user agent consistency for high-security environments

---

## Activity Monitoring

### Viewing Activity Logs

1. Navigate to **Admin Dashboard** → **Security** → **Activity Logs**
2. Filter logs by:
   - Date range
   - User
   - Action type
   - Resource type
   - IP address

### Understanding Log Entries

Each activity log entry contains:
- **User**: Who performed the action
- **Action**: What was done (create, update, delete, login, etc.)
- **Resource**: What was affected
- **IP Address**: Where the action originated
- **Timestamp**: When the action occurred
- **Request Data**: Details of the action (encrypted if sensitive)

### Searching Activity Logs

1. Use the search bar to find specific activities
2. Search by:
   - User name or email
   - Action type
   - Resource type
   - IP address
   - Keywords in request data

### Exporting Activity Logs

1. Navigate to **Admin Dashboard** → **Security** → **Activity Logs**
2. Apply desired filters
3. Click **Export** button
4. Choose export format (CSV, JSON, PDF)
5. Download the exported file

**Note**: Sensitive data remains encrypted in exports.

### Activity Log Retention

Configure retention in `config/security.php`:

```php
'audit' => [
    'retention_days' => 90, // Keep logs for 90 days
],
```

**Best Practices**:
- Set retention based on compliance requirements
- Regularly review activity logs for suspicious patterns
- Export important logs before they expire
- Monitor failed authentication attempts

---

## Security Auditing

### Viewing Security Events

1. Navigate to **Admin Dashboard** → **Security** → **Security Audits**
2. View all security events with:
   - Event type (failed_login, suspicious_activity, security_breach)
   - Severity (low, medium, high, critical)
   - Description
   - IP address
   - Timestamp
   - Resolution status

### Event Types

- **Failed Login**: Unsuccessful authentication attempts
- **Suspicious Activity**: Anomalous behavior patterns
- **Security Breach**: Critical security incidents

### Severity Levels

- **Low**: Informational events
- **Medium**: Requires attention
- **High**: Requires immediate action
- **Critical**: Emergency - requires immediate response

### Resolving Security Events

1. Click on an event to view details
2. Review the event information and context
3. Click **Resolve** button
4. Enter resolution notes
5. Submit the resolution

**Note**: Critical events require immediate attention and should be escalated.

### Configuring Alerts

Configure alert recipients in `.env`:

```env
SECURITY_ALERT_RECIPIENTS=admin@example.com,security@example.com
```

**Best Practices**:
- Review security events daily
- Respond to high/critical events immediately
- Document all resolution actions
- Escalate unresolved critical events
- Monitor for patterns in failed login attempts

---

## Transaction Management

### Viewing Transactions

1. Navigate to **Admin Dashboard** → **Security** → **Transactions**
2. View all transactions with:
   - Transaction ID
   - Type (create, update, delete, rollback)
   - Status (pending, committed, rolled_back, failed)
   - Table affected
   - Record ID
   - User who initiated
   - Timestamp

### Transaction Statuses

- **Pending**: Transaction in progress
- **Committed**: Transaction completed successfully
- **Rolled Back**: Transaction was rolled back
- **Failed**: Transaction failed with error

### Viewing Transaction Details

1. Click on a transaction to view details
2. See:
   - Before state (data before change)
   - After state (data after change)
   - Changed fields
   - Reason for transaction
   - Error message (if failed)
   - Retry count
   - Transaction duration

### Filtering Transactions

Filter by:
- Status
- Type
- Table name
- User
- Date range
- Requires manual review

### Manual Transaction Review

1. Navigate to transactions requiring review
2. Review the transaction details
3. Approve or reject the transaction
4. Add notes for audit trail

**Best Practices**:
- Review failed transactions regularly
- Monitor long-running pending transactions
- Keep transaction logs for compliance
- Use transactions for all critical operations

---

## Data Integrity

### Running Integrity Checks

1. Navigate to **Admin Dashboard** → **Security** → **Data Integrity**
2. Click **Run Integrity Checks** button
3. Wait for checks to complete
4. Review results

### Understanding Integrity Check Results

Checks performed:
- **Referential Integrity**: Validates foreign key relationships
- **Orphaned Records**: Detects records with deleted parents
- **Duplicate Records**: Identifies duplicate entries
- **Foreign Key Constraints**: Validates FK constraint compliance
- **Unique Constraints**: Validates unique constraint compliance
- **Data Consistency**: Checks data consistency across tables

### Handling Integrity Violations

1. Review each violation
2. Determine the cause
3. Fix the issue:
   - Delete orphaned records
   - Remove duplicates
   - Fix broken relationships
4. Re-run integrity checks to verify fixes

### Scheduling Regular Checks

Use Laravel scheduler to run checks automatically:

```php
// app/Console/Kernel.php
$schedule->call(function () {
    $service = new \App\Services\DataIntegrityService();
    $results = $service->runAllChecks();
    // Send email report if violations found
})->daily();
```

**Best Practices**:
- Run integrity checks weekly
- Address violations immediately
- Document all fixes
- Monitor for recurring violations

---

## Transaction Recovery

### Viewing Failed Transactions

1. Navigate to **Admin Dashboard** → **Security** → **Transaction Recovery**
2. View all failed transactions that can be recovered
3. See:
   - Transaction details
   - Error message
   - Retry count
   - Recovery status

### Recovering a Single Transaction

1. Click on a failed transaction
2. Review the transaction details and error
3. Click **Recover** button
4. Confirm the recovery action
5. Monitor the recovery result

### Batch Recovery

1. Select multiple failed transactions
2. Click **Batch Recover** button
3. Confirm the batch recovery
4. View recovery results

### Manual Recovery

For complex recoveries:
1. Export the transaction data
2. Manually apply the fix
3. Mark transaction as resolved
4. Document the manual recovery process

### Rolling Back Transactions

1. Navigate to a committed transaction
2. Click **Rollback** button
3. Review the rollback preview
4. Confirm the rollback
5. The transaction will be reverted to its before state

**Best Practices**:
- Review failed transactions daily
- Test recovery in staging environment first
- Document all recovery actions
- Monitor for recurring failures

---

## Performance Monitoring

### Viewing Performance Metrics

1. Navigate to **Admin Dashboard** → **Security** → **Performance**
2. View performance metrics:
   - Daily operation counts
   - Average response times
   - Slow operations
   - Success rates

### Performance Benchmarks

View baseline comparisons:
- Session validation time
- Activity logging time
- Transaction creation time
- Encryption/decryption time

### Performance Reports

Generate performance reports:
1. Select date range
2. Click **Generate Report**
3. Review the report
4. Export if needed

### Handling Performance Issues

If performance degrades:
1. Identify the slow operation
2. Review the operation logic
3. Consider:
   - Adding database indexes
   - Implementing caching
   - Optimizing queries
   - Increasing resources

### Performance Thresholds

Configure thresholds in `config/security.php`:

```php
'transactions' => [
    'timeout_seconds' => 30, // Transaction timeout
],
```

**Best Practices**:
- Monitor performance weekly
- Address slow operations immediately
- Maintain performance >95% of baseline
- Document performance optimizations

---

## Troubleshooting

### Common Issues

#### Sessions Not Tracking

**Symptoms**: User sessions not appearing in session list

**Solutions**:
1. Verify session tracking is enabled in config
2. Check database connection
3. Review logs for errors
4. Ensure UserSession model is properly configured

#### Activity Logs Not Recording

**Symptoms**: Activities not appearing in activity logs

**Solutions**:
1. Verify audit logging is enabled
2. Check LogsTransactions trait is applied to models
3. Review encryption configuration
4. Check database permissions

#### Transactions Failing

**Symptoms**: High number of failed transactions

**Solutions**:
1. Review error messages in failed transactions
2. Check database constraints
3. Verify data integrity
4. Review timeout settings

#### Performance Degradation

**Symptoms**: Slow response times, timeouts

**Solutions**:
1. Run performance report
2. Identify slow operations
3. Add database indexes
4. Implement caching
5. Review server resources

#### Integrity Check Failures

**Symptoms**: Repeated integrity violations

**Solutions**:
1. Review violation details
2. Fix root cause
3. Update application logic
4. Add validation at application level

### Getting Help

If issues persist:
1. Check application logs: `storage/logs/laravel.log`
2. Review security documentation
3. Contact technical support
4. Escalate critical issues immediately

---

## Best Practices

### Daily Tasks

- Review security events
- Check failed transactions
- Monitor performance metrics
- Review activity logs for anomalies

### Weekly Tasks

- Run data integrity checks
- Review session activity
- Clean up old logs
- Generate performance reports

### Monthly Tasks

- Review security trends
- Audit transaction logs
- Update security configurations
- Review and update documentation

### Security Best Practices

1. **Never share admin credentials**
2. **Use strong, unique passwords**
3. **Enable two-factor authentication**
4. **Review access logs regularly**
5. **Keep software updated**
6. **Follow the principle of least privilege**
7. **Report security incidents immediately**
8. **Participate in security training**

### Data Protection Best Practices

1. **Encrypt sensitive data at rest**
2. **Use secure connections (HTTPS)**
3. **Implement proper backup procedures**
4. **Follow data retention policies**
5. **Monitor for data leaks**
6. **Validate all input data**
7. **Use parameterized queries**

---

## Quick Reference

### Important Commands

```bash
# Run security tests
php artisan test --filter=SecurityTest

# Run data integrity checks
php artisan tinker
>>> $service = new App\Services\DataIntegrityService();
>>> $service->runAllChecks();

# View performance report
php artisan tinker
>>> $service = new App\Services\PerformanceMonitorService();
>>> $service->getPerformanceReport();

# Clear performance metrics
php artisan tinker
>>> $service = new App\Services\PerformanceMonitorService();
>>> $service->clearOldMetrics(30);
```

### Important Configuration Files

- `config/security.php` - Security configuration
- `.env` - Environment variables
- `app/Services/TransactionService.php` - Transaction management
- `app/Services/DataIntegrityService.php` - Data integrity checks
- `app/Services/TransactionRecoveryService.php` - Transaction recovery

### Important Models

- `App\Models\UserSession` - Session management
- `App\Models\ActivityLog` - Activity logging
- `App\Models\SecurityAudit` - Security events
- `App\Models\Transaction` - Transaction tracking

---

## Conclusion

This training guide provides the essential knowledge for administrators to effectively use the Security & Data Integrity features in Scholaria. Regular practice and adherence to best practices will ensure a secure and reliable system.

For additional support or questions, refer to the API documentation or contact the technical team.
