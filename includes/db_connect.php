<?php
// Database connection file for Fit & Brawl Gym

$host = "localhost";  
$user = "root";         
$pass = "";             
$db   = "fit_and_brawl_gym"; 
$port = 3306;       
     
// Create connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Optional: set charset to UTF-8 (avoids weird symbols with special characters)
$conn->set_charset("utf8");

// Debug message (can be commented out later)
// echo "✅ Database connection successful!";
?>
