<?php
/**
 * PasswordHistory helper prevents users from reusing recent passwords.
 */
class PasswordHistory
{
    private const MAX_HISTORY = 5;
    private static $tableEnsured = false;

    /**
     * Ensure supporting table exists.
     */
    private static function ensureTable($conn): bool
    {
        if (self::$tableEnsured) {
            return true;
        }

        if (!$conn) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS password_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(64) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_created (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$conn->query($sql)) {
            error_log('PasswordHistory: Failed to ensure table - ' . $conn->error);
            return false;
        }

        self::$tableEnsured = true;
        return true;
    }

    /**
     * Check if the provided plaintext password matches recent history.
     */
    public static function hasBeenUsed($conn, $userId, $plainPassword, $historySize = self::MAX_HISTORY): bool
    {
        if (!$conn || empty($userId) || $plainPassword === '') {
            return false;
        }

        if (!self::ensureTable($conn)) {
            return false;
        }

        $historySize = max(1, (int) $historySize);

        $stmt = $conn->prepare(
            'SELECT password_hash FROM password_history WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ?'
        );
        if (!$stmt) {
            error_log('PasswordHistory: Failed to prepare lookup - ' . $conn->error);
            return false;
        }

        $stmt->bind_param('si', $userId, $historySize);
        $stmt->execute();
        $result = $stmt->get_result();

        $used = false;
        while ($row = $result->fetch_assoc()) {
            if (password_verify($plainPassword, $row['password_hash'])) {
                $used = true;
                break;
            }
        }

        $stmt->close();
        return $used;
    }

    /**
     * Record the previous password hash and prune older entries.
     */
    public static function record($conn, $userId, $passwordHash): void
    {
        if (!$conn || empty($userId) || empty($passwordHash)) {
            return;
        }

        if (!self::ensureTable($conn)) {
            return;
        }

        $stmt = $conn->prepare('INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)');
        if (!$stmt) {
            error_log('PasswordHistory: Failed to prepare insert - ' . $conn->error);
            return;
        }

        $stmt->bind_param('ss', $userId, $passwordHash);
        if (!$stmt->execute()) {
            error_log('PasswordHistory: Insert failed - ' . $stmt->error);
        }
        $stmt->close();

        self::prune($conn, $userId);
    }

    /**
     * Retain only the latest MAX_HISTORY rows per user.
     */
    private static function prune($conn, $userId): void
    {
        $stmt = $conn->prepare('SELECT COUNT(*) as total FROM password_history WHERE user_id = ?');
        if (!$stmt) {
            error_log('PasswordHistory: Failed to prepare count - ' . $conn->error);
            return;
        }

        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total = (int) ($row['total'] ?? 0);
        $stmt->close();

        if ($total <= self::MAX_HISTORY) {
            return;
        }

        $excess = $total - self::MAX_HISTORY;
        $deleteStmt = $conn->prepare('DELETE FROM password_history WHERE user_id = ? ORDER BY created_at ASC, id ASC LIMIT ?');
        if (!$deleteStmt) {
            error_log('PasswordHistory: Failed to prepare prune delete - ' . $conn->error);
            return;
        }

        $deleteStmt->bind_param('si', $userId, $excess);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
}
