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

// Format date nicely
if ($membershipRequest && $membershipRequest['date_submitted']) {
    $date = new DateTime($membershipRequest['date_submitted']);
    $formattedDate = $date->format('F j, Y \a\t g:i A'); // e.g., "October 24, 2025 at 3:45 PM"
}

$pageTitle = "Membership Status - Fit and Brawl";
$currentPage = "membership_status";
$additionalCSS = ['../css/pages/membership-status.css'];
$additionalJS = [];
require_once '../../includes/header.php';
?>
    <main>
        <?php if ($membershipRequest): ?>
            <?php
                $planName = htmlspecialchars($membershipRequest['plan_name']);
                $status = $membershipRequest['request_status'];
                $formattedDate = date('F d, Y', strtotime($membershipRequest['date_submitted']));
                $isUpgrade = !empty($membershipRequest['source_id']); // means it's an upgrade from previous membership
            ?>

            <?php if ($status === 'pending'): ?>
                <div class="status-message pending">
                    <h2><?= $isUpgrade ? 'Upgrade Request Submitted' : 'Payment Submitted' ?></h2>
                    <p>
                        Thank you for submitting your <?= $isUpgrade ? 'upgrade' : 'payment' ?> for the
                        <strong><?= $planName ?></strong> plan.
                    </p>
                    <p>
                        Your request is currently <strong>pending admin approval</strong>.
                        Please wait for confirmation before using your <?= $isUpgrade ? 'upgraded' : 'new' ?> plan.
                    </p>
                    <p class="date-info">
                        <i class="fa-regular fa-calendar"></i>
                        Submitted on <?= $formattedDate ?>
                    </p>
                </div>

            <?php elseif ($status === 'rejected'): ?>
                <div class="status-message rejected">
                    <h2><?= $isUpgrade ? 'Upgrade Request Rejected' : 'Payment Rejected' ?></h2>
                    <p>
                        Your <?= $isUpgrade ? 'upgrade request' : 'payment' ?> for the
                        <strong><?= $planName ?></strong> plan was <strong>rejected</strong>.
                    </p>
                    <p>Please contact support or submit a new <?= $isUpgrade ? 'upgrade request' : 'payment' ?>.</p>
                    <p class="date-info">
                        <i class="fa-regular fa-calendar"></i>
                        Submitted on <?= $formattedDate ?>
                    </p>
                </div>

            <?php elseif ($status === 'approved'): ?>
                <div class="status-message approved">
                    <h2><?= $isUpgrade ? 'Upgrade Approved!' : 'Membership Approved!' ?></h2>
                    <p>
                        Your <?= $isUpgrade ? 'upgrade request' : 'membership payment' ?> for the
                        <strong><?= $planName ?></strong> plan has been approved.
                    </p>
                    <p>Enjoy your <?= $isUpgrade ? 'new plan benefits' : 'membership privileges' ?>!</p>
                    <p class="date-info">
                        <i class="fa-regular fa-calendar"></i>
                        Approved on <?= date('F d, Y', strtotime($membershipRequest['date_approved'] ?? 'now')) ?>
                    </p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="status-message none">
                <h2>No Pending Requests</h2>
                <p>
                    You currently have no active or pending membership requests.
                    <a href="membership.php">Select a plan</a> to become a member or upgrade your plan.
                </p>
            </div>
        <?php endif; ?>

        <div class="button-group">
            <a href="loggedin-index.php" class="btn-home">
                <i class="fa-solid fa-house"></i> Return to Home
            </a>
            <a href="membership.php" class="btn-secondary">
                <?= $membershipRequest ? 'Change Plan' : 'View Plans' ?>
            </a>
        </div>
    </main>

<?php require_once '../../includes/footer.php'; ?>
