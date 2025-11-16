<?php
/**
 * Session Tracker
 * Tracks active user sessions for revocation capabilities
 */
class SessionTracker {
    private static $conn = null;
    private static $tableReady = false;

    /**
     * Initialize the tracker with database connection
     */
    public static function init($conn) {
        self::$conn = $conn;
        self::ensureTable();
    }

    /**
     * Ensure active_sessions table exists
     */
    private static function ensureTable() {
        if (self::$tableReady || !self::$conn) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS active_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(50) NOT NULL,
                    session_id VARCHAR(128) NOT NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    login_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    is_current TINYINT(1) DEFAULT 0,
                    INDEX idx_user_id (user_id),
                    INDEX idx_session_id (session_id),
                    INDEX idx_is_current (is_current),
                    INDEX idx_last_activity (last_activity)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        if (self::$conn->query($sql) === true) {
            self::$tableReady = true;
        } else {
            error_log('SessionTracker: Failed to ensure active_sessions table - ' . self::$conn->error);
        }
    }

    /**
     * Register a new session
     */
    public static function registerSession($userId, $sessionId) {
        if (!self::$conn) {
            return false;
        }

        self::ensureTable();

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $userAgent = $userAgent ? substr($userAgent, 0, 500) : null;

        // Mark all other sessions for this user as not current
        $updateStmt = self::$conn->prepare("UPDATE active_sessions SET is_current = 0 WHERE user_id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("s", $userId);
            $updateStmt->execute();
            $updateStmt->close();
        }

        // Insert new session
        $stmt = self::$conn->prepare("
            INSERT INTO active_sessions (user_id, session_id, ip_address, user_agent, is_current)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                login_time = CURRENT_TIMESTAMP,
                last_activity = CURRENT_TIMESTAMP,
                is_current = 1
        ");

        if (!$stmt) {
            error_log('SessionTracker: Failed to prepare statement - ' . self::$conn->error);
            return false;
        }

        $stmt->bind_param("ssss", $userId, $sessionId, $ipAddress, $userAgent);

        if (!$stmt->execute()) {
            error_log('SessionTracker: Failed to execute - ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;
    }

    /**
     * Update session activity
     */
    public static function updateActivity($userId, $sessionId) {
        if (!self::$conn) {
            return false;
        }

        self::ensureTable();

        $stmt = self::$conn->prepare("
            UPDATE active_sessions
            SET last_activity = CURRENT_TIMESTAMP
            WHERE user_id = ? AND session_id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $userId, $sessionId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Revoke a session
     */
    public static function revokeSession($userId, $sessionId) {
        if (!self::$conn) {
            return false;
        }

        self::ensureTable();

        $stmt = self::$conn->prepare("DELETE FROM active_sessions WHERE user_id = ? AND session_id = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $userId, $sessionId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Revoke all sessions for a user (except current)
     */
    public static function revokeAllOtherSessions($userId, $currentSessionId) {
        if (!self::$conn) {
            return false;
        }

        self::ensureTable();

        $stmt = self::$conn->prepare("DELETE FROM active_sessions WHERE user_id = ? AND session_id != ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $userId, $currentSessionId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Get all active sessions for a user
     */
    public static function getUserSessions($userId) {
        if (!self::$conn) {
            return [];
        }

        self::ensureTable();

        $stmt = self::$conn->prepare("
            SELECT
                id,
                session_id,
                ip_address,
                user_agent,
                login_time,
                last_activity,
                is_current,
                TIMESTAMPDIFF(MINUTE, last_activity, NOW()) as minutes_inactive
            FROM active_sessions
            WHERE user_id = ?
            ORDER BY is_current DESC, last_activity DESC
        ");

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }

        $stmt->close();
        return $sessions;
    }

    /**
     * Check if a session is still valid (not revoked)
     */
    public static function isSessionValid($userId, $sessionId) {
        if (!self::$conn) {
            return true; // Fallback to allow if DB not available
        }

        self::ensureTable();

        $stmt = self::$conn->prepare("
            SELECT COUNT(*) as count
            FROM active_sessions
            WHERE user_id = ? AND session_id = ?
        ");

        if (!$stmt) {
            return true; // Fallback
        }

        $stmt->bind_param("ss", $userId, $sessionId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Clean up expired sessions (older than 10 hours)
     */
    public static function cleanupExpiredSessions() {
        if (!self::$conn) {
            return false;
        }

        self::ensureTable();

        $stmt = self::$conn->prepare("
            DELETE FROM active_sessions
            WHERE last_activity < DATE_SUB(NOW(), INTERVAL 10 HOUR)
        ");

        if (!$stmt) {
            return false;
        }

        $result = $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        return ['success' => $result, 'deleted' => $deleted];
    }

    /**
     * Get device/browser name from user agent
     */
    public static function getDeviceInfo($userAgent) {
        if (empty($userAgent)) {
            return 'Unknown Device';
        }

        // Simple device detection
        $device = 'Desktop';
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
            $device = 'Mobile';
        }

        // Browser detection
        $browser = 'Unknown Browser';
        if (preg_match('/Chrome/i', $userAgent) && !preg_match('/Edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edg/i', $userAgent)) {
            $browser = 'Edge';
        }

        return "$device - $browser";
    }
}

