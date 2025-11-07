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

function sendTrainerCredentialsEmail($email, $name, $username, $password) {
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
        $mail->setFrom(getenv('EMAIL_USER'), 'FitXBrawl');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to FitXBrawl - Your Trainer Account';

        // Use the shared email template
        $html = "<h2>Welcome to FitXBrawl, " . htmlspecialchars($name) . "!</h2>"
            . "<p>Your trainer account has been created successfully. Here are your login credentials:</p>"
            . "<div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>"
            . "<p style='margin: 5px 0;'><strong>Email:</strong> " . htmlspecialchars($email) . "</p>"
            . "<p style='margin: 5px 0;'><strong>Username:</strong> " . htmlspecialchars($username) . "</p>"
            . "<p style='margin: 5px 0;'><strong>Temporary Password:</strong> " . htmlspecialchars($password) . "</p>"
            . "</div>"
            . "<p><strong style='color: #d5ba2b;'>Important:</strong> Please change your password after your first login for security purposes.</p>"
            . "<p>You can login at: <a href='" . getenv('APP_URL') . "/public/php/login.php'>FitXBrawl Login</a></p>"
            . "<p>If you have any questions, please contact the administrator.</p>";

        applyEmailTemplate($mail, $html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Failed to send trainer credentials email: " . $e->getMessage());
        return false;
    }
}
