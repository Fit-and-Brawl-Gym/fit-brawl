<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $email = $_SESSION['reset_email'];

    // Update database with new OTP
    $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $expiry, $email);

    if($stmt->execute() && sendOTPEmail($email, $otp)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to send OTP']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}