<?php
// Check if user has active membership
$hasActiveMembership = false;
if(isset($_SESSION['user_id'])) {
    if (!isset($conn)) {
        require_once __DIR__ . '/db_connect.php';
    }

    $user_id = $_SESSION['user_id'];
    $membership_query = "SELECT id FROM user_memberships
                        WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()
                        LIMIT 1";
    $stmt = $conn->prepare($membership_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasActiveMembership = $result->num_rows > 0;
}

// Set membership link based on subscription status
$membershipLink = $hasActiveMembership ? 'reservations.php' : 'membership.php';
?>
