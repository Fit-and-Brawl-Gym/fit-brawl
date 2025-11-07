<?php
/**
 * Environment Configuration
 * Sets appropriate paths for local development
 */

// Set base paths for localhost (XAMPP - project in /fit-brawl/)
define('BASE_PATH', '/fit-brawl/');
define('PUBLIC_PATH', '/fit-brawl/public');
define('IMAGES_PATH', '/fit-brawl/images');
define('UPLOADS_PATH', '/fit-brawl/uploads');
define('ENVIRONMENT', 'localhost');

// Helper function to get full URL path
function getPath($path) {
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}
