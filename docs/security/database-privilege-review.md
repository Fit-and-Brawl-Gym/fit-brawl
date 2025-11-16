# Database Privilege Review Script

This script helps audit and implement least privilege database access for the Fit & Brawl application.

## Current Setup

The application currently uses a single database user with full privileges. We need to create restricted users with minimal required permissions.

## Recommended Database Users

### 1. **Application User** (fitbrawl_app)
- **Purpose**: Normal application operations
- **Privileges**:
  - SELECT, INSERT, UPDATE on all tables
  - DELETE only on necessary tables (user_reservations, login_attempts, etc.)
  - NO DROP, ALTER, CREATE privileges
  - NO DELETE on critical tables (users, trainers, admin_logs)

### 2. **Read-Only User** (fitbrawl_readonly)
- **Purpose**: Reporting, analytics, backups
- **Privileges**:
  - SELECT only on all tables
  - NO write operations

### 3. **Admin User** (fitbrawl_admin)
- **Purpose**: Schema migrations, maintenance
- **Privileges**:
  - Full privileges for schema changes
  - Should only be used during deployment/migration

## Implementation Steps

### Step 1: Audit Current Permissions

```sql
-- Check current database users
SELECT User, Host FROM mysql.user WHERE User LIKE 'fitbrawl%' OR User LIKE 'root';

-- Check current privileges
SHOW GRANTS FOR CURRENT_USER();

-- List all tables in database
SELECT TABLE_NAME, TABLE_TYPE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'fitbrawl_db';
```

### Step 2: Create Restricted Application User

```sql
-- Create application user (change password!)
CREATE USER 'fitbrawl_app'@'localhost' IDENTIFIED BY 'CHANGE_THIS_STRONG_PASSWORD';

-- Grant basic SELECT, INSERT, UPDATE on all tables
GRANT SELECT, INSERT, UPDATE ON fitbrawl_db.* TO 'fitbrawl_app'@'localhost';

-- Grant DELETE only on specific tables that need it
GRANT DELETE ON fitbrawl_db.user_reservations TO 'fitbrawl_app'@'localhost';
GRANT DELETE ON fitbrawl_db.login_attempts TO 'fitbrawl_app'@'localhost';
GRANT DELETE ON fitbrawl_db.password_reset_tokens TO 'fitbrawl_app'@'localhost';
GRANT DELETE ON fitbrawl_db.email_verification_tokens TO 'fitbrawl_app'@'localhost';
GRANT DELETE ON fitbrawl_db.active_sessions TO 'fitbrawl_app'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;
```

### Step 3: Create Read-Only User

```sql
-- Create read-only user (change password!)
CREATE USER 'fitbrawl_readonly'@'localhost' IDENTIFIED BY 'CHANGE_THIS_READONLY_PASSWORD';

-- Grant SELECT only
GRANT SELECT ON fitbrawl_db.* TO 'fitbrawl_readonly'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;
```

### Step 4: Revoke Unnecessary Privileges

```sql
-- Remove DELETE privilege on critical tables from app user
REVOKE DELETE ON fitbrawl_db.users FROM 'fitbrawl_app'@'localhost';
REVOKE DELETE ON fitbrawl_db.trainers FROM 'fitbrawl_app'@'localhost';
REVOKE DELETE ON fitbrawl_db.admin_logs FROM 'fitbrawl_app'@'localhost';
REVOKE DELETE ON fitbrawl_db.activity_log FROM 'fitbrawl_app'@'localhost';
REVOKE DELETE ON fitbrawl_db.security_events FROM 'fitbrawl_app'@'localhost';
REVOKE DELETE ON fitbrawl_db.password_history FROM 'fitbrawl_app'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;
```

### Step 5: Update Application Configuration

Update `.env` file:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=fitbrawl_app  # Changed from root or full-privilege user
DB_PASS=CHANGE_THIS_STRONG_PASSWORD
DB_NAME=fitbrawl_db

# Read-only connection (for reports/analytics)
DB_READONLY_USER=fitbrawl_readonly
DB_READONLY_PASS=CHANGE_THIS_READONLY_PASSWORD
```

## Security Checklist

- [ ] Created restricted application user (fitbrawl_app)
- [ ] Created read-only user (fitbrawl_readonly)
- [ ] Tested application with new user
- [ ] Verified DELETE restrictions on critical tables
- [ ] Removed old high-privilege user from application
- [ ] Updated `.env` configuration
- [ ] Documented user credentials securely
- [ ] Set up credential rotation schedule
- [ ] Verified no application errors with new permissions

## Testing

### Test Application User

```sql
-- Connect as fitbrawl_app
mysql -u fitbrawl_app -p fitbrawl_db

-- Should work:
SELECT * FROM users LIMIT 1;
INSERT INTO login_attempts (identifier, attempt_time) VALUES ('test', NOW());
UPDATE users SET name = 'Test' WHERE id = 999999;
DELETE FROM login_attempts WHERE id = 999999;

-- Should fail:
DELETE FROM users WHERE id = 1;  -- Should be denied
DROP TABLE users;                  -- Should be denied
ALTER TABLE users ADD test VARCHAR(255);  -- Should be denied
```

### Test Read-Only User

```sql
-- Connect as fitbrawl_readonly
mysql -u fitbrawl_readonly -p fitbrawl_db

-- Should work:
SELECT * FROM users LIMIT 1;
SELECT * FROM admin_logs;

-- Should fail:
INSERT INTO users VALUES (...);  -- Should be denied
UPDATE users SET name = 'Test';  -- Should be denied
DELETE FROM users WHERE id = 1;  -- Should be denied
```

## Tables That SHOULD Have DELETE

- `user_reservations` - Cancel bookings
- `login_attempts` - Cleanup old attempts
- `password_reset_tokens` - Remove used tokens
- `email_verification_tokens` - Remove verified tokens
- `active_sessions` - Session cleanup

## Tables That SHOULD NOT Have DELETE

- `users` - User accounts (soft delete only with `deleted_at`)
- `trainers` - Trainer profiles
- `admin_logs` - Audit trail
- `activity_log` - Activity history
- `security_events` - Security audit
- `password_history` - Password tracking
- `subscriptions` - Payment records
- `transactions` - Financial records

## Rollback Plan

If issues occur:

```sql
-- Revert to old user
UPDATE fitbrawl_db SET DB_USER='old_username' in .env

-- Remove new users
DROP USER 'fitbrawl_app'@'localhost';
DROP USER 'fitbrawl_readonly'@'localhost';

-- Restart application
```

## Monitoring

After implementation, monitor for:
- Permission denied errors in PHP error log
- Failed queries in application log
- User complaints about missing functionality

## References

- [MySQL Privilege System](https://dev.mysql.com/doc/refman/8.0/en/privileges-provided.html)
- [Principle of Least Privilege](https://en.wikipedia.org/wiki/Principle_of_least_privilege)
- OWASP: Database Security
