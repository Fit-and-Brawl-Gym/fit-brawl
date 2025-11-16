<?php
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Require admin authentication
$user = ApiSecurityMiddleware::requireAuth(['role' => 'admin']);
if (!$user) {
    exit; // Already sent response
}

// Rate limiting for admin read endpoints - 30 requests per minute per admin
$adminId = $user['user_id'];
ApiSecurityMiddleware::applyRateLimit($conn, 'admin_get_contacts:' . $adminId, 30, 60);

try {
    // Check if status column exists (might not have run migration yet)
    $columns = $conn->query("SHOW COLUMNS FROM contact");
    $hasStatus = false;
    while ($col = $columns->fetch_assoc()) {
        if ($col['Field'] === 'status') {
            $hasStatus = true;
            break;
        }
    }

    if ($hasStatus) {
        // Get non-archived contacts with status
        $sql = "SELECT id, first_name, last_name, email, phone_number, message, status, date_submitted
                FROM contact
                WHERE (archived = 0 OR archived IS NULL)
                AND deleted_at IS NULL
                ORDER BY
                    CASE WHEN status = 'unread' THEN 0 ELSE 1 END,
                    date_submitted DESC";
    } else {
        // Fallback if status column doesn't exist yet
        $sql = "SELECT id, first_name, last_name, email, phone_number, message,
                'unread' as status, date_submitted
                FROM contact
                ORDER BY date_submitted DESC";
    }

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'contacts' => $contacts,
        'total' => count($contacts),
        'has_status_column' => $hasStatus
    ], 200);

} catch (Exception $e) {
    error_log("Error in get_contacts.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching contacts. Please try again.'
    ], 500);
}

if (isset($conn)) {
    $conn->close();
}
