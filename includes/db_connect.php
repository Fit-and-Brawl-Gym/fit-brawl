<?php
// Database connection file for Fit & Brawl Gym

$host = "localhost";
$user = "root";
$pass = "";
$db   = "fit_and_brawl_gym";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>
