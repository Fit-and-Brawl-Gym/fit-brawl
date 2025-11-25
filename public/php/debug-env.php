<?php
/**
 * Environment Variable Debug Page
 * ONLY USE IN DEVELOPMENT - REMOVE IN PRODUCTION
 */

// Security check
if (isset($_GET['key']) && $_GET['key'] === 'debug123') {
    header('Content-Type: application/json');
    
    $env = [
        'RESEND_API_KEY' => getenv('RESEND_API_KEY') ? 'SET (length: ' . strlen(getenv('RESEND_API_KEY')) . ')' : 'NOT SET',
        'EMAIL_FROM' => getenv('EMAIL_FROM') ?: 'NOT SET',
        'EMAIL_HOST' => getenv('EMAIL_HOST') ?: 'NOT SET',
        'EMAIL_PORT' => getenv('EMAIL_PORT') ?: 'NOT SET',
        'EMAIL_USER' => getenv('EMAIL_USER') ? 'SET' : 'NOT SET',
        'EMAIL_PASS' => getenv('EMAIL_PASS') ? 'SET' : 'NOT SET',
        'APP_ENV' => getenv('APP_ENV') ?: 'NOT SET',
        'ENVIRONMENT' => defined('ENVIRONMENT') ? ENVIRONMENT : 'NOT DEFINED',
        'DB_HOST' => getenv('DB_HOST') ? 'SET' : 'NOT SET',
        'PHP_VERSION' => phpversion(),
        'ENV_FILE_EXISTS' => file_exists(__DIR__ . '/../../.env') ? 'YES' : 'NO',
        'USING_GETENV' => function_exists('getenv') ? 'YES' : 'NO',
        'USING_ENV_LOADER' => class_exists('EnvLoader') ? 'YES' : 'NO',
    ];
    
    // Check if $_ENV works
    $env['_ENV_RESEND'] = isset($_ENV['RESEND_API_KEY']) ? 'SET in $_ENV' : 'NOT in $_ENV';
    $env['_SERVER_RESEND'] = isset($_SERVER['RESEND_API_KEY']) ? 'SET in $_SERVER' : 'NOT in $_SERVER';
    
    echo json_encode($env, JSON_PRETTY_PRINT);
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
}
