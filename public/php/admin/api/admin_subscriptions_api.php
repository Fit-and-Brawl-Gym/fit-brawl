<?php
// ==============================================
// admin_subscriptions_api.php
// Handles admin actions for managing subscriptions
// ==============================================

// Allow JSON responses
header('Content-Type: application/json');

// Include initialization file
include_once('../../../../includes/init.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Access denied."]);
    exit();
}

// Database connection check
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // ===============================
        // Fetch all subscriptions
        // ===============================
        $status = isset($_GET['status']) ? $_GET['status'] : null;

        if ($status) {
            $stmt = $conn->prepare("
                SELECT s.*, u.username, m.plan_name 
                FROM subscriptions s
                JOIN users u ON s.user_id = u.id
                JOIN memberships m ON s.plan_id = m.id
                WHERE s.status = ?
                ORDER BY s.date_submitted DESC
            ");
            $stmt->bind_param("s", $status);
        } else {
            $stmt = $conn->prepare("
                SELECT s.*, u.username, m.plan_name 
                FROM subscriptions s
                JOIN users u ON s.user_id = u.id
                JOIN memberships m ON s.plan_id = m.id
                ORDER BY s.date_submitted DESC
            ");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        //remove later
        if ($result === false) {
            echo json_encode(["error" => "Query failed", "sql_error" => $conn->error]);
            exit();
        }


        $subscriptions = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($subscriptions);
        break;


    case 'POST':
        // ===============================
        // Approve / Reject a subscription
        // ===============================
        $input = json_decode(file_get_contents("php://input"), true);

        if (!isset($input['id']) || !isset($input['status'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit();
        }

        $id = $input['id'];
        $status = $input['status'];
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';
        $admin_id = $_SESSION['user_id'];

        if (!in_array($status, ['Approved', 'Rejected'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid status"]);
            exit();
        }

        $stmt = $conn->prepare("
            UPDATE subscriptions
            SET status = ?, admin_id = ?, date_approved = NOW(), remarks = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sisi", $status, $admin_id, $remarks, $id);

        if ($stmt->execute()) {
            // Optional: Log the admin action
            if (function_exists('logAction')) {
                logAction($conn, $admin_id, "Updated subscription #$id to $status");
            }

            echo json_encode(["success" => true, "message" => "Subscription updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update subscription"]);
        }
        break;


    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

$conn->close();
?>