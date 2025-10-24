<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\feedback_actions.php
session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../../../includes/db_connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$id = (int) ($input['id'] ?? 0);

// Log the request for debugging
error_log("Feedback action: $action, ID: $id, Input: " . json_encode($input));

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Check if is_visible column exists, if not create it
$checkColumn = $conn->query("SHOW COLUMNS FROM feedback LIKE 'is_visible'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE feedback ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER message");
}

// Check what columns exist in feedback table
$columns = $conn->query("SHOW COLUMNS FROM feedback");
$columnNames = [];
while ($col = $columns->fetch_assoc()) {
    $columnNames[] = $col['Field'];
}
error_log("Feedback table columns: " . json_encode($columnNames));

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
            echo json_encode(['success' => false, 'message' => "No feedback found with $primaryKey = $id"]);
            $checkStmt->close();
            break;
        }
        $checkStmt->close();

        // Now update
        $stmt = $conn->prepare("UPDATE feedback SET is_visible = ? WHERE $primaryKey = ?");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            break;
        }

        $stmt->bind_param("ii", $isVisible, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Visibility updated']);
            } else {
                echo json_encode(['success' => false, 'message' => "No rows updated for $primaryKey = $id"]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'delete':
        $stmt = $conn->prepare("DELETE FROM feedback WHERE $primaryKey = ?");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            break;
        }

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Feedback deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => "No rows deleted for $primaryKey = $id"]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();