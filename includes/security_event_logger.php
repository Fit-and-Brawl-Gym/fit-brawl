<?php
/**
 * Security Event Logger
 * Logs security-related events for monitoring and auditing
 */
class SecurityEventLogger {
    private static $conn = null;
    private static $tableReady = false;

    /**
     * Initialize the logger with database connection
     */
    public static function init($conn) {
        self::$conn = $conn;
        self::ensureTable();
    }

    /**
     * Ensure security_events table exists
     */
    private static function ensureTable() {
        if (self::$tableReady || !self::$conn) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS security_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_type VARCHAR(50) NOT NULL,
                    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
                    user_id VARCHAR(50) NULL,
                    username VARCHAR(100) NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    endpoint VARCHAR(255) NULL,
                    details TEXT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_event_type (event_type),
                    INDEX idx_severity (severity),
                    INDEX idx_user_id (user_id),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        if (self::$conn->query($sql) === true) {
            self::$tableReady = true;
        } else {
            error_log('SecurityEventLogger: Failed to ensure security_events table - ' . self::$conn->error);
        }
    }

    /**
     * Log a security event
     *
     * @param string $eventType Event type (e.g., 'csrf_failure', 'rate_limit_exceeded', 'unauthorized_access')
     * @param string $severity Severity level ('low', 'medium', 'high', 'critical')
     * @param array $context Additional context (user_id, username, ip_address, endpoint, details)
     */
    public static function log($eventType, $severity = 'medium', $context = []) {
        if (!self::$conn) {
            // Fallback to error_log if DB not initialized
            error_log("Security Event [{$severity}]: {$eventType} - " . json_encode($context));
            return;
        }

        self::ensureTable();

        $userId = $context['user_id'] ?? ($_SESSION['user_id'] ?? null);
        $username = $context['username'] ?? ($_SESSION['username'] ?? null);
        $ipAddress = $context['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $userAgent = $context['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
        $endpoint = $context['endpoint'] ?? ($_SERVER['REQUEST_URI'] ?? null);
        $details = isset($context['details']) ? json_encode($context['details']) : null;

        // Truncate long fields
        $userAgent = $userAgent ? substr($userAgent, 0, 500) : null;
        $endpoint = $endpoint ? substr($endpoint, 0, 255) : null;

        $stmt = self::$conn->prepare("
            INSERT INTO security_events
            (event_type, severity, user_id, username, ip_address, user_agent, endpoint, details)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            error_log('SecurityEventLogger: Failed to prepare statement - ' . self::$conn->error);
            return;
        }

        $stmt->bind_param("ssssssss",
            $eventType,
            $severity,
            $userId,
            $username,
            $ipAddress,
            $userAgent,
            $endpoint,
            $details
        );

        if (!$stmt->execute()) {
            error_log('SecurityEventLogger: Failed to execute - ' . $stmt->error);
        }

        $stmt->close();
    }

    /**
     * Log CSRF token validation failure
     */
    public static function logCSRFFailure($endpoint = null) {
        self::log('csrf_failure', 'high', [
            'endpoint' => $endpoint,
            'details' => [
                'token_provided' => !empty($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'
            ]
        ]);
    }

    /**
     * Log rate limit violation
     */
    public static function logRateLimitExceeded($identifier, $maxRequests, $windowSeconds) {
        self::log('rate_limit_exceeded', 'medium', [
            'details' => [
                'identifier' => $identifier,
                'max_requests' => $maxRequests,
                'window_seconds' => $windowSeconds
            ]
        ]);
    }

    /**
     * Log unauthorized access attempt
     */
    public static function logUnauthorizedAccess($endpoint = null, $requiredRole = null) {
        self::log('unauthorized_access', 'high', [
            'endpoint' => $endpoint,
            'details' => [
                'required_role' => $requiredRole,
                'user_role' => $_SESSION['role'] ?? null,
                'authenticated' => isset($_SESSION['user_id'])
            ]
        ]);
    }

    /**
     * Log authentication failure
     */
    public static function logAuthenticationFailure($email = null, $reason = null) {
        self::log('authentication_failure', 'medium', [
            'details' => [
                'email' => $email,
                'reason' => $reason
            ]
        ]);
    }

    /**
     * Log suspicious activity
     */
    public static function logSuspiciousActivity($activity, $details = []) {
        self::log('suspicious_activity', 'high', [
            'details' => array_merge(['activity' => $activity], $details)
        ]);
    }

    /**
     * Log file upload security event
     */
    public static function logFileUploadEvent($eventType, $filename = null, $details = []) {
        self::log($eventType, 'medium', [
            'details' => array_merge([
                'filename' => $filename,
                'file_size' => $_FILES['file']['size'] ?? null,
                'file_type' => $_FILES['file']['type'] ?? null
            ], $details)
        ]);
    }

    /**
     * Get recent security events
     *
     * @param int $limit Number of events to retrieve
     * @param string $severity Filter by severity (optional)
     * @return array
     */
    public static function getRecentEvents($limit = 100, $severity = null) {
        if (!self::$conn) {
            return [];
        }

        self::ensureTable();

        $sql = "SELECT * FROM security_events";
        $params = [];
        $types = '';

        if ($severity) {
            $sql .= " WHERE severity = ?";
            $params[] = $severity;
            $types .= 's';
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= 'i';

        $stmt = self::$conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $row['details'] = json_decode($row['details'], true);
            $events[] = $row;
        }

        $stmt->close();
        return $events;
    }
}

