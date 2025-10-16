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

// helper for consistent JSON errors
function json_error($message, $code = 500)
{
    http_response_code($code);
    echo json_encode(["error" => $message]);
    exit();
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    json_error('Access denied', 403);
}

// Database connection check
if (!isset($conn) || $conn->connect_error) {
    json_error('Database connection failed: ' . ($conn->connect_error ?? 'unknown'));
}

$method = $_SERVER['REQUEST_METHOD'];
$admin_id = $_SESSION['user_id'] ?? 0;

// helper
function column_exists($conn, $table, $col)
{
    $res = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $conn->real_escape_string($col) . "'");
    return ($res && $res->num_rows > 0);
}

switch ($method) {
    case 'GET':
        // ===============================
        // Fetch all subscriptions
        // ===============================
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;

        // Build SELECT including plan name + price and remarks if present
        $selectRemarks = column_exists($conn, 'subscriptions', 'remarks') ? ', s.remarks' : ', NULL AS remarks';
        $sql = "
            SELECT
              s.id,
              s.user_id,
              u.username,
              s.plan_id,
              m.plan_name,
              m.price,
              s.duration,
              s.qr_proof,
              s.status,
              s.admin_id,
              s.date_submitted,
              s.date_approved
              {$selectRemarks}
            FROM subscriptions s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN memberships m ON s.plan_id = m.id
        ";

        if ($status) {
            $sql .= " WHERE s.status = ? ";
            $sql .= " ORDER BY s.date_submitted DESC";
            $stmt = $conn->prepare($sql);
            if ($stmt === false)
                json_error('DB prepare failed: ' . $conn->error);
            if (!$stmt->bind_param('s', $status)) {
                $stmt->close();
                json_error('DB bind failed: ' . $stmt->error);
            }
        } else {
            $sql .= " ORDER BY s.date_submitted DESC";
            $stmt = $conn->prepare($sql);
            if ($stmt === false)
                json_error('DB prepare failed: ' . $conn->error);
        }

        if (!$stmt->execute()) {
            $err = $stmt->error ?: $conn->error;
            $stmt->close();
            json_error('DB execute failed: ' . $err);
        }
        $res = $stmt->get_result();
        if ($res === false) {
            $stmt->close();
            json_error('Get result failed: ' . $stmt->error);
        }
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode($rows);
        break;


    case 'POST':
        // ===============================
        // Approve / Reject a subscription
        // ===============================
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input))
            $input = $_POST;

        $id = isset($input['id']) ? (int) $input['id'] : 0;
        $newStatus = $input['status'] ?? '';
        $reason = trim($input['reason'] ?? '');

        $allowed = ['Pending', 'Approved', 'Rejected'];
        if ($id <= 0 || !in_array($newStatus, $allowed, true))
            json_error('Invalid id or status', 400);

        $hasRemarks = column_exists($conn, 'subscriptions', 'remarks');

        if ($newStatus === 'Rejected') {
            if ($hasRemarks) {
                $stmt = $conn->prepare("UPDATE subscriptions SET status = ?, remarks = ?, admin_id = ?, date_approved = NULL WHERE id = ?");
                if ($stmt === false)
                    json_error('DB prepare failed: ' . $conn->error);
                if (!$stmt->bind_param('ssii', $newStatus, $reason, $admin_id, $id)) {
                    $stmt->close();
                    json_error('DB bind failed: ' . $stmt->error);
                }
            } else {
                $stmt = $conn->prepare("UPDATE subscriptions SET status = ?, admin_id = ?, date_approved = NULL WHERE id = ?");
                if ($stmt === false)
                    json_error('DB prepare failed: ' . $conn->error);
                if (!$stmt->bind_param('sii', $newStatus, $admin_id, $id)) {
                    $stmt->close();
                    json_error('DB bind failed: ' . $stmt->error);
                }
            }
        } elseif ($newStatus === 'Approved') {
            // set admin + date_approved and clear remarks if present
            if ($hasRemarks) {
                $stmt = $conn->prepare("UPDATE subscriptions SET status = ?, remarks = NULL, admin_id = ?, date_approved = NOW() WHERE id = ?");
                if ($stmt === false)
                    json_error('DB prepare failed: ' . $conn->error);
                if (!$stmt->bind_param('sii', $newStatus, $admin_id, $id)) {
                    $stmt->close();
                    json_error('DB bind failed: ' . $stmt->error);
                }
            } else {
                $stmt = $conn->prepare("UPDATE subscriptions SET status = ?, admin_id = ?, date_approved = NOW() WHERE id = ?");
                if ($stmt === false)
                    json_error('DB prepare failed: ' . $conn->error);
                if (!$stmt->bind_param('sii', $newStatus, $admin_id, $id)) {
                    $stmt->close();
                    json_error('DB bind failed: ' . $stmt->error);
                }
            }
        } else { // Pending or other
            if ($hasRemarks) {
                $stmt = $conn->prepare("UPDATE subscriptions SET status = ?, remarks = NULL WHERE id = ?");
                if ($stmt === false)
                    json_error('DB prepare failed: ' . $conn->error);
                if (!$stmt->bind_param('si', $newStatus, $id)) {
                    $stmt->close();
                    json_error('DB bind failed: ' . $stmt->error);
                }
            } else {
                $stmt = $conn->prepare("UPDATE subscriptions SET status = ? WHERE id = ?");
                if ($stmt === false)
                    json_error('DB prepare failed: ' . $conn->error);
                if (!$stmt->bind_param('si', $newStatus, $id)) {
                    $stmt->close();
                    json_error('DB bind failed: ' . $stmt->error);
                }
            }
        }

        if (!$stmt->execute()) {
            $err = $stmt->error ?: $conn->error;
            $stmt->close();
            json_error('DB execute failed: ' . $err);
        }
        $stmt->close();

        // Optional: Log the admin action
        if (function_exists('logAction')) {
            logAction($conn, $admin_id, "Updated subscription #{$id} -> {$newStatus}" . ($newStatus === 'Rejected' && $reason ? " (reason: {$reason})" : ''));
        }

        echo json_encode(['success' => true, 'message' => 'Subscription updated']);
        break;


    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

$conn->close();
?>