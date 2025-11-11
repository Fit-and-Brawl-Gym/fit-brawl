<?php
/**
 * Application Configuration
 * - Derives URL base paths from environment with safe defaults
 * - Keeps backward compatibility with existing path constants
 */

// Load env vars (if available)
require_once __DIR__ . '/env_loader.php';
// Try project-root .env
loadEnv(__DIR__ . '/../.env');

// Determine environment automatically based on server characteristics
// Priority: 1) APP_ENV from .env, 2) Auto-detect from SERVER_NAME
$appEnv = getenv('APP_ENV');

if (!$appEnv) {
    // Auto-detect environment
    $serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // If it's localhost, 127.0.0.1, or contains 'local', it's development
    if (in_array($serverName, ['localhost', '127.0.0.1', '::1']) || 
        strpos($serverName, 'local') !== false ||
        strpos($serverName, '.local') !== false) {
        $appEnv = 'development';
    } else {
        $appEnv = 'production';
    }
}

// Determine BASE_PATH (URL prefix where the repo root is served)
// Examples:
// - Local XAMPP:      /fit-brawl
// - Production root:  /
$defaultBase = $appEnv === 'production' ? '/' : '/fit-brawl';
$configuredBase = getenv('BASE_PATH') ?: $defaultBase;

// Normalize to leading slash, single trailing slash
$configuredBase = '/' . ltrim($configuredBase, '/');
if ($configuredBase !== '/') {
    $configuredBase = rtrim($configuredBase, '/') . '/';
}

define('BASE_PATH', $configuredBase);         // e.g. "/fit-brawl/" or "/"

// Public assets (CSS/JS) are served from document root in production
// For production (DocumentRoot = /public): PUBLIC_PATH = '' (empty, since DocumentRoot is already /public)
// For localhost (no DocumentRoot): PUBLIC_PATH = /fit-brawl/public
define('PUBLIC_PATH',  $appEnv === 'production' ? '' : (rtrim(BASE_PATH, '/') . '/public'));

// Images and uploads are at repo root level (one level up from public)
// For production: /images, /uploads (Apache serves from repo root via Alias or symlink)
// For localhost: /fit-brawl/images, /fit-brawl/uploads
define('IMAGES_PATH',  rtrim(BASE_PATH, '/') . '/images');
define('UPLOADS_PATH', rtrim(BASE_PATH, '/') . '/uploads');

// Expose a simple ENVIRONMENT flag
define('ENVIRONMENT', $appEnv);

// Set timezone to Philippine Time (UTC+8)
date_default_timezone_set('Asia/Manila');

// Helper to build URL paths
function getPath($path) {
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}
