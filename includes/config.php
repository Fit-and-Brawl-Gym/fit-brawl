<?php
/**
 * Environment Configuration
 * Sets appropriate paths for local development
 */

// Set base paths for localhost (XAMPP - project in /fit-brawl/)
define('BASE_PATH', '/fit-brawl/');
define('PUBLIC_PATH',  BASE_PATH . '/public');
define('IMAGES_PATH',  BASE_PATH . '/images');
define('UPLOADS_PATH', BASE_PATH .'/uploads');
define('ENVIRONMENT', 'localhost');

// Helper function to get full URL path
function getPath($path) {
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}
