<?php
// Check if user has active membership (robust to schema differences)
$hasActiveMembership = false;
if (isset($_SESSION['user_id'])) {
    if (!isset($conn)) {
        require_once __DIR__ . '/db_connect.php';
    }

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        return false;
    }

    $query = "SELECT id FROM user_memberships WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() LIMIT 1";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        // Debug: log the SQL error and stop gracefully
        error_log('membership_check prepare failed: ' . $conn->error);
        return false;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasActiveMembership = ($result && $result->num_rows > 0);
    $stmt->close();
    $user_id = $_SESSION['user_id'];

    // Detect which status column exists
    $statusColumn = null;
    $check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'membership_status'");
    if ($check && $check->num_rows > 0) {
        $statusColumn = 'membership_status';
    } else {
        $check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'status'");
        if ($check && $check->num_rows > 0) {
            $statusColumn = 'status';
        } else {
            $check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'request_status'");
            if ($check && $check->num_rows > 0) {
                $statusColumn = 'request_status';
            }
        }
    }

    if ($statusColumn === 'membership_status' || $statusColumn === 'status') {
        $membership_query = "SELECT id FROM user_memberships WHERE user_id = ? AND " . $statusColumn . " = 'active' AND end_date >= CURDATE() LIMIT 1";
    } elseif ($statusColumn === 'request_status') {
        $membership_query = "SELECT id FROM user_memberships WHERE user_id = ? AND request_status = 'approved' AND end_date >= CURDATE() LIMIT 1";
    } else {
        $membership_query = "SELECT id FROM user_memberships WHERE user_id = ? AND end_date >= CURDATE() LIMIT 1";
    }

    $stmt = $conn->prepare($membership_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasActiveMembership = ($result && $result->num_rows > 0);
}

// Set membership link based on subscription status
$membershipLink = $hasActiveMembership ? 'reservations.php' : 'membership.php';
?>