<?php
// Check if user has active membership
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
}

// Set membership link based on subscription status
$membershipLink = $hasActiveMembership ? 'reservations.php' : 'membership.php';
?>