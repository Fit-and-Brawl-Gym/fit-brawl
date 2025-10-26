<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\get_feedback.php
session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../../../includes/db_connect.php';

// Check if is_visible column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM feedback LIKE 'is_visible'");
$hasVisibleColumn = $checkColumn->num_rows > 0;

// Build SQL - get ALL feedback for admin (including hidden)
if ($hasVisibleColumn) {
    $sql = "SELECT id, user_id, username, email, avatar, message, date, is_visible FROM feedback ORDER BY date DESC";
} else {
    // If column doesn't exist, create it
    $conn->query("ALTER TABLE feedback ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER message");
    $sql = "SELECT id, user_id, username, email, avatar, message, date, is_visible FROM feedback ORDER BY date DESC";
}

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

$feedbacks = [];
while ($row = $result->fetch_assoc()) {
    $feedbacks[] = $row;
}

echo json_encode($feedbacks);
$conn->close();