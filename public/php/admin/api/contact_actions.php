<?php
session_start();

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../../../includes/db_connect.php';
require_once '../../../../includes/csrf_protection.php';
require_once '../../../../includes/api_security_middleware.php';
require_once '../../../../includes/activity_logger.php';

// Initialize activity logger
ActivityLogger::init($conn);

ApiSecurityMiddleware::setSecurityHeaders();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!CSRFProtection::validateToken($csrfToken)) {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid or missing CSRF token'], 419);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['action']) || !isset($input['id'])) {
        throw new Exception('Invalid request parameters');
    }

    $action = $input['action'];
    $id = intval($input['id']);

    // Check if status column exists
    $columns = $conn->query("SHOW COLUMNS FROM contact");
    $hasStatus = false;
    $hasArchived = false;
    while ($col = $columns->fetch_assoc()) {
        if ($col['Field'] === 'status')
            $hasStatus = true;
        if ($col['Field'] === 'archived')
            $hasArchived = true;
    }

    if (!$hasStatus && in_array($action, ['mark_read', 'mark_unread'])) {
        throw new Exception('Status column not found. Please run the database migration first.');
    }

    switch ($action) {
        case 'mark_read':
            $sql = "UPDATE contact SET status = 'read' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            break;

        case 'mark_unread':
            $sql = "UPDATE contact SET status = 'unread' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            break;

        case 'archive':
            if ($hasArchived) {
                $sql = "UPDATE contact SET archived = 1 WHERE id = ?";
            } else {
                // Fallback to soft delete
                $sql = "UPDATE contact SET deleted_at = NOW() WHERE id = ?";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            break;

        case 'delete':
            // Soft delete
            $sql = "UPDATE contact SET deleted_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            break;

        default:
            throw new Exception('Invalid action');
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }

    // Get contact details for logging
    $infoStmt = $conn->prepare("SELECT first_name, last_name, email FROM contact WHERE id = ?");
    $infoStmt->bind_param("i", $id);
    $infoStmt->execute();
    $contactInfo = $infoStmt->get_result()->fetch_assoc();
    $infoStmt->close();

    // Log admin action using ActivityLogger
    if ($contactInfo) {
        $logAction = 'contact_' . $action;
        $fullName = trim(($contactInfo['first_name'] ?? '') . ' ' . ($contactInfo['last_name'] ?? ''));
        $logDetails = ucfirst(str_replace('_', ' ', $action)) . " contact from {$fullName} ({$contactInfo['email']})";

        ActivityLogger::log(
            $logAction,
            $fullName ?: 'Unknown',
            $id,
            $logDetails
        );
    }

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'message' => ucfirst($action) . ' completed successfully'
    ], 200);

} catch (Exception $e) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}

if (isset($conn)) {
    $conn->close();
}
