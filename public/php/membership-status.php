<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Redirect if not logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Redirect admin and trainer to their respective dashboards
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin.php');
        exit;
    } elseif ($_SESSION['role'] === 'trainer') {
        header('Location: trainer/schedule.php');
        exit;
    }
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit;
}


$stmt = $conn->prepare("
    SELECT request_status, plan_name, date_submitted, remarks, payment_method
    FROM user_memberships
    WHERE user_id = ?
      AND request_status IN ('pending','rejected')
    ORDER BY date_submitted DESC
    LIMIT 1
");
$stmt->bind_param("s", $user_id);
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
                <?php if (isset($membershipRequest['payment_method']) && $membershipRequest['payment_method'] === 'cash'): ?>
                    <h2>Cash Payment Request Submitted</h2>
                    <p>
                        Your <strong><?= $planName ?></strong> membership request has been submitted. 
                        Please visit the gym to complete your cash payment.
                    </p>
                    <p>
                        Your membership will be activated once our staff confirms your payment.
                    </p>
                <?php else: ?>
                    <h2>Payment Submitted</h2>
                    <p>
                        Thank you for submitting your payment for the
                        <strong><?= $planName ?></strong> plan.
                    </p>
                    <p>
                        Your request is currently <strong>pending admin approval</strong>.
                        Please wait for confirmation before using your new plan.
                    </p>
                <?php endif; ?>
                <p class="date-info">
                    <i class="fa-regular fa-calendar"></i>
                    Submitted on <?= $formattedDate ?>
                </p>
            </div>

        <?php elseif ($status === 'rejected'): ?>
            <div class="status-message rejected">
                <h2>Payment Rejected</h2>
                <p>
                    Your payment for the
                    <strong><?= $planName ?></strong> plan was <strong>rejected</strong>.
                </p>
                <?php if (!empty($membershipRequest['remarks'])): ?>
                    <div style="background: rgba(15, 79, 82, 0.5); border-left: 4px solid #dc3545; padding: 1rem; margin: 1rem 0; border-radius: 8px;">
                        <p style="margin: 0; color: #fff;">
                            <strong style="color: #ffcc00;">Reason for Rejection:</strong><br>
                            <span style="color: #e0e0e0; display: block; margin-top: 0.5rem;"><?= htmlspecialchars($membershipRequest['remarks']) ?></span>
                        </p>
                    </div>
                <?php endif; ?>
                <p>Please contact support or submit a new payment.</p>
                <p class="date-info">
                    <i class="fa-regular fa-calendar"></i>
                    Submitted on <?= $formattedDate ?>
                </p>
            </div>

        <?php elseif ($status === 'approved'): ?>
            <div class="status-message approved">
                <h2>Membership Approved!</h2>
                <p>
                    Your membership payment for the
                    <strong><?= $planName ?></strong> plan has been approved.
                </p>
                <p>Enjoy your membership privileges!</p>
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
                <a href="membership.php">Select a plan</a> to become a member.
            </p>
        </div>
    <?php endif; ?>

    <div class="button-group">
        <a href="loggedin-index.php" class="btn-home">
            <i class="fa-solid fa-house"></i> Return to Home
        </a>
        <?php if ($membershipRequest && $status === 'rejected'): ?>
            <a href="membership.php" class="btn-secondary">
                <i class="fa-solid fa-rotate"></i> Resubmit or Choose New Plan
            </a>
        <?php elseif (!$membershipRequest || $status !== 'pending'): ?>
            <a href="membership.php" class="btn-secondary">
                View Plans
            </a>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
