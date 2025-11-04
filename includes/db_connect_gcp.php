<?php
/**
 * Database Connection Helper for Google Cloud Platform
 * Automatically detects environment (local vs GCP) and configures connection accordingly
 */

// Load environment variables
include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

// Detect if running on Google Cloud
$isGCP = isset($_SERVER['GAE_ENV']) || isset($_SERVER['GAE_VERSION']);

if ($isGCP) {
    // Google Cloud Platform configuration
    // Use Unix socket for Cloud SQL connection (more secure and reliable)
    $connectionName = getenv('DB_HOST'); // Should be /cloudsql/PROJECT:REGION:INSTANCE
    $dbName = getenv('DB_NAME');
    $dbUser = getenv('DB_USER');
    $dbPassword = getenv('DB_PASS');

    // Connect via Unix socket (no port needed)
    $conn = new mysqli(null, $dbUser, $dbPassword, $dbName, null, $connectionName);

    if ($conn->connect_error) {
        error_log("Cloud SQL connection failed: " . $conn->connect_error);
        die("Database connection failed. Please check the logs.");
    }
} else {
    // Local development configuration
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db   = getenv('DB_NAME') ?: 'fit_and_brawl_gym';
    $port = getenv('DB_PORT') ?: 3306;

    $conn = new mysqli($host, $user, $pass, $db, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Set character set
$conn->set_charset("utf8mb4");

// Optional: Set timezone
$conn->query("SET time_zone = '+00:00'");
?>
