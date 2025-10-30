<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\debug_feedback.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../../../includes/db_connect.php';

$debug = [];

// 1. Check table structure
$columns = $conn->query("SHOW COLUMNS FROM feedback");
$debug['columns'] = [];
while ($col = $columns->fetch_assoc()) {
    $debug['columns'][] = $col;
}

// 2. Get sample feedback data
$result = $conn->query("SELECT * FROM feedback LIMIT 1");
if ($result && $result->num_rows > 0) {
    $debug['sample_feedback'] = $result->fetch_assoc();
} else {
    $debug['sample_feedback'] = null;
}

// 3. Count total feedback
$countResult = $conn->query("SELECT COUNT(*) as total FROM feedback");
$debug['total_feedback'] = $countResult->fetch_assoc()['total'];

// 4. Check for id column specifically
$hasId = $conn->query("SHOW COLUMNS FROM feedback LIKE 'id'")->num_rows > 0;
$hasUserId = $conn->query("SHOW COLUMNS FROM feedback LIKE 'user_id'")->num_rows > 0;
$hasIsVisible = $conn->query("SHOW COLUMNS FROM feedback LIKE 'is_visible'")->num_rows > 0;

$debug['has_id_column'] = $hasId;
$debug['has_user_id_column'] = $hasUserId;
$debug['has_is_visible_column'] = $hasIsVisible;

echo json_encode($debug, JSON_PRETTY_PRINT);