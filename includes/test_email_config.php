<?php
/**
 * Email Configuration Test Script
 * Run this to check if email credentials are properly configured
 */

require_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/env');

echo "<h2>Email Configuration Check</h2>";
echo "<pre>";

$configs = [
    'EMAIL_HOST' => getenv('EMAIL_HOST'),
    'EMAIL_USER' => getenv('EMAIL_USER'),
    'EMAIL_PASS' => getenv('EMAIL_PASS') ? '***SET***' : 'NOT SET',
    'EMAIL_PORT' => getenv('EMAIL_PORT')
];

foreach ($configs as $key => $value) {
    $status = $value ? '✓' : '✗';
    echo "$status $key: " . ($value ?: 'NOT SET') . "\n";
}

echo "\n";

if (!getenv('EMAIL_HOST') || !getenv('EMAIL_USER') || !getenv('EMAIL_PASS')) {
    echo "⚠️ Email credentials are missing!\n\n";
    echo "To fix this, create a file at: includes/env\n";
    echo "With the following content:\n\n";
    echo "EMAIL_HOST=smtp.gmail.com\n";
    echo "EMAIL_USER=your-email@gmail.com\n";
    echo "EMAIL_PASS=your-app-password\n";
    echo "EMAIL_PORT=587\n";
    echo "APP_URL=http://localhost/fit-brawl\n";
} else {
    echo "✓ All email credentials are configured!\n";
}

echo "</pre>";
?>
