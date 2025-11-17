<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require authentication
$user = ApiSecurityMiddleware::requireAuth();
if (!$user) {
    exit; // Already sent response
}

$user_id = $user['user_id'];

// Rate limiting - 30 requests per minute (read endpoint)
ApiSecurityMiddleware::applyRateLimit($conn, 'get_membership:' . $user_id, 30, 60);

// Get active membership
$query = "SELECT um.*, m.plan_name, m.price, m.duration
          FROM user_memberships um
          JOIN memberships m ON um.membership_id = m.id
          WHERE um.user_id = ? AND um.status = 'active' AND um.end_date >= CURDATE()
          ORDER BY um.end_date DESC
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'membership' => [
            'plan_name' => $row['plan_name'],
            'end_date' => $row['end_date'],
            'billing_type' => $row['billing_type'],
            'price' => $row['price']
        ]
    ], 200);
} else {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'No active membership found'
    ], 404);
}

$stmt->close();
$conn->close();
?>
