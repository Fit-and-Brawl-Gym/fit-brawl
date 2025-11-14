<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/mail_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['reset_email'])) {
    echo json_encode(['error' => 'No email found in session']);
    exit;
}

// Initialize resend counter if not set
if (!isset($_SESSION['otp_resend_count'])) {
    $_SESSION['otp_resend_count'] = 0;
}

// Check if resend limit reached (5 resends)
if ($_SESSION['otp_resend_count'] >= 5) {
    echo json_encode(['error' => 'Maximum resend limit reached. Please try again later or contact support.']);
    exit;
}

try {
    // Generate new OTP
    $otp = sprintf("%06d", random_int(0, 999999));
    $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    $email = $_SESSION['reset_email'];

    // Update database with new OTP
    $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $otp, $expiry, $email);
    
    if ($stmt->execute() && sendOTPEmail($email, $otp)) {
        // Increment resend counter
        $_SESSION['otp_resend_count']++;
        $remaining = 5 - $_SESSION['otp_resend_count'];
        
        echo json_encode([
            'success' => true,
            'resend_count' => $_SESSION['otp_resend_count'],
            'remaining_resends' => $remaining
        ]);
    } else {
        throw new Exception("Failed to update or send OTP");
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
