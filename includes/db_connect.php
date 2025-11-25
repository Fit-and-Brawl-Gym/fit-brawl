<?php
// Database connection file for Fit & Brawl Gym
// Optimized for performance with persistent connections

include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

// Get database configuration from environment
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'fit_and_brawl_gym';
$port = getenv('DB_PORT') ?: 3306;

// Use persistent connection in production for faster subsequent requests
// Prefix host with 'p:' to enable persistent connections
$isProduction = (getenv('APP_ENV') === 'production') ||
                (defined('ENVIRONMENT') && ENVIRONMENT === 'production');

if ($isProduction) {
    // Persistent connection for production (reuses connection across requests)
    $host = 'p:' . $host;
}

// Create connection with error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);

    // Set character set to support UTF-8 (emojis, international characters)
    $conn->set_charset("utf8mb4");

    // Set MySQL timezone to Philippine Time (UTC+8)
    $conn->query("SET time_zone = '+08:00'");

    // Optimize MySQL session settings for performance
    $conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

} catch (mysqli_sql_exception $e) {
    if (php_sapi_name() === 'cli') {
        // CLI: print error and exit
        fwrite(STDERR, "DB Connection failed: " . $e->getMessage() . "\n");
        exit(1);
    } else {
        // API: return JSON error and exit
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        error_log("DB Connection failed: " . $e->getMessage());
        exit;
    }
}
?>
