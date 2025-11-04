<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/env');

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
        $mail->Body = "Your OTP for password reset is: <b>$otp</b><br>This OTP will expire in 5 minutes.";

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
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to FitXBrawl - Your Trainer Account Credentials';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #002f3f;'>Welcome to FitXBrawl, " . htmlspecialchars($name) . "!</h2>
                <p>Your trainer account has been created successfully. Below are your login credentials:</p>
                
                <div style='background-color: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 10px 0;'><strong>Username:</strong> " . htmlspecialchars($username) . "</p>
                    <p style='margin: 10px 0;'><strong>Default Password:</strong> " . htmlspecialchars($password) . "</p>
                </div>
                
                <div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #d5ba2b; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404;'><strong>⚠️ Important Security Notice:</strong></p>
                    <p style='margin: 10px 0 0 0; color: #856404;'>Please change your password immediately after your first login for security purposes. You will see a notification on your profile page until you change your password.</p>
                </div>
                
                <p>You can login at: <a href='" . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . "/fit-brawl/public/php/login.php'>Login Page</a></p>
                
                <p style='margin-top: 30px; color: #666; font-size: 12px;'>If you did not expect this email, please contact the gym administrator immediately.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendContactReply($to, $subject, $htmlBody)
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
        $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl Gym');
        $mail->addAddress($to);
        $mail->addReplyTo(getenv('EMAIL_USER'), 'Fit & Brawl Gym');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $e->getMessage());
        return false;
    }
}