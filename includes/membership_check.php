<?php
// Check if user has active membership (robust to schema differences)
$hasActiveMembership = false;
if (isset($_SESSION['user_id'])) {
    if (!isset($conn)) {
        require_once __DIR__ . '/db_connect.php';
    }

    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    if ($user_id <= 0) {
        // no valid user id
        $membershipLink = 'membership.php';
        return;
    }

    $membership_query = null;

    // Prefer combined user_memberships table
    if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
        // detect available status-like column
        if ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'membership_status'")->num_rows) {
            $membership_query = "SELECT id FROM user_memberships WHERE user_id = ? AND membership_status = 'active' AND end_date >= CURDATE() LIMIT 1";
        } elseif ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'status'")->num_rows) {
            $membership_query = "SELECT id FROM user_memberships WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() LIMIT 1";
        } elseif ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'request_status'")->num_rows) {
            $membership_query = "SELECT id FROM user_memberships WHERE user_id = ? AND request_status = 'approved' AND end_date >= CURDATE() LIMIT 1";
        } else {
            $membership_query = "SELECT id FROM user_memberships WHERE user_id = ? AND end_date >= CURDATE() LIMIT 1";
        }
    }

    // Fallback to legacy subscriptions table
    if (!$membership_query && $conn->query("SHOW TABLES LIKE 'subscriptions'")->num_rows) {
        $membership_query = "SELECT id FROM subscriptions WHERE user_id = ? AND status IN ('Approved','approved') LIMIT 1";
    }

    if ($membership_query) {
        $stmt = $conn->prepare($membership_query);
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $hasActiveMembership = ($result && $result->num_rows > 0);
            $stmt->close();
        } else {
            error_log('membership_check prepare failed: ' . $conn->error);
        }
    }
}

// Set membership link based on subscription status
$membershipLink = $hasActiveMembership ? 'reservations.php' : 'membership.php';
?>
