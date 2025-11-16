<?php
// ==============================================
// admin_subscriptions_api.php
// Handles admin actions for managing subscriptions
// ==============================================

// Allow JSON responses
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/../../../../includes/activity_logger.php';
require_once __DIR__ . '/../../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../../includes/api_rate_limiter.php';
// mailer for membership notifications
include_once __DIR__ . '/../../../../includes/membership_mailer.php';

// Initialize activity logger
ActivityLogger::init($conn);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Rate limiting for admin APIs - 20 requests per minute per admin
$adminId = $_SESSION['user_id'] ?? 'unknown';
$rateCheck = ApiRateLimiter::checkAndIncrement($conn, 'admin_api:' . $adminId, 20, 60);
if ($rateCheck['blocked']) {
    http_response_code(429);
    header('X-RateLimit-Limit: 20');
    header('X-RateLimit-Remaining: 0');
    header('Retry-After: ' . $rateCheck['retry_after']);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}
header('X-RateLimit-Limit: 20');
header('X-RateLimit-Remaining: ' . $rateCheck['remaining']);
header('X-RateLimit-Reset: ' . (time() + $rateCheck['retry_after']));

$method = $_SERVER['REQUEST_METHOD'];

// APPROVE subscription
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'approve') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }

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
    $stmt->bind_param('ssssi', $admin_id, $date_approved, $start_date, $end_date, $id);

    if ($stmt->execute()) {
        // LOG THE ACTIVITY - FIX: Don't use LEFT JOIN with m, plan_name is in user_memberships
        $logStmt = $conn->prepare("SELECT name, plan_name, duration FROM user_memberships WHERE id = ?");
        $logStmt->bind_param('i', $id);
        $logStmt->execute();
        $logResult = $logStmt->get_result();
        $logData = $logResult->fetch_assoc();
        $logStmt->close();

        if ($logData) {
            $logSuccess = ActivityLogger::log(
                'subscription_approved',
                $logData['name'],
                $id,
                "Approved {$logData['plan_name']} subscription for {$logData['name']} (Duration: {$logData['duration']} days)"
            );

            // Try to send approval email to the user
            try {
                // fetch user email and up-to-date membership row
                $emailStmt = $conn->prepare("SELECT u.email, u.username, um.plan_name, um.start_date, um.end_date FROM user_memberships um LEFT JOIN users u ON um.user_id = u.id WHERE um.id = ? LIMIT 1");
                if ($emailStmt) {
                    $emailStmt->bind_param('i', $id);
                    $emailStmt->execute();
                    $emailRow = $emailStmt->get_result()->fetch_assoc();
                    $emailStmt->close();

                    if ($emailRow && !empty($emailRow['email'])) {
                        // no perks array here; leave empty or extend later
                        sendMembershipDecisionEmail($emailRow['email'], $emailRow['username'] ?? $logData['name'], $emailRow['plan_name'] ?? $logData['plan_name'], true, $emailRow['start_date'] ?? null, $emailRow['end_date'] ?? null, null, []);
                    }
                }
            } catch (Exception $e) {
                error_log('Failed to send membership approval email: ' . $e->getMessage());
            }

            // Debug: Check if logging worked
            error_log("Activity log result: " . ($logSuccess ? 'SUCCESS' : 'FAILED'));
        } else {
            error_log("Could not fetch subscription data for logging");
        }

        echo json_encode(['success' => true, 'message' => 'Subscription approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// REJECT subscription
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reject') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }

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
    $stmt->bind_param('ssi', $admin_id, $remarks, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // LOG THE ACTIVITY - FIX: Don't use LEFT JOIN with m
            $logStmt = $conn->prepare("SELECT name, plan_name FROM user_memberships WHERE id = ?");
            $logStmt->bind_param('i', $id);
            $logStmt->execute();
            $logResult = $logStmt->get_result();
            $logData = $logResult->fetch_assoc();
            $logStmt->close();

            if ($logData) {
                $logSuccess = ActivityLogger::log(
                    'subscription_rejected',
                    $logData['name'],
                    $id,
                    "Rejected {$logData['plan_name']} subscription for {$logData['name']}. Reason: {$remarks}"
                );

                // Try to send rejection email to the user (include admin remarks)
                try {
                    $emailStmt = $conn->prepare("SELECT u.email, u.username, um.plan_name FROM user_memberships um LEFT JOIN users u ON um.user_id = u.id WHERE um.id = ? LIMIT 1");
                    if ($emailStmt) {
                        $emailStmt->bind_param('i', $id);
                        $emailStmt->execute();
                        $emailRow = $emailStmt->get_result()->fetch_assoc();
                        $emailStmt->close();

                        if ($emailRow && !empty($emailRow['email'])) {
                            sendMembershipDecisionEmail($emailRow['email'], $emailRow['username'] ?? $logData['name'], $emailRow['plan_name'] ?? $logData['plan_name'], false, null, null, $remarks, []);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Failed to send membership rejection email: ' . $e->getMessage());
                }

                // Debug: Check if logging worked
                error_log("Activity log result: " . ($logSuccess ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Could not fetch subscription data for logging");
            }

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

// MARK CASH PAYMENT AS PAID
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mark_cash_paid') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    // Check if payment_method column exists
    $columns_check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'payment_method'");
    if ($columns_check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Payment method column not found. Please update database.']);
        exit;
    }

    $admin_id = $_SESSION['user_id'];
    $payment_date = date('Y-m-d H:i:s');

    // Verify this is a cash payment that's unpaid
    $stmt = $conn->prepare("SELECT id, user_id, name, plan_name FROM user_memberships WHERE id = ? AND payment_method = 'cash' AND cash_payment_status = 'unpaid'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription = $result->fetch_assoc();
    $stmt->close();

    if (!$subscription) {
        echo json_encode(['success' => false, 'message' => 'Cash payment not found or already marked as paid']);
        exit;
    }

    // Get subscription details for calculating dates
    $detailsStmt = $conn->prepare("SELECT duration FROM user_memberships WHERE id = ?");
    $detailsStmt->bind_param('i', $id);
    $detailsStmt->execute();
    $details = $detailsStmt->get_result()->fetch_assoc();
    $detailsStmt->close();

    // Calculate start and end dates
    $start_date = date('Y-m-d');
    $end_date = null;
    if (!empty($details['duration'])) {
        $end_date = date('Y-m-d', strtotime("+{$details['duration']} days"));
    }

    // Update cash payment status AND approve membership
    $stmt = $conn->prepare("UPDATE user_memberships SET
        cash_payment_status = 'paid',
        cash_payment_date = ?,
        cash_received_by = ?,
        request_status = 'approved',
        membership_status = 'active',
        admin_id = ?,
        date_approved = ?,
        start_date = ?,
        end_date = ?,
        source_table = 'user_memberships',
        source_id = id
        WHERE id = ?");
    $stmt->bind_param('ssssssi', $payment_date, $admin_id, $admin_id, $payment_date, $start_date, $end_date, $id);

    if ($stmt->execute()) {
        // Log the activity
        ActivityLogger::log(
            'cash_payment_received',
            $subscription['name'],
            $id,
            "Received cash payment and approved {$subscription['plan_name']} membership for {$subscription['name']}"
        );

        // Try to send approval email
        try {
            $emailStmt = $conn->prepare("SELECT u.email, u.username FROM user_memberships um LEFT JOIN users u ON um.user_id = u.id WHERE um.id = ? LIMIT 1");
            if ($emailStmt) {
                $emailStmt->bind_param('i', $id);
                $emailStmt->execute();
                $emailRow = $emailStmt->get_result()->fetch_assoc();
                $emailStmt->close();

                if ($emailRow && !empty($emailRow['email'])) {
                    sendMembershipDecisionEmail($emailRow['email'], $emailRow['username'] ?? $subscription['name'], $subscription['plan_name'], true, $start_date, $end_date, null, []);
                }
            }
        } catch (Exception $e) {
            error_log('Failed to send membership approval email: ' . $e->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Cash payment received and membership approved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update payment status: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// FETCH subscriptions
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $type = $_GET['type'] ?? 'processing';

    // Check if payment_method column exists
    $columns_check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'payment_method'");
    $has_payment_method = $columns_check->num_rows > 0;

    $payment_columns = $has_payment_method ? ", um.payment_method, um.cash_payment_status, um.cash_payment_date" : "";

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
                {$payment_columns}
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
