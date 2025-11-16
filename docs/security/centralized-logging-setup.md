# Centralized Logging Setup

## Overview

The centralized logging system aggregates logs from multiple sources into a unified `unified_logs` table, providing a single point of access for all system logs.

## Features

- **Unified Log Storage**: All logs stored in a single database table
- **Structured Logging**: JSON context support for detailed information
- **Multiple Log Sources**: Security, activity, application, database, email, system
- **Log Levels**: Debug, info, warning, error, critical
- **Query & Filtering**: Flexible querying by level, source, category, user, IP, date range
- **Statistics**: Aggregate statistics by level and source
- **Retention Policy**: Automatic cleanup of old logs

## Log Sources

1. **Security** - Security events (CSRF failures, unauthorized access, etc.)
2. **Activity** - Admin and user activities
3. **Application** - Application-level errors and events
4. **Database** - Database errors and queries
5. **Email** - Email sending events and failures
6. **System** - System-level events

## Integration

### Automatic Integration

The centralized logger automatically receives logs from:
- `SecurityEventLogger` - All security events are forwarded
- `ActivityLogger` - All admin activities are forwarded

### Manual Logging

You can manually log to the centralized logger:

```php
require_once __DIR__ . '/includes/centralized_logger.php';
require_once __DIR__ . '/includes/db_connect.php';

CentralizedLogger::init($conn);

// Log security event
CentralizedLogger::logSecurity('error', 'Unauthorized access attempt', [
    'category' => 'unauthorized_access',
    'user_id' => '123',
    'ip_address' => '192.168.1.100',
    'endpoint' => '/admin/users.php'
]);

// Log application error
CentralizedLogger::logApplication('error', 'Failed to process booking', [
    'category' => 'booking_error',
    'user_id' => '456',
    'details' => ['booking_id' => 789, 'error' => 'Database timeout']
]);

// Log database error
CentralizedLogger::logDatabase('error', 'Query failed', [
    'category' => 'query_error',
    'query' => 'SELECT * FROM users',
    'error' => 'Connection timeout'
]);

// Log email event
CentralizedLogger::logEmail('info', 'Email sent successfully', [
    'category' => 'email_sent',
    'recipient' => 'user@example.com',
    'subject' => 'Welcome Email'
]);

// Log system event
CentralizedLogger::logSystem('info', 'Scheduled task completed', [
    'category' => 'cron_job',
    'task' => 'cleanup_old_logs'
]);
```

## Querying Logs

### Get Recent Logs

```php
// Get last 100 logs
$logs = CentralizedLogger::getLogs(['limit' => 100]);

// Get error logs only
$errorLogs = CentralizedLogger::getLogs([
    'level' => 'error',
    'limit' => 50
]);

// Get security logs from last 24 hours
$securityLogs = CentralizedLogger::getLogs([
    'source' => 'security',
    'date_from' => date('Y-m-d H:i:s', strtotime('-24 hours')),
    'limit' => 100
]);

// Get logs for specific user
$userLogs = CentralizedLogger::getLogs([
    'user_id' => '123',
    'limit' => 50
]);

// Get logs by IP address
$ipLogs = CentralizedLogger::getLogs([
    'ip_address' => '192.168.1.100',
    'limit' => 50
]);

// Get logs by category
$categoryLogs = CentralizedLogger::getLogs([
    'category' => 'csrf_failure',
    'limit' => 50
]);
```

### Get Statistics

```php
// Get statistics for last 7 days
$stats = CentralizedLogger::getStatistics([
    'date_from' => date('Y-m-d H:i:s', strtotime('-7 days'))
]);

// Example output:
// [
//     'error_security' => 15,
//     'warning_security' => 8,
//     'info_activity' => 234,
//     'error_application' => 3
// ]
```

## Log Retention

### Automatic Cleanup

Clean up logs older than specified days:

```php
// Keep logs for 90 days (default)
$result = CentralizedLogger::cleanupOldLogs(90);

// Keep logs for 30 days
$result = CentralizedLogger::cleanupOldLogs(30);

// Result: ['success' => true, 'deleted' => 1234]
```

### Recommended Retention Policy

- **Security logs**: 90 days (for compliance and investigation)
- **Activity logs**: 90 days (for audit trail)
- **Application errors**: 30 days (for debugging)
- **System logs**: 30 days (for monitoring)

## Database Schema

The `unified_logs` table structure:

```sql
CREATE TABLE unified_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_level ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL DEFAULT 'info',
    log_source ENUM('security', 'activity', 'application', 'database', 'email', 'system') NOT NULL,
    category VARCHAR(100) NULL,
    message TEXT NOT NULL,
    user_id VARCHAR(50) NULL,
    username VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    endpoint VARCHAR(255) NULL,
    context JSON NULL,
    stack_trace TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_level (log_level),
    INDEX idx_log_source (log_source),
    INDEX idx_category (category),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    INDEX idx_log_source_level (log_source, log_level),
    INDEX idx_created_at_source (created_at, log_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Best Practices

1. **Use Appropriate Log Levels**:
   - `debug`: Detailed information for debugging
   - `info`: General informational messages
   - `warning`: Warning messages (potential issues)
   - `error`: Error messages (operation failed)
   - `critical`: Critical errors (system failure)

2. **Include Context**: Always include relevant context in the `$context` array:
   - User information (user_id, username)
   - Request information (ip_address, endpoint)
   - Error details (error message, stack trace)
   - Business context (booking_id, transaction_id, etc.)

3. **Use Categories**: Use consistent category names for easier filtering:
   - Security: `csrf_failure`, `unauthorized_access`, `rate_limit_exceeded`
   - Activity: `user_login`, `admin_action`, `profile_update`
   - Application: `booking_error`, `payment_failed`, `email_failed`

4. **Regular Cleanup**: Set up a cron job to clean old logs:
   ```bash
   # Run daily at 2 AM
   0 2 * * * php /path/to/cleanup_logs.php
   ```

5. **Monitor Log Volume**: Monitor the size of the `unified_logs` table and adjust retention policy as needed.

## Performance Considerations

- **Indexes**: The table includes indexes on commonly queried fields (level, source, user_id, ip_address, created_at)
- **Partitioning**: For high-volume systems, consider partitioning by date
- **Archiving**: For long-term retention, consider archiving old logs to separate tables or files

## Troubleshooting

### Logs Not Appearing

1. Check database connection is initialized
2. Verify `unified_logs` table exists
3. Check PHP error logs for errors
4. Verify the logger is being called

### High Log Volume

1. Review log levels (reduce debug/info logs in production)
2. Implement log sampling for high-frequency events
3. Adjust retention policy
4. Consider archiving old logs

---

*Last updated: [Current Date]*

