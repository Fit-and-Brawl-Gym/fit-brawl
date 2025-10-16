<?php
// ==============================================
// admin_subscriptions_api.php
// Handles admin actions for managing subscriptions
// ==============================================

// Allow JSON responses
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/init.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// APPROVE subscription
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'approve') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $admin_id = $_SESSION['user_id'];
    $date_approved = date('Y-m-d H:i:s');

    // Get subscription details to calculate dates
    $stmt = $conn->prepare("SELECT plan_id, duration FROM user_memberships WHERE id = ? AND request_status = 'pending'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription = $result->fetch_assoc();
    $stmt->close();

    if (!$subscription) {
        echo json_encode(['success' => false, 'message' => 'Subscription not found or already processed']);
        exit;
    }

    // Calculate start and end dates
    $start_date = date('Y-m-d');
    $end_date = null;
    if (!empty($subscription['duration'])) {
        $end_date = date('Y-m-d', strtotime("+{$subscription['duration']} days"));
    }

    // Update subscription
    $stmt = $conn->prepare("UPDATE user_memberships SET 
        request_status = 'approved', 
        membership_status = 'active',
        admin_id = ?, 
        date_approved = ?,
        start_date = ?,
        end_date = ?,
        source_table = 'user_memberships',
        source_id = id
        WHERE id = ?");
    $stmt->bind_param('isssi', $admin_id, $date_approved, $start_date, $end_date, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Subscription approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// REJECT subscription
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reject') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $remarks = trim($_POST['remarks'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    if (empty($remarks)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        exit;
    }

    $admin_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE user_memberships SET 
        request_status = 'rejected', 
        membership_status = 'cancelled',
        admin_id = ?, 
        remarks = ?,
        source_table = 'user_memberships',
        source_id = id
        WHERE id = ? AND request_status = 'pending'");
    $stmt->bind_param('isi', $admin_id, $remarks, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Subscription rejected successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Subscription not found or already processed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// FETCH subscriptions
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $type = $_GET['type'] ?? 'processing';

    $sql = "SELECT 
                um.id,
                um.user_id,
                u.username AS member,
                m.plan_name AS plan,
                um.qr_proof,
                um.date_submitted,
                um.date_approved,
                um.remarks,
                um.request_status,
                um.start_date,
                um.end_date,
                um.membership_status
            FROM user_memberships um
            LEFT JOIN users u ON um.user_id = u.id
            LEFT JOIN memberships m ON um.plan_id = m.id
            WHERE ";

    switch ($type) {
        case 'processing':
            $sql .= "um.request_status = 'pending'";
            break;
        case 'approved':
            $sql .= "um.request_status = 'approved' AND um.membership_status = 'active'";
            break;
        case 'rejected':
            $sql .= "um.request_status = 'rejected'";
            break;
        default:
            $sql .= "um.request_status = 'pending'";
    }

    $sql .= " ORDER BY um.date_submitted DESC";

    $result = $conn->query($sql);

    if ($result) {
        $subscriptions = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $subscriptions]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch subscriptions']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>