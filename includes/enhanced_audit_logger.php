<?php
/**
 * Enhanced Audit Logger with Full RBAC Support
 * Logs all admin actions with detailed tracking including:
 * - Previous/new values
 * - IP address and user agent
 * - Severity levels
 * - Target user tracking
 */

class EnhancedAuditLogger
{
    private static $conn;

    public static function init($connection)
    {
        self::$conn = $connection;
        if (!self::$conn) {
            error_log('EnhancedAuditLogger: Connection is NULL!');
        }
    }

    /**
     * Log an admin action with comprehensive details
     * 
     * @param string $actionType Type of action (RESET_PASSWORD, UPDATE_PROFILE, etc.)
     * @param string|null $targetUserId ID of affected user
     * @param string|null $targetUserName Name of affected user (for display)
     * @param string|null $previousValue Value before change (will be sanitized)
     * @param string|null $newValue Value after change (will be sanitized)
     * @param string $severity Severity level: low, medium, high, critical
     * @param string|null $details Additional context
     * @return bool Success status
     */
    public static function log(
        $actionType,
        $targetUserId = null,
        $targetUserName = null,
        $previousValue = null,
        $newValue = null,
        $severity = 'low',
        $details = ''
    ) {
        if (!self::$conn) {
            error_log('EnhancedAuditLogger: Database connection not initialized');
            return false;
        }

        // Get current admin info from session
        $adminId = $_SESSION['user_id'] ?? 'SYSTEM';
        $adminName = $_SESSION['username'] ?? 'System';

        // Get IP address
        $ipAddress = self::getClientIp();

        // Get user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Sanitize sensitive data - NEVER log passwords
        $previousValue = self::sanitizeSensitiveData($previousValue);
        $newValue = self::sanitizeSensitiveData($newValue);

        // Prepare query with all new fields
        $query = "INSERT INTO admin_logs 
                  (admin_id, admin_name, action_type, target_user_id, target_user, 
                   previous_value, new_value, details, ip_address, user_agent, severity, timestamp) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = self::$conn->prepare($query);

        if (!$stmt) {
            error_log("EnhancedAuditLogger: Failed to prepare statement: " . self::$conn->error);
            return false;
        }

        $stmt->bind_param(
            'sssssssssss',
            $adminId,
            $adminName,
            $actionType,
            $targetUserId,
            $targetUserName,
            $previousValue,
            $newValue,
            $details,
            $ipAddress,
            $userAgent,
            $severity
        );

        $success = $stmt->execute();

        if (!$success) {
            error_log("EnhancedAuditLogger: Failed to log action: " . $stmt->error);
        }

        return $success;
    }

    /**
     * Sanitize sensitive data before logging
     * NEVER log passwords, tokens, or other secrets
     */
    private static function sanitizeSensitiveData($value)
    {
        if ($value === null) {
            return null;
        }

        // Check for sensitive keywords
        $sensitiveKeywords = ['password', 'token', 'secret', 'key', 'otp', 'pin'];
        $lowerValue = strtolower($value);

        foreach ($sensitiveKeywords as $keyword) {
            if (strpos($lowerValue, $keyword) !== false) {
                return '[REDACTED - SENSITIVE DATA]';
            }
        }

        // Limit length to prevent log pollution
        if (strlen($value) > 1000) {
            return substr($value, 0, 1000) . '... [TRUNCATED]';
        }

        return $value;
    }

    /**
     * Get real client IP address (considers proxies)
     */
    private static function getClientIp()
    {
        // Check for proxy headers first
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }

        // Validate IP address
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return 'Unknown';
    }

    /**
     * Query audit logs with filters
     * 
     * @param array $filters Associative array of filters
     * @return array Results
     */
    public static function query($filters = [])
    {
        if (!self::$conn) {
            return [];
        }

        $where = [];
        $params = [];
        $types = '';

        // Build WHERE clause based on filters
        if (isset($filters['admin_id'])) {
            $where[] = "admin_id = ?";
            $params[] = $filters['admin_id'];
            $types .= 's';
        }

        if (isset($filters['target_user_id'])) {
            $where[] = "target_user_id = ?";
            $params[] = $filters['target_user_id'];
            $types .= 's';
        }

        if (isset($filters['action_type'])) {
            $where[] = "action_type = ?";
            $params[] = $filters['action_type'];
            $types .= 's';
        }

        if (isset($filters['severity'])) {
            $where[] = "severity = ?";
            $params[] = $filters['severity'];
            $types .= 's';
        }

        if (isset($filters['from_date'])) {
            $where[] = "timestamp >= ?";
            $params[] = $filters['from_date'];
            $types .= 's';
        }

        if (isset($filters['to_date'])) {
            $where[] = "timestamp <= ?";
            $params[] = $filters['to_date'];
            $types .= 's';
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        $limit = $filters['limit'] ?? 100;

        $query = "SELECT * FROM admin_logs $whereClause ORDER BY timestamp DESC LIMIT $limit";

        if (empty($params)) {
            $result = self::$conn->query($query);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }

        $stmt = self::$conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
