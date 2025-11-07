<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/mail_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['reset_email'])) {
    echo json_encode(['error' => 'No email found in session']);
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
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to update or send OTP");
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
