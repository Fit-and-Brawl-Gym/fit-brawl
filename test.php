<?php
// Simple test to verify PHP is working on App Engine

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Page</title></head><body>";
echo "<h1>✅ PHP is Working!</h1>";
echo "<h2>Server Info:</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li><strong>Script Filename:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "<li><strong>Current Directory:</strong> " . getcwd() . "</li>";
echo "</ul>";

// Test file existence
echo "<h2>File Checks:</h2>";
echo "<ul>";
$files_to_check = [
    'health.php',
    'public/php/index.php',
    'includes/session_manager.php',
    'includes/db_connect.php',
    'vendor/autoload.php',
];

foreach ($files_to_check as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<li><strong>$file:</strong> $status</li>";
}
echo "</ul>";

// Test database connection (without executing)
echo "<h2>Environment Variables:</h2>";
echo "<ul>";
$env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_URL', 'GCP_PROJECT_ID'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    $display = $value ? '✅ SET' : '❌ NOT SET';
    echo "<li><strong>$var:</strong> $display</li>";
}
echo "</ul>";

echo "</body></html>";
