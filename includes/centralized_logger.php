<?php
/**
 * Centralized Logger
 * Aggregates logs from multiple sources (security events, activity logs, application errors)
 * Provides unified logging interface and query capabilities
 */
class CentralizedLogger {
    private static $conn = null;
    private static $tableReady = false;
    private static $logLevels = ['debug', 'info', 'warning', 'error', 'critical'];
    private static $logSources = ['security', 'activity', 'application', 'database', 'email', 'system'];

    /**
     * Initialize the centralized logger
     */
    public static function init($conn) {
        self::$conn = $conn;
        self::ensureTable();
    }

    /**
     * Ensure unified_logs table exists
     */
    private static function ensureTable() {
        if (self::$tableReady || !self::$conn) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS unified_logs (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        if (self::$conn->query($sql) === true) {
            self::$tableReady = true;
        } else {
            error_log('CentralizedLogger: Failed to ensure unified_logs table - ' . self::$conn->error);
        }
    }

    /**
     * Log a message
     *
     * @param string $level Log level (debug, info, warning, error, critical)
     * @param string $source Log source (security, activity, application, database, email, system)
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function log($level, $source, $message, $context = []) {
        if (!self::$conn) {
            // Fallback to error_log
            error_log("[{$level}] [{$source}] {$message} - " . json_encode($context));
            return;
        }

        if (!in_array($level, self::$logLevels)) {
            $level = 'info';
        }

        if (!in_array($source, self::$logSources)) {
            $source = 'application';
        }

        self::ensureTable();

        $category = $context['category'] ?? null;
        $userId = $context['user_id'] ?? ($_SESSION['user_id'] ?? null);
        $username = $context['username'] ?? ($_SESSION['username'] ?? null);
        $ipAddress = $context['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $endpoint = $context['endpoint'] ?? ($_SERVER['REQUEST_URI'] ?? null);
        $stackTrace = $context['stack_trace'] ?? null;

        // Remove context fields that are stored separately
        $contextData = $context;
        unset($contextData['category'], $contextData['user_id'], $contextData['username'],
              $contextData['ip_address'], $contextData['endpoint'], $contextData['stack_trace']);

        $jsonContext = !empty($contextData) ? json_encode($contextData) : null;

        // Truncate long fields
        $message = substr($message, 0, 5000);
        $endpoint = $endpoint ? substr($endpoint, 0, 255) : null;
        $stackTrace = $stackTrace ? substr($stackTrace, 0, 10000) : null;

        $stmt = self::$conn->prepare("
            INSERT INTO unified_logs
            (log_level, log_source, category, message, user_id, username, ip_address, endpoint, context, stack_trace)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            error_log('CentralizedLogger: Failed to prepare statement - ' . self::$conn->error);
            return;
        }

        $stmt->bind_param("ssssssssss",
            $level,
            $source,
            $category,
            $message,
            $userId,
            $username,
            $ipAddress,
            $endpoint,
            $jsonContext,
            $stackTrace
        );

        if (!$stmt->execute()) {
            error_log('CentralizedLogger: Failed to execute - ' . $stmt->error);
        }

        $stmt->close();
    }

    /**
     * Log security event
     */
    public static function logSecurity($level, $message, $context = []) {
        self::log($level, 'security', $message, $context);
    }

    /**
     * Log activity event
     */
    public static function logActivity($level, $message, $context = []) {
        self::log($level, 'activity', $message, $context);
    }

    /**
     * Log application error
     */
    public static function logApplication($level, $message, $context = []) {
        self::log($level, 'application', $message, $context);
    }

    /**
     * Log database error
     */
    public static function logDatabase($level, $message, $context = []) {
        self::log($level, 'database', $message, $context);
    }

    /**
     * Log email event
     */
    public static function logEmail($level, $message, $context = []) {
        self::log($level, 'email', $message, $context);
    }

    /**
     * Log system event
     */
    public static function logSystem($level, $message, $context = []) {
        self::log($level, 'system', $message, $context);
    }

    /**
     * Get logs with filters
     *
     * @param array $filters Filters: level, source, category, user_id, ip_address, date_from, date_to, limit
     * @return array
     */
    public static function getLogs($filters = []) {
        if (!self::$conn) {
            return [];
        }

        self::ensureTable();

        $sql = "SELECT * FROM unified_logs WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['level'])) {
            $sql .= " AND log_level = ?";
            $params[] = $filters['level'];
            $types .= "s";
        }

        if (!empty($filters['source'])) {
            $sql .= " AND log_source = ?";
            $params[] = $filters['source'];
            $types .= "s";
        }

        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
            $types .= "s";
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "s";
        }

        if (!empty($filters['ip_address'])) {
            $sql .= " AND ip_address = ?";
            $params[] = $filters['ip_address'];
            $types .= "s";
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        $sql .= " ORDER BY created_at DESC";

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 100;
        $limit = max(1, min(1000, $limit)); // Between 1 and 1000
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types .= "i";

        $stmt = self::$conn->prepare($sql);
        if (!$stmt) {
            error_log('CentralizedLogger: Failed to prepare getLogs statement - ' . self::$conn->error);
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $row['context'] = $row['context'] ? json_decode($row['context'], true) : null;
            $logs[] = $row;
        }

        $stmt->close();
        return $logs;
    }

    /**
     * Get log statistics
     *
     * @param array $filters Same as getLogs
     * @return array Statistics by level and source
     */
    public static function getStatistics($filters = []) {
        if (!self::$conn) {
            return [];
        }

        self::ensureTable();

        $sql = "SELECT log_level, log_source, COUNT(*) as count
                FROM unified_logs WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        $sql .= " GROUP BY log_level, log_source ORDER BY count DESC";

        $stmt = self::$conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['log_level'] . '_' . $row['log_source'];
            $stats[$key] = (int)$row['count'];
        }

        $stmt->close();
        return $stats;
    }

    /**
     * Clean up old logs (retention policy)
     *
     * @param int $daysToKeep Number of days to keep logs
     * @return array Result with deleted count
     */
    public static function cleanupOldLogs($daysToKeep = 90) {
        if (!self::$conn) {
            return ['success' => false, 'deleted' => 0];
        }

        self::ensureTable();

        $stmt = self::$conn->prepare("
            DELETE FROM unified_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");

        if (!$stmt) {
            return ['success' => false, 'deleted' => 0];
        }

        $stmt->bind_param("i", $daysToKeep);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        return ['success' => true, 'deleted' => $deleted];
    }
}

