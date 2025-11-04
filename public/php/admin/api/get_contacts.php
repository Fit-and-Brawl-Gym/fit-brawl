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

    echo json_encode([
        'success' => true,
        'contacts' => $contacts,
        'total' => count($contacts),
        'has_status_column' => $hasStatus
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
