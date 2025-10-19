<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Redirect if not logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit;
}


$stmt = $conn->prepare("
    SELECT request_status, plan_name, date_submitted 
    FROM user_memberships 
    WHERE user_id = ? 
      AND request_status IN ('pending','rejected')
    ORDER BY date_submitted DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$membershipRequest = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Membership Status</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/membership-status.css">
</head>
<body>
    <main>
        <?php if ($membershipRequest): ?>
            <?php if ($membershipRequest['request_status'] === 'pending'): ?>
                <div class="status-message pending">
                    <h2>Payment Submitted</h2>
                    <p>Thank you for submitting your payment for the <strong><?= htmlspecialchars($membershipRequest['plan_name']) ?></strong> plan.</p>
                    <p>Your request is currently <strong>pending approval</strong>. Please wait for our admin to approve it.</p>
                    <p>Submitted on: <?= htmlspecialchars($membershipRequest['date_submitted']) ?></p>
                </div>
            <?php elseif ($membershipRequest['request_status'] === 'rejected'): ?>
                <div class="status-message rejected">
                    <h2>Payment Rejected</h2>
                    <p>Your payment for the <strong><?= htmlspecialchars($membershipRequest['plan_name']) ?></strong> plan was rejected.</p>
                    <p>Please contact our support to resolve the issue or submit a new payment.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="status-message none">
                <h2>No Pending Requests</h2>
                <p>You currently have no pending membership requests. <a href="membership.php">Select a plan</a> to become a member.</p>
            </div>
        <?php endif; ?>

        <a href="loggedin-index.php" class="back-btn">Back</a>
    </main>
</body>
</html>
