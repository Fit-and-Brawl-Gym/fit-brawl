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

function sendAccountLockNotification($email, $retryAfterSeconds, $ipAddress = 'unknown', $maxAttempts = 5)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('EMAIL_PORT');

        $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl Gym');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Security alert: login temporarily locked';

        $minutes = max(1, ceil($retryAfterSeconds / 60));
        $formattedIp = htmlspecialchars((string) $ipAddress, ENT_QUOTES, 'UTF-8');
        $innerHtml = "<h2>We detected multiple failed login attempts.</h2>";
        $innerHtml .= "<p>Your Fit & Brawl account has been temporarily locked after {$maxAttempts} unsuccessful attempts.";
        $innerHtml .= " The lockout will automatically clear in approximately {$minutes} minute" . ($minutes === 1 ? '' : 's') . ".</p>";
        $innerHtml .= "<p><strong>Recent IP address:</strong> {$formattedIp}</p>";
        $innerHtml .= '<p>If this was not you, we recommend resetting your password and contacting support immediately.</p>';
        $innerHtml .= '<p>You do not need to take any action if you initiated the attempts, simply wait for the cooldown and try again.</p>';

        applyEmailTemplate($mail, $innerHtml);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Failed to send account lock notification: ' . $e->getMessage());
        return false;
    }
}

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

        // Build login URL dynamically based on environment
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $loginUrl = $protocol . '://' . $host . (ENVIRONMENT === 'production' ? '' : BASE_PATH) . '/php/login.php';

        // Use the shared email template
        $html = "<h2>Welcome to FitXBrawl, " . htmlspecialchars($name) . "!</h2>"
            . "<p>Your trainer account has been created successfully. Here are your login credentials:</p>"
            . "<div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>"
            . "<p style='margin: 5px 0;'><strong>Email:</strong> " . htmlspecialchars($email) . "</p>"
            . "<p style='margin: 5px 0;'><strong>Temporary Password:</strong> " . htmlspecialchars($password) . "</p>"
            . "</div>"
            . "<p><strong style='color: #d5ba2b;'>Important:</strong> Please change your password after your first login for security purposes.</p>"
            . "<p>You can login at: <a href='" . htmlspecialchars($loginUrl) . "'>FitXBrawl Login</a></p>"
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

function sendTrainerBookingNotification($trainer_email, $trainer_name, $member_name, $date, $session_time, $class_type) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('EMAIL_PORT');

        $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl Gym');
        $mail->addAddress($trainer_email, $trainer_name);

        $formatted_date = date('l, F j, Y', strtotime($date));
        $session_hours = [
            'Morning' => '7:00 AM - 11:00 AM',
            'Afternoon' => '1:00 PM - 5:00 PM',
            'Evening' => '6:00 PM - 10:00 PM'
        ];
        $display_time = $session_hours[$session_time] ?? $session_time;

        $mail->isHTML(true);
        $mail->Subject = 'New Training Session Booking';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2>New Session Booking</h2>
                <p>Hello {$trainer_name},</p>
                <p>You have a new training session booking:</p>
                <div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>
                    <p><strong>Client:</strong> {$member_name}</p>
                    <p><strong>Date:</strong> {$formatted_date}</p>
                    <p><strong>Time:</strong> {$display_time}</p>
                    <p><strong>Class Type:</strong> {$class_type}</p>
                </div>
                <p>Log in to your trainer dashboard for more details.</p>
            </div>";
        return $mail->send();
        applyEmailTemplate($mail, $html);
    } catch (Exception $e) {
        error_log("Failed to send trainer notification email: " . $e->getMessage());
        return false;
    }
}

function sendMemberBookingCancellationNotification($email, $member_name, $trainer_name, $date, $session_time, $class_type, $reason = '') {
    $mail = new PHPMailer(true);

    try {
        // SMTP Setup
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('EMAIL_PORT');
        $mail->SMTPDebug = 2; // or 3 for more detailed output
        $mail->Debugoutput = 'error_log'; // log to PHP error log
        // Sender and Recipient
        $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl Gym');
        $mail->addAddress($email, $member_name);

        // Subject
        $mail->isHTML(true);
        $mail->Subject = "Class Cancellation Notice - $class_type on " . date('M d, Y', strtotime($date));

        // Message body
        $html = "
            <p>Hi <strong>" . htmlspecialchars($member_name) . "</strong>,</p>
            <p>We regret to inform you that your training session has been <strong>cancelled</strong> due to your trainer’s unavailability.</p>
            <table style='border-collapse: collapse; margin: 15px 0;'>
                <tr><td><strong>Trainer:</strong></td><td>" . htmlspecialchars($trainer_name) . "</td></tr>
                <tr><td><strong>Date:</strong></td><td>" . date('M d, Y', strtotime($date)) . "</td></tr>
                <tr><td><strong>Session:</strong></td><td>" . htmlspecialchars($session_time) . "</td></tr>
                <tr><td><strong>Class Type:</strong></td><td>" . htmlspecialchars($class_type) . "</td></tr>
            </table>";

        if (!empty($reason)) {
            $html .= "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>";
        }

        $html .= "<p>We apologize for any inconvenience this may cause. You may rebook another available session from your member dashboard.</p>";
        $html .= "<p>Thank you for your understanding,<br><strong>Fit & Brawl Gym Team</strong></p>";

        // Apply shared template
        applyEmailTemplate($mail, $html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Failed to send booking cancellation email to $email: " . $e->getMessage());
        return false;
    }
}
