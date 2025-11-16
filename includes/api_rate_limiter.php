<?php
/**
 * Simple per-identifier API rate limiter used for synchronous PHP endpoints.
 */
class ApiRateLimiter
{
    private const DEFAULT_MAX_REQUESTS = 30;
    private const DEFAULT_WINDOW_SECONDS = 60;

    private static bool $tableReady = false;

    private static function ensureTable(mysqli $conn): void
    {
        if (self::$tableReady) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS api_rate_limits (
                    identifier VARCHAR(255) NOT NULL,
                    request_count INT NOT NULL DEFAULT 1,
                    window_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        if ($conn->query($sql) === true) {
            self::$tableReady = true;
        } else {
            error_log('Failed to ensure api_rate_limits table: ' . $conn->error);
        }
    }

    /**
     * Checks and increments the rate limit for an identifier.
     *
     * @return array{blocked: bool, retry_after: int, remaining: int}
     */
    public static function checkAndIncrement(mysqli $conn, string $identifier, int $maxRequests = self::DEFAULT_MAX_REQUESTS, int $windowSeconds = self::DEFAULT_WINDOW_SECONDS): array
    {
        if (!$identifier) {
            return ['blocked' => false, 'retry_after' => 0, 'remaining' => $maxRequests];
        }

        self::ensureTable($conn);

        $stmt = $conn->prepare("SELECT request_count, window_start FROM api_rate_limits WHERE identifier = ?");
        if (!$stmt) {
            return ['blocked' => false, 'retry_after' => 0, 'remaining' => $maxRequests];
        }

        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $now = time();

        if (!$row) {
            $insert = $conn->prepare("INSERT INTO api_rate_limits (identifier, request_count, window_start) VALUES (?, 1, NOW())");
            if ($insert) {
                $insert->bind_param('s', $identifier);
                $insert->execute();
                $insert->close();
            }
            return ['blocked' => false, 'retry_after' => 0, 'remaining' => $maxRequests - 1];
        }

        $windowStart = strtotime($row['window_start']);
        $elapsed = $now - $windowStart;

        if ($elapsed > $windowSeconds) {
            $reset = $conn->prepare("UPDATE api_rate_limits SET request_count = 1, window_start = NOW() WHERE identifier = ?");
            if ($reset) {
                $reset->bind_param('s', $identifier);
                $reset->execute();
                $reset->close();
            }
            return ['blocked' => false, 'retry_after' => 0, 'remaining' => $maxRequests - 1];
        }

        $count = (int) $row['request_count'];
        if ($count >= $maxRequests) {
            $retryAfter = max(0, $windowSeconds - $elapsed);
            return ['blocked' => true, 'retry_after' => $retryAfter, 'remaining' => 0];
        }

        $update = $conn->prepare("UPDATE api_rate_limits SET request_count = request_count + 1 WHERE identifier = ?");
        if ($update) {
            $update->bind_param('s', $identifier);
            $update->execute();
            $update->close();
        }

        return ['blocked' => false, 'retry_after' => 0, 'remaining' => max(0, $maxRequests - ($count + 1))];
    }
}
