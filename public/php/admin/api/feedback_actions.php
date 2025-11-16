<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\feedback_actions.php
session_start();

require_once '../../../../includes/db_connect.php';
require_once '../../../../includes/csrf_protection.php';
require_once '../../../../includes/api_rate_limiter.php';
require_once '../../../../includes/api_security_middleware.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Check admin authentication
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$id = (int) ($input['id'] ?? 0);

// Validate CSRF token
$csrfToken = $input['csrf_token'] ?? '';
if (!CSRFProtection::validateToken($csrfToken)) {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'CSRF token validation failed'], 403);
    exit;
}

// Log the request for debugging
error_log("Feedback action: $action, ID: $id, Input: " . json_encode($input));

if (!$id) {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
    exit;
}

// Check if is_visible column exists, if not create it
$checkColumn = $conn->prepare("SHOW COLUMNS FROM feedback LIKE 'is_visible'");
if ($checkColumn) {
    $checkColumn->execute();
    $checkResult = $checkColumn->get_result();
    if ($checkResult->num_rows == 0) {
        // Safe to use query for DDL statements (no user input)
        $conn->query("ALTER TABLE feedback ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER message");
    }
    $checkColumn->close();
}

// Check what columns exist in feedback table (safe - no user input)
$columns = $conn->query("SHOW COLUMNS FROM feedback");
$columnNames = [];
if ($columns) {
    while ($col = $columns->fetch_assoc()) {
        $columnNames[] = $col['Field'];
    }
    error_log("Feedback table columns: " . json_encode($columnNames));
}

// Determine primary key column
$primaryKey = in_array('id', $columnNames) ? 'id' : 'user_id';

switch ($action) {
    case 'toggle_visibility':
        $isVisible = (int) ($input['is_visible'] ?? 1);

        // First check if the record exists
        $checkStmt = $conn->prepare("SELECT $primaryKey FROM feedback WHERE $primaryKey = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => "No feedback found with $primaryKey = $id"], 404);
            $checkStmt->close();
            break;
        }
        $checkStmt->close();

        // Now update
        $stmt = $conn->prepare("UPDATE feedback SET is_visible = ? WHERE $primaryKey = ?");

        if (!$stmt) {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);
            break;
        }

        $stmt->bind_param("ii", $isVisible, $id);

        if ($stmt->execute()) {
            // Success even if no rows changed (already had that visibility value)
            ApiSecurityMiddleware::sendJsonResponse(['success' => true, 'message' => 'Visibility updated'], 200);
        } else {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Execute failed: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'delete':
        $stmt = $conn->prepare("DELETE FROM feedback WHERE $primaryKey = ?");

        if (!$stmt) {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);
            break;
        }

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                ApiSecurityMiddleware::sendJsonResponse(['success' => true, 'message' => 'Feedback deleted'], 200);
            } else {
                ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => "No rows deleted for $primaryKey = $id"], 404);
            }
        } else {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Execute failed: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    default:
        ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

$conn->close();
