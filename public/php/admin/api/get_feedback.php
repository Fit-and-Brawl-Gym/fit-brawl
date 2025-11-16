<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\get_feedback.php
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require admin authentication
$user = ApiSecurityMiddleware::requireAuth(['role' => 'admin']);
if (!$user) {
    exit; // Already sent response
}

// Rate limiting for admin read endpoints - 30 requests per minute per admin
$adminId = $user['user_id'];
ApiSecurityMiddleware::applyRateLimit($conn, 'admin_get_feedback:' . $adminId, 30, 60);

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

try {
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'feedbacks' => $feedbacks,
        'total' => count($feedbacks)
    ], 200);
} catch (Exception $e) {
    error_log("Error in get_feedback.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching feedback. Please try again.'
    ], 500);
}

$conn->close();
