<?php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/encryption.php'; // Add encryption support
require_once __DIR__ . '/../../includes/mail_config.php'; // Use shared mail config
include_once __DIR__ . '/../../includes/env_loader.php';
loadEnv(__DIR__ . '/../../.env');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/email_template.php';

header('Content-Type: application/json');

// Check if email is provided in the request
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? null;

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    // Check if user exists and is not yet verified
    $stmt = $conn->prepare("SELECT id, username, is_verified, verification_token FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        exit;
    }

    $user = $result->fetch_assoc();

    if ($user['is_verified'] == 1) {
        echo json_encode(['success' => false, 'message' => 'Email already verified. Please login.']);
        exit;
    }

    // Generate new verification token if needed
    $verificationToken = $user['verification_token'];
    if (empty($verificationToken)) {
        $verificationToken = bin2hex(random_bytes(32));
        $updateStmt = $conn->prepare("UPDATE users SET verification_token = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $verificationToken, $email);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Build verification URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    if (ENVIRONMENT === 'production') {
        $verificationLink = $protocol . '://' . $host . '/php/verify-email.php?token=' . $verificationToken;
    } else {
        $verificationLink = $protocol . '://' . $host . PUBLIC_PATH . '/php/verify-email.php?token=' . $verificationToken;
    }

    // Send verification email
    $mail = new PHPMailer(true);
    configureMailerSMTP($mail);
    $mail->addAddress($email, $user['username']);
    $mail->isHTML(true);
    $mail->Subject = 'Verify Your Email - FitXBrawl';

    $html = "<h2>Welcome to FitXBrawl, " . htmlspecialchars($user['username']) . "!</h2>"
        . "<p>Click the link below to verify your email:</p>"
        . "<p><a href='" . htmlspecialchars($verificationLink) . "'>" . htmlspecialchars($verificationLink) . "</a></p>"
        . "<p>This link will confirm your account registration.</p>";

    applyEmailTemplate($mail, $html);

    if ($mail->send()) {
        echo json_encode(['success' => true, 'message' => 'Verification email sent successfully!']);
    } else {
        throw new Exception('Failed to send email');
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Resend verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again later.']);
}
?>
