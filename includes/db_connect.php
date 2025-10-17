<?php
// Database connection file for Fit & Brawl Gym

include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');
$port = getenv('DB_PORT');

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>
