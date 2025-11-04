<?php
// Database connection file for Fit & Brawl Gym
// Auto-detects local vs Google Cloud Platform environment

include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

// Detect if running on Google Cloud Platform
$isGCP = isset($_SERVER['GAE_ENV']) || isset($_SERVER['GAE_VERSION']);

if ($isGCP) {
    // Google Cloud Platform - Use Unix socket connection
    $connectionName = getenv('DB_HOST'); // e.g., /cloudsql/PROJECT:REGION:INSTANCE
    $dbName = getenv('DB_NAME');
    $dbUser = getenv('DB_USER');
    $dbPassword = getenv('DB_PASS');

    // Connect via Unix socket (more secure, no port needed)
    $conn = new mysqli(null, $dbUser, $dbPassword, $dbName, null, $connectionName);

    if ($conn->connect_error) {
        error_log("Cloud SQL connection failed: " . $conn->connect_error);
        die("Database connection failed. Please check server logs.");
    }
} else {
    // Local development - Use TCP connection
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $db   = getenv('DB_NAME');
    $port = getenv('DB_PORT');

    $conn = new mysqli($host, $user, $pass, $db, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Set character set to support UTF-8 (emojis, international characters)
$conn->set_charset("utf8mb4");

// Optional: Set timezone to UTC for consistency
$conn->query("SET time_zone = '+00:00'");
?>
