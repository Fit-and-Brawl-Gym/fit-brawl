<?php
/**
 * Email Configuration Test
 * Tests SMTP settings without sending an actual email
 * Access: /php/test-email-config.php
 */

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// Don't expose in production without auth
$isAdmin = isset($_GET['key']) && $_GET['key'] === 'test123';

if (!$isAdmin) {
    echo json_encode(['error' => 'Unauthorized. Add ?key=test123 to access']);
    exit;
}

require_once __DIR__ . '/../../includes/config.php';
include_once __DIR__ . '/../../includes/env_loader.php';
loadEnv(__DIR__ . '/../../.env');

$config = [
    'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown',
    'email_host' => getenv('EMAIL_HOST') ?: '(not set)',
    'email_port' => getenv('EMAIL_PORT') ?: '(not set)',
    'email_user' => getenv('EMAIL_USER') ? substr(getenv('EMAIL_USER'), 0, 5) . '***' : '(not set)',
    'email_pass' => getenv('EMAIL_PASS') ? '***set***' : '(not set)',
    'app_env' => getenv('APP_ENV') ?: '(not set)',
    'db_host' => getenv('DB_HOST') ? 'set' : '(not set)',
];

// Test SMTP connection (without sending)
try {
    require_once __DIR__ . '/../../includes/mail_config.php';
    
    $mail = new PHPMailer(true);
    configureMailerSMTP($mail);
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    
    // Capture debug output
    ob_start();
    $connected = $mail->smtpConnect();
    $debug = ob_get_clean();
    
    if ($connected) {
        $mail->smtpClose();
        $config['smtp_test'] = 'SUCCESS - Connected to SMTP server';
    } else {
        $config['smtp_test'] = 'FAILED - Could not connect';
    }
    $config['smtp_debug'] = $debug;
    
} catch (Exception $e) {
    $config['smtp_test'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($config, JSON_PRETTY_PRINT);
