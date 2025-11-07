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
