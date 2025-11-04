<?php
// Front controller for App Engine
// App Engine routes all PHP requests through here via 'script: auto'

// Get the requested path
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove query string for routing
$path = rtrim($path, '/');

// Determine which file to serve
$file_to_serve = null;
$change_dir = false;

// Root - serve homepage
if ($path === '' || $path === '/' || $path === '/index.php') {
    $file_to_serve = __DIR__ . '/public/php/index.php';
    $change_dir = true;
}
// Health check and test files in root
elseif ($path === '/health.php') {
    $file_to_serve = __DIR__ . '/health.php';
}
elseif ($path === '/test.php') {
    $file_to_serve = __DIR__ . '/test.php';
}
// Admin routes (e.g., /admin, /admin/admin.php, /admin/equipment.php)
elseif (preg_match('#^/admin(/.*)?$#', $path)) {
    $admin_path = str_replace('/admin', '', $path);
    if ($admin_path === '' || $admin_path === '/') {
        $admin_path = '/admin.php';
    } elseif (!str_ends_with($admin_path, '.php')) {
        $admin_path .= '.php';
    }
    $file_to_serve = __DIR__ . '/public/php/admin' . $admin_path;
    $change_dir = true;
}
// Trainer routes (e.g., /trainer, /trainer/index.php, /trainer/schedule.php)
elseif (preg_match('#^/trainer(/.*)?$#', $path)) {
    $trainer_path = str_replace('/trainer', '', $path);
    if ($trainer_path === '' || $trainer_path === '/') {
        $trainer_path = '/index.php';
    } elseif (!str_ends_with($trainer_path, '.php')) {
        $trainer_path .= '.php';
    }
    $file_to_serve = __DIR__ . '/public/php/trainer' . $trainer_path;
    $change_dir = true;
}
// API routes (e.g., /api/..., /admin/api/...)
elseif (preg_match('#^/admin/api/(.+)$#', $path, $matches)) {
    // Admin API routes
    $api_file = $matches[1];
    if (!str_ends_with($api_file, '.php')) {
        $api_file .= '.php';
    }
    $file_to_serve = __DIR__ . '/public/php/admin/api/' . $api_file;
    $change_dir = true;
}
elseif (preg_match('#^/api/(.+)$#', $path, $matches)) {
    // Public API routes
    $api_file = $matches[1];
    if (!str_ends_with($api_file, '.php')) {
        $api_file .= '.php';
    }
    $file_to_serve = __DIR__ . '/public/php/api/' . $api_file;
    $change_dir = true;
}
// Public PHP files with full path (e.g., /public/php/login.php)
elseif (preg_match('#^/public/php/(.+\.php)$#', $path)) {
    $file_to_serve = __DIR__ . $path;
    $change_dir = true;
}
// Clean URLs for main pages (e.g., /login, /sign-up, /membership, etc.)
elseif (preg_match('#^/([a-z0-9\-_]+)(\.php)?$#i', $path, $matches)) {
    $page_name = $matches[1];

    // List of known pages in public/php directory
    $known_pages = [
        'login', 'sign-up', 'membership', 'equipment', 'products', 'contact',
        'feedback', 'reservations', 'logout', 'user_profile', 'update_profile',
        'change-password', 'forgot-password', 'verification', 'verify-email',
        'loggedin-index', 'transaction', 'transaction_service', 'transaction_nonmember',
        'receipt_render', 'receipt_service', 'receipt_fallback', 'receipt_nonmember',
        'membership-status', 'feedback-form', 'auth', 'check_session', 'extend_session',
        'resend-otp', 'trainer_api', 'get_daily_bookings', 'get_trainer_bookings'
    ];

    // Check if the file exists directly (for dynamic routes)
    $direct_file = __DIR__ . '/public/php/' . $page_name . '.php';
    if (file_exists($direct_file)) {
        $file_to_serve = $direct_file;
        $change_dir = true;
    } elseif (in_array($page_name, $known_pages)) {
        // Fallback to known pages list
        $file_to_serve = __DIR__ . '/public/php/' . $page_name . '.php';
        $change_dir = true;
    }
}
// Direct PHP file access if it exists
elseif (file_exists(__DIR__ . $path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
    $file_to_serve = __DIR__ . $path;
    $change_dir = true;
}

// Serve the file or return 404
if ($file_to_serve && file_exists($file_to_serve)) {
    // For files in subdirectories, change working directory so relative includes work
    if ($change_dir) {
        $file_dir = dirname($file_to_serve);
        chdir($file_dir);
    }

    // Include the file
    require $file_to_serve;
} else {
    // 404 Not Found
    http_response_code(404);
    echo '404 - Page Not Found: ' . htmlspecialchars($path);
}
