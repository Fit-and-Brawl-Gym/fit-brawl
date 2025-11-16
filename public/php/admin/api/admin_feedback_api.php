<?php
require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/../../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../../includes/api_rate_limiter.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../../includes/activity_logger.php';

// Initialize activity logger
ActivityLogger::init($conn);

ApiSecurityMiddleware::setSecurityHeaders();

// Only admins can access this API
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    exit;
}

// Rate limiting for admin APIs - 20 requests per minute per admin
$adminId = $_SESSION['user_id'] ?? 'unknown';
$rateCheck = ApiRateLimiter::checkAndIncrement($conn, 'admin_api:' . $adminId, 20, 60);
if ($rateCheck['blocked']) {
    http_response_code(429);
    header('X-RateLimit-Limit: 20');
    header('X-RateLimit-Remaining: 0');
    header('Retry-After: ' . $rateCheck['retry_after']);
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
    exit;
}
header('X-RateLimit-Limit: 20');
header('X-RateLimit-Remaining: ' . $rateCheck['remaining']);
header('X-RateLimit-Reset: ' . (time() + $rateCheck['retry_after']));

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetch':
        // Use prepared statement for security
        $stmt = $conn->prepare("SELECT f.id, u.username, f.message, f.date, f.is_visible
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                ORDER BY f.date DESC");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            ApiSecurityMiddleware::sendJsonResponse(['success' => true, 'data' => $data], 200);
        } else {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Database error'], 500);
        }
        break;

    case 'delete':
        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFProtection::validateToken($csrfToken)) {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'CSRF token validation failed'], 403);
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id > 0) {
            // Get feedback details before deletion for logging
            $infoStmt = $conn->prepare("SELECT u.username, f.message FROM feedback f LEFT JOIN users u ON f.user_id = u.id WHERE f.id = ?");
            $infoStmt->bind_param('i', $id);
            $infoStmt->execute();
            $feedbackInfo = $infoStmt->get_result()->fetch_assoc();
            $infoStmt->close();

            $stmt = $conn->prepare("DELETE FROM feedback WHERE id=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                // Log admin action
                if ($feedbackInfo) {
                    ActivityLogger::log(
                        'feedback_delete',
                        $feedbackInfo['username'] ?? 'Unknown',
                        $id,
                        "Deleted feedback from {$feedbackInfo['username']}: " . substr($feedbackInfo['message'] ?? '', 0, 100)
                    );
                }
                ApiSecurityMiddleware::sendJsonResponse(['success' => true, 'message' => 'Feedback deleted'], 200);
            } else {
                ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Delete failed'], 500);
            }
            $stmt->close();
        } else {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
        }
        break;

    default:
        ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}
