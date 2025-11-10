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

// Determine environment (default: production)
$appEnv = getenv('APP_ENV') ?: 'production';

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

// Public assets (CSS/JS) live under repo/public even when served from repo root.
// URLs reference "/public/..." under the BASE_PATH.
define('PUBLIC_PATH',  rtrim(BASE_PATH, '/') . '/public');      // e.g. "/fit-brawl/public" or "/public"

// Images and uploads are served directly from repo/images and repo/uploads
define('IMAGES_PATH',  rtrim(BASE_PATH, '/') . '/images');      // e.g. "/fit-brawl/images" or "/images"
define('UPLOADS_PATH', rtrim(BASE_PATH, '/') . '/uploads');     // e.g. "/fit-brawl/uploads" or "/uploads"

// Expose a simple ENVIRONMENT flag
define('ENVIRONMENT', $appEnv);

// Helper to build URL paths
function getPath($path) {
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}
