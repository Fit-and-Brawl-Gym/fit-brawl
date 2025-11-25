<?php
/**
 * Email Configuration Test
 * Tests email settings and provider
 * Access: /php/test-email-config.php?key=test123
 */

header('Content-Type: application/json');

// Don't expose in production without auth
$isAdmin = isset($_GET['key']) && $_GET['key'] === 'test123';

if (!$isAdmin) {
    echo json_encode(['error' => 'Unauthorized. Add ?key=test123 to access']);
    exit;
}

require_once __DIR__ . '/../../includes/config.php';
include_once __DIR__ . '/../../includes/env_loader.php';
loadEnv(__DIR__ . '/../../.env');
require_once __DIR__ . '/../../includes/email_service.php';

$config = [
    'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown',
    'email_provider' => EmailService::getProvider(),
    'resend_api_key' => getenv('RESEND_API_KEY') ? '***set***' : '(not set)',
    'sendgrid_api_key' => getenv('SENDGRID_API_KEY') ? '***set***' : '(not set)',
    'email_host' => getenv('EMAIL_HOST') ?: '(not set)',
    'email_port' => getenv('EMAIL_PORT') ?: '(not set)',
    'email_user' => getenv('EMAIL_USER') ? substr(getenv('EMAIL_USER'), 0, 5) . '***' : '(not set)',
    'email_pass' => getenv('EMAIL_PASS') ? '***set***' : '(not set)',
    'app_env' => getenv('APP_ENV') ?: '(not set)',
];

// Test email send if requested
if (isset($_GET['test']) && isset($_GET['email'])) {
    $testEmail = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
    if ($testEmail) {
        $html = "<h2>Test Email</h2><p>This is a test email from Fit & Brawl.</p><p>If you received this, email is working!</p>";
        $result = EmailService::send($testEmail, 'Test Email - Fit & Brawl', $html);
        $config['test_result'] = $result ? 'SUCCESS - Email sent!' : 'FAILED - Check logs';
    } else {
        $config['test_result'] = 'Invalid email address';
    }
}

$config['usage'] = 'Add &test=1&email=your@email.com to send a test email';

echo json_encode($config, JSON_PRETTY_PRINT);
