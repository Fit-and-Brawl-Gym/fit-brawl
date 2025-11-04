<?php
/**
 * Environment Configuration
 * Detects whether running on localhost or production and sets appropriate paths
 */

// Detect environment
// GAE_ENV is set by Google App Engine
$isProduction = isset($_SERVER['GAE_ENV']) || isset($_SERVER['GAE_APPLICATION']);

// Set base paths based on environment
if ($isProduction) {
    // Production (Google Cloud App Engine)
    define('BASE_PATH', '/');
    define('PUBLIC_PATH', '/public');
    define('IMAGES_PATH', '/images');
    define('UPLOADS_PATH', '/uploads');
    define('ENVIRONMENT', 'production');
} else {
    // Localhost (XAMPP - assumes project is in /fit-brawl/)
    define('BASE_PATH', '/fit-brawl/');
    define('PUBLIC_PATH', '/fit-brawl/public');
    define('IMAGES_PATH', '/fit-brawl/images');
    define('UPLOADS_PATH', '/fit-brawl/uploads');
    define('ENVIRONMENT', 'localhost');
}

// Helper function to get full URL path
function getPath($path) {
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}
