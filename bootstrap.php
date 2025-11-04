<?php
/**
 * Bootstrap file for App Engine
 * Sets up paths and autoloading before including application files
 */

// Define base paths
define('APP_ROOT', __DIR__);
define('PUBLIC_ROOT', APP_ROOT . '/public');
define('INCLUDES_ROOT', APP_ROOT . '/includes');

// Set include path for easier requires
set_include_path(get_include_path() . PATH_SEPARATOR . APP_ROOT . PATH_SEPARATOR . INCLUDES_ROOT);

// Load Composer autoloader if it exists
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

// Helper function to include files with proper paths
function app_include($file) {
    $possible_paths = [
        APP_ROOT . '/' . $file,
        INCLUDES_ROOT . '/' . $file,
        PUBLIC_ROOT . '/' . $file,
    ];

    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return require_once $path;
        }
    }

    throw new Exception("File not found: $file");
}
