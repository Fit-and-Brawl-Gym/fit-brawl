<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/env');

// Email template helper (adds header/footer and plaintext AltBody)
include_once __DIR__ . '/email_template.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('EMAIL_PORT');

    // Recipients
    $mail->setFrom(getenv('EMAIL_USER'), 'FitXBrawl'); // Use same email as Username
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your FitXBrawl OTP';
        // Use the shared email template so header/footer are included
        $html = "<p>Your OTP for password reset is: <strong>" . htmlspecialchars($otp) . "</strong></p>"
            . "<p>This OTP will expire in 5 minutes.</p>";
        applyEmailTemplate($mail, $html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}