<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/env_loader.php';

// Load .env from root directory
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
} else {
    // Fallback to old location
    loadEnv(__DIR__ . '/env');
}

// Email template helper (adds header/footer and plaintext AltBody)
include_once __DIR__ . '/email_template.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOTPEmail($email, $otp)
{
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

function sendTrainerCredentialsEmail($email, $name, $username, $password)
{
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

function sendContactReply($email, $subject, $replyMessage, $originalMessage = '')
{
    $mail = new PHPMailer(true);

    try {
        // Get email credentials
        $emailHost = getenv('EMAIL_HOST');
        $emailUser = getenv('EMAIL_USER');
        $emailPass = getenv('EMAIL_PASS');
        $emailPort = getenv('EMAIL_PORT');

        // Check if credentials are configured
        if (!$emailHost || !$emailUser || !$emailPass) {
            error_log("Email credentials not configured. Host: $emailHost, User: $emailUser");
            throw new Exception('Email credentials are not configured. Please check your environment settings.');
        }

        // Server settings
        $mail->isSMTP();
        $mail->Host = $emailHost;
        $mail->SMTPAuth = true;
        $mail->Username = $emailUser;
        $mail->Password = $emailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $emailPort ?: 587; // Default to 587 if not set

        // Enable verbose debug output for troubleshooting
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = function($str, $level) {
        //     error_log("SMTP Debug level $level; message: $str");
        // };

        // Recipients
        $mail->setFrom($emailUser, 'Fit & Brawl Gym');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Build the inner HTML content for the email template
        $innerHtml = '<p>Hello,</p>';
        $innerHtml .= '<p>Thank you for contacting Fit & Brawl Gym. Here is our response to your inquiry:</p>';

        $innerHtml .= '<div style="background: #f8f9fa; padding: 20px; border-left: 4px solid #d5ba2b; margin: 20px 0; border-radius: 4px;">';
        $innerHtml .= nl2br(htmlspecialchars($replyMessage));
        $innerHtml .= '</div>';

        if ($originalMessage) {
            $innerHtml .= '<div style="background: #f0f0f0; padding: 15px; margin-top: 20px; border-radius: 4px; font-size: 0.9em;">';
            $innerHtml .= '<strong>Your Original Message:</strong><br>';
            $innerHtml .= nl2br(htmlspecialchars($originalMessage));
            $innerHtml .= '</div>';
        }

        $innerHtml .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;">';
        $innerHtml .= '<p><strong>Best regards,</strong><br>Fit & Brawl Gym Team</p>';
        $innerHtml .= '<p style="font-size: 0.9em; color: #666;">';
        $innerHtml .= 'If you have any further questions, please don\'t hesitate to contact us.';
        $innerHtml .= '</p>';
        $innerHtml .= '</div>';

        // Apply the standard email template (with header/footer)
        applyEmailTemplate($mail, $innerHtml);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMsg = "Failed to send contact reply email to $email: " . $e->getMessage();
        error_log($errorMsg);
        // Throw the exception so the API can return the actual error message
        throw new Exception($e->getMessage());
    }
}

