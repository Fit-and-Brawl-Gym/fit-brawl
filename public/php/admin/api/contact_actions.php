<?php
session_start();
header('Content-Type: application/json');

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../../../includes/db_connect.php';

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

    // Log admin action
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['username'] ?? 'Admin';
    $log_action = ucfirst(str_replace('_', ' ', $action));
    $details = "Contact ID: $id - Action: $log_action";

    $log_sql = "INSERT INTO admin_logs (admin_id, admin_name, action_type, target_id, details) 
                VALUES (?, ?, 'contact_management', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param("isis", $admin_id, $admin_name, $id, $details);
    $log_stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => ucfirst($action) . ' completed successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
