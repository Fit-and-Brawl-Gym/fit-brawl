<?php

function checkOTPRateLimit($conn, $email, $maxAttempts = 3, $timeWindow = 300) {
    // Get current user's OTP attempts
    $stmt = $conn->prepare("SELECT otp_attempts, last_otp_request FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        return ['allowed' => false, 'message' => 'User not found'];
    }

    $currentTime = time();
    $lastRequestTime = strtotime($user['last_otp_request'] ?? '0');
    $timeDiff = $currentTime - $lastRequestTime;

    // Reset attempts if time window has passed
    if ($timeDiff > $timeWindow) {
        $stmt = $conn->prepare("UPDATE users SET otp_attempts = 1, last_otp_request = NOW() WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return ['allowed' => true, 'message' => ''];
    }

    // Check if max attempts reached
    if ($user['otp_attempts'] >= $maxAttempts) {
        $waitTime = $timeWindow - $timeDiff;
        return [
            'allowed' => false,
            'message' => "Too many attempts. Please wait " . ceil($waitTime/60) . " minutes."
        ];
    }

    // Increment attempts
    $stmt = $conn->prepare("UPDATE users SET otp_attempts = otp_attempts + 1, last_otp_request = NOW() WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    return ['allowed' => true, 'message' => ''];
}

// ------------------------------
// Login attempt rate limiting
// ------------------------------

function ensureLoginAttemptsTable($conn) {
    static $tableReady = false;

    if ($tableReady || !$conn) {
        return;
    }

    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
                identifier VARCHAR(255) PRIMARY KEY,
                attempt_count INT NOT NULL DEFAULT 0,
                last_attempt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql) === true) {
        $tableReady = true;
    } else {
        error_log('Failed to ensure login_attempts table: ' . $conn->error);
    }
}

function logFailedLoginAttempt($conn, $identifier) {
    if (!$conn || empty($identifier)) {
        return;
    }

    ensureLoginAttemptsTable($conn);

    $stmt = $conn->prepare("INSERT INTO login_attempts (identifier, attempt_count, last_attempt)
        VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1, last_attempt = NOW()");

    if ($stmt) {
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $stmt->close();
    }
}

function clearLoginAttempts($conn, $identifier) {
    if (!$conn || empty($identifier)) {
        return;
    }

    ensureLoginAttemptsTable($conn);

    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ?");
    if ($stmt) {
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $stmt->close();
    }
}

function isLoginBlocked($conn, $identifier, $maxAttempts = 5, $windowSeconds = 900) {
    if (!$conn || empty($identifier)) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    ensureLoginAttemptsTable($conn);

    $stmt = $conn->prepare("SELECT attempt_count, last_attempt FROM login_attempts WHERE identifier = ?");
    if (!$stmt) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    $stmt->bind_param('s', $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    $lastAttempt = strtotime($row['last_attempt']);
    $elapsed = time() - $lastAttempt;

    if ($elapsed > $windowSeconds) {
        clearLoginAttempts($conn, $identifier);
        return ['blocked' => false, 'retry_after' => 0];
    }

    if ((int)$row['attempt_count'] >= $maxAttempts) {
        $retryAfter = max(0, $windowSeconds - $elapsed);
        return ['blocked' => true, 'retry_after' => $retryAfter];
    }

    return ['blocked' => false, 'retry_after' => 0];
}

// ------------------------------
// Signup attempt rate limiting
// ------------------------------

function logSignupAttempt($conn, $identifier) {
    if (!$conn || empty($identifier)) {
        return;
    }

    ensureLoginAttemptsTable($conn);

    $stmt = $conn->prepare("INSERT INTO login_attempts (identifier, attempt_count, last_attempt)
        VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1, last_attempt = NOW()");

    if ($stmt) {
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $stmt->close();
    }
}

function isSignupBlocked($conn, $identifier, $maxAttempts = 5, $windowSeconds = 900) {
    if (!$conn || empty($identifier)) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    ensureLoginAttemptsTable($conn);

    $stmt = $conn->prepare("SELECT attempt_count, last_attempt FROM login_attempts WHERE identifier = ?");
    if (!$stmt) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    $stmt->bind_param('s', $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    $lastAttempt = strtotime($row['last_attempt']);
    $elapsed = time() - $lastAttempt;

    if ($elapsed > $windowSeconds) {
        clearLoginAttempts($conn, $identifier);
        return ['blocked' => false, 'retry_after' => 0];
    }

    if ((int)$row['attempt_count'] >= $maxAttempts) {
        $retryAfter = max(0, $windowSeconds - $elapsed);
        return ['blocked' => true, 'retry_after' => $retryAfter];
    }

    return ['blocked' => false, 'retry_after' => 0];
}
