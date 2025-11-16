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
// Handle anonymous feedback (user_id can be NULL)
if ($hasVisibleColumn) {
    $sql = "SELECT
                f.id,
                f.user_id,
                COALESCE(f.username, 'Anonymous') as username,
                COALESCE(f.email, '') as email,
                COALESCE(f.avatar, 'default-avatar.png') as avatar,
                f.message,
                f.date,
                f.is_visible
            FROM feedback f
            ORDER BY f.date DESC";
} else {
    // If column doesn't exist, create it
    $conn->query("ALTER TABLE feedback ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER message");
    $sql = "SELECT
                f.id,
                f.user_id,
                COALESCE(f.username, 'Anonymous') as username,
                COALESCE(f.email, '') as email,
                COALESCE(f.avatar, 'default-avatar.png') as avatar,
                f.message,
                f.date,
                f.is_visible
            FROM feedback f
            ORDER BY f.date DESC";
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
