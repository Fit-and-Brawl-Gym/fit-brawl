<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\get_member_history.php
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../../includes/input_validator.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Require admin authentication
$user = ApiSecurityMiddleware::requireAuth(['role' => 'admin']);
if (!$user) {
    exit; // Already sent response
}

// Admins can query freely; no rate limiting
$adminId = $user['user_id'];

// Validate and sanitize input
$validation = ApiSecurityMiddleware::validateInput([
    'user_id' => [
        'type' => 'string',
        'required' => true,
        'max_length' => 50
    ]
], $_GET);

if (!$validation['valid']) {
    $errors = implode(', ', $validation['errors']);
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Validation failed: ' . $errors
    ], 400);
}

$userIdParam = $validation['data']['user_id'];

// Handle both integer and string user IDs
if (is_numeric($userIdParam)) {
    $userId = (int) $userIdParam;
} else if (preg_match('/MBR-\d{2}-(\d+)/', $userIdParam, $matches)) {
    // Extract numeric ID from format like "MBR-25-0005"
    $userId = (int) $matches[1];
} else {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Invalid user ID format'
    ], 400);
}

if (!$userId || $userId <= 0) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Invalid user ID'
    ], 400);
}

try {
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'user_memberships'");
    if ($tableCheck->num_rows == 0) {
        throw new Exception('user_memberships table does not exist');
    }
    // Get available columns
    $columns = $conn->query("SHOW COLUMNS FROM user_memberships");
    $availableColumns = [];
    while ($col = $columns->fetch_assoc()) {
        $availableColumns[] = $col['Field'];
    }

    // Build SELECT clause based on available columns
    $selectFields = ['id', 'plan_name'];


    // Build SELECT clause based on available columns
    $selectFields = ['id', 'plan_name'];

    $optionalColumns = [
        'duration',
        'total_payment',
        'start_date',
        'end_date',
        'date_submitted',
        'membership_status',
        'request_status'
    ];


    foreach ($optionalColumns as $col) {
        if (in_array($col, $availableColumns)) {
            $selectFields[] = $col;
        } else {
            $selectFields[] = "NULL as $col";
        }
    }

    $selectClause = implode(', ', $selectFields);

    // Get all membership history for this user
    $sql = "SELECT $selectClause
            FROM user_memberships
            WHERE user_id = ?
            ORDER BY " . (in_array('date_submitted', $availableColumns) ? 'date_submitted' : 'id') . " DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $userId);

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'history' => $history
    ], 200);

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in get_member_history.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching member history. Please try again.'
    ], 500);
}

if (isset($conn)) {
    $conn->close();
}
