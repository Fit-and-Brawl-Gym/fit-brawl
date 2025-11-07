<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\get_member_history.php
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

$userId = (int) ($_GET['user_id'] ?? 0);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
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

    echo json_encode([
        'success' => true,
        'history' => $history
    ]);

    $stmt->close();

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
