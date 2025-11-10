<?php
// Database connection file for Fit & Brawl Gym
// Local development configuration

include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

// Local development - Use TCP connection
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'fit_and_brawl_gym';
$port = getenv('DB_PORT') ?: 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to support UTF-8 (emojis, international characters)
$conn->set_charset("utf8mb4");

// Optional: Set timezone to UTC for consistency
$conn->query("SET time_zone = '+00:00'");