<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\debug_feedback.php
// NOTE: This is a debug endpoint. Consider disabling in production.
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require admin authentication
$user = ApiSecurityMiddleware::requireAuth(['role' => 'admin']);
if (!$user) {
    exit; // Already sent response
}

// Admin-only endpoint; no rate limiting
$adminId = $user['user_id'];

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

ApiSecurityMiddleware::sendJsonResponse([
    'success' => true,
    'debug' => $debug
], 200);
