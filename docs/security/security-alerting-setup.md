# Security Event Alerting Setup

## Overview

The security alerting system automatically sends email notifications to administrators when critical or high-severity security events occur.

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
# Security Alerting Configuration
SECURITY_ALERTING_ENABLED=true
ADMIN_EMAIL=admin@example.com,security@example.com
```

- **SECURITY_ALERTING_ENABLED**: Enable/disable alerting (default: `true`)
- **ADMIN_EMAIL**: Comma-separated list of admin email addresses to receive alerts

### Alert Thresholds

The system uses the following thresholds (configurable in `security_alerter.php`):

- **Critical**: Alert immediately (1 event)
- **High**: Alert after 3 events in 5 minutes
- **Medium**: Alert after 10 events in 5 minutes

### Cooldown Period

To prevent alert spam, there's a 10-minute cooldown period between alerts for the same event type and severity level.

## Alert Types

The following security events trigger alerts:

1. **CSRF Failures** (High severity)
   - Triggered when CSRF token validation fails

2. **Unauthorized Access** (High severity)
   - Triggered when unauthenticated users attempt to access protected resources
   - Triggered when users attempt to access resources without required permissions

3. **Rate Limit Exceeded** (Medium severity)
   - Triggered when API rate limits are exceeded

4. **Authentication Failures** (Medium severity)
   - Triggered on failed login attempts (if integrated)

5. **Suspicious Activity** (High severity)
   - Triggered for various suspicious behaviors

6. **File Upload Events** (Medium severity)
   - Triggered for suspicious file upload attempts

## Alert Email Format

Alerts include:
- Event type and severity
- Timestamp
- IP address
- User information (if available)
- Endpoint/URL
- Detailed context
- Action recommendations

## Manual Alert Testing

You can manually trigger an alert for testing:

```php
require_once __DIR__ . '/includes/security_alerter.php';
require_once __DIR__ . '/includes/db_connect.php';

SecurityAlerter::init($conn);
SecurityAlerter::triggerAlert('test_alert', 'high', [
    'ip_address' => '127.0.0.1',
    'endpoint' => '/test',
    'details' => ['test' => true]
]);
```

## Database Tables

The alerting system uses:
- `security_events` - Stores all security events (created by SecurityEventLogger)
- `security_alerts_sent` - Tracks sent alerts for cooldown management (auto-created)

## Monitoring

To view recent security events:

```php
require_once __DIR__ . '/includes/security_event_logger.php';
require_once __DIR__ . '/includes/db_connect.php';

SecurityEventLogger::init($conn);
$events = SecurityEventLogger::getRecentEvents(100, 'high');
```

## Best Practices

1. **Configure Multiple Admin Emails**: Use multiple email addresses to ensure alerts are received even if one email fails
2. **Monitor Alert Frequency**: If you receive too many alerts, adjust thresholds in `security_alerter.php`
3. **Review Alerts Regularly**: Check security events dashboard regularly to identify patterns
4. **Test Alerting**: Periodically test that alerts are working correctly
5. **Keep Email Credentials Secure**: Store email credentials in `.env` file (not in code)

## Troubleshooting

### Alerts Not Sending

1. Check `SECURITY_ALERTING_ENABLED` is set to `true`
2. Verify `ADMIN_EMAIL` is configured correctly
3. Check email server credentials in `.env`
4. Review PHP error logs for email sending errors
5. Verify `security_alerts_sent` table exists

### Too Many Alerts

1. Increase thresholds in `security_alerter.php`
2. Increase cooldown period
3. Review and fix the root cause of frequent security events

### Missing Alerts

1. Check spam/junk folders
2. Verify email server is working
3. Check PHP error logs
4. Ensure `SecurityEventLogger` is properly initialized

---

*Last updated: [Current Date]*

