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
    SELECT id, request_status, plan_name, date_submitted, remarks, payment_method
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
                    <!-- Header Section -->
                    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(255, 204, 0, 0.2);">
                        <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 70px; height: 70px; background: rgba(255, 204, 0, 0.15); border-radius: 12px; border: 2px solid #ffcc00;">
                            <i class="fa-solid fa-money-bill-wave" style="font-size: 1.75rem; color: #ffcc00;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h2 style="margin: 0 0 0.35rem 0; font-size: 1.65em; color: #fff;">Cash Payment Pending</h2>
                            <p style="margin: 0; color: #aaa; font-size: 1em;">
                                <strong style="color: #ffcc00;"><?= $planName ?></strong> Membership • Submitted <?= $formattedDate ?>
                            </p>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <!-- Payment Proof Card -->
                        <div style="background: var(--color-primary); border: 3px solid #ffcc00; padding: 1.75rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(255, 204, 0, 0.2); text-align: center;">
                            <div style="display: inline-block; background: rgba(255, 204, 0, 0.2); padding: 0.4rem 1rem; border-radius: 20px; margin-bottom: 1rem; border: 1px solid rgba(255, 204, 0, 0.4);">
                                <p style="margin: 0; color: #ffcc00; font-weight: 700; font-size: 0.95em; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-id-card" style="margin-right: 6px;"></i>PAYMENT REFERENCE
                                </p>
                            </div>
                            <div style="background: rgba(0, 0, 0, 0.4); padding: 1.25rem 1.5rem; border-radius: 10px; border: 2px dashed #ffcc00; margin-top: 1rem;">
                                <p style="margin: 0 0 0.4rem 0; color: #999; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">
                                    Reference ID
                                </p>
                                <p style="margin: 0; color: #ffcc00; font-size: 2em; font-family: 'Courier New', monospace; font-weight: bold; letter-spacing: 2px;">
                                    #<?= str_pad($membershipRequest['id'], 6, '0', STR_PAD_LEFT) ?>
                                </p>
                            </div>
                            <p style="margin: 1rem 0 0 0; color: #bbb; font-size: 0.9em;">
                                Show this to staff at the counter
                            </p>
                        </div>

                        <!-- Instructions Card -->
                        <div style="background: rgba(255, 204, 0, 0.08); border: 1px solid rgba(255, 204, 0, 0.25); padding: 1.75rem; border-radius: 12px;">
                            <h3 style="margin: 0 0 1rem 0; color: #ffcc00; font-size: 1.15em; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fa-solid fa-list-check"></i> Next Steps
                            </h3>
                            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #ffcc00; color: #000; border-radius: 50%; font-weight: bold; font-size: 0.85em;">1</span>
                                    <p style="margin: 0; color: #ddd; line-height: 1.5;">Visit <strong>Fit and Brawl Gym</strong> during business hours <span style="color: #ffcc00; font-weight: 600;">(7AM - 12AM)</span></p>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #ffcc00; color: #000; border-radius: 50%; font-weight: bold; font-size: 0.85em;">2</span>
                                    <p style="margin: 0; color: #ddd; line-height: 1.5;">Present this screenshot or your <strong>Reference ID</strong> to the counter staff</p>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #ffcc00; color: #000; border-radius: 50%; font-weight: bold; font-size: 0.85em;">3</span>
                                    <p style="margin: 0; color: #ddd; line-height: 1.5;">Complete cash payment and receive instant activation</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Banner -->
                    <div style="background: rgba(15, 79, 82, 0.3); border-left: 4px solid #ffcc00; padding: 1rem 1.25rem; border-radius: 8px; display: flex; align-items: center; gap: 1rem;">
                        <i class="fa-solid fa-circle-info" style="color: #ffcc00; font-size: 1.25rem; flex-shrink: 0;"></i>
                        <p style="margin: 0; color: #ccc; line-height: 1.5;">
                            Your membership will be activated immediately after payment confirmation by our staff.
                        </p>
                    </div>

                <?php else: ?>
                    <!-- Online Payment Pending -->
                    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(255, 204, 0, 0.2);">
                        <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 70px; height: 70px; background: rgba(255, 204, 0, 0.15); border-radius: 12px; border: 2px solid #ffcc00;">
                            <i class="fa-solid fa-clock" style="font-size: 1.75rem; color: #ffcc00;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h2 style="margin: 0 0 0.35rem 0; font-size: 1.65em; color: #fff;">Payment Under Review</h2>
                            <p style="margin: 0; color: #aaa; font-size: 1em;">
                                <strong style="color: #ffcc00;"><?= $planName ?></strong> Membership • Submitted <?= $formattedDate ?>
                            </p>
                        </div>
                    </div>

                    <!-- Status Cards Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div style="background: rgba(255, 204, 0, 0.08); border: 1px solid rgba(255, 204, 0, 0.25); padding: 1.5rem; border-radius: 12px;">
                            <div style="display: flex; align-items: start; gap: 1rem;">
                                <i class="fa-solid fa-circle-check" style="color: #ffcc00; font-size: 1.5rem; flex-shrink: 0; margin-top: 0.15rem;"></i>
                                <div>
                                    <h3 style="margin: 0 0 0.5rem 0; color: #fff; font-size: 1.15em;">Payment Received</h3>
                                    <p style="margin: 0 0 0.5rem 0; color: #ccc; line-height: 1.6; font-size: 0.95em;">
                                        Your payment proof has been submitted successfully.
                                    </p>
                                    <p style="margin: 0; color: #999; font-size: 0.85em;">
                                        Reference ID: <span style="color: #ffcc00; font-weight: 600; font-family: 'Courier New', monospace;">#<?= str_pad($membershipRequest['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(15, 79, 82, 0.3); border: 1px solid rgba(255, 204, 0, 0.25); padding: 1.5rem; border-radius: 12px;">
                            <div style="display: flex; align-items: start; gap: 1rem;">
                                <i class="fa-solid fa-hourglass-half" style="color: #ffcc00; font-size: 1.5rem; flex-shrink: 0; margin-top: 0.15rem;"></i>
                                <div>
                                    <h3 style="margin: 0 0 0.5rem 0; color: #fff; font-size: 1.15em;">Review Timeline</h3>
                                    <p style="margin: 0; color: #ccc; line-height: 1.6; font-size: 0.95em;">
                                        Verification typically takes <strong>24-48 hours</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Banner -->
                    <div style="background: rgba(15, 79, 82, 0.3); border-left: 4px solid #ffcc00; padding: 1rem 1.25rem; border-radius: 8px; display: flex; align-items: center; gap: 1rem;">
                        <i class="fa-solid fa-circle-info" style="color: #ffcc00; font-size: 1.25rem; flex-shrink: 0;"></i>
                        <p style="margin: 0; color: #ccc; line-height: 1.5;">
                            You'll receive an email notification once your membership is approved by our admin team.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($status === 'rejected'): ?>
            <div class="status-message rejected">
                <!-- Header Section -->
                <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(220, 53, 69, 0.3);">
                    <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 70px; height: 70px; background: rgba(220, 53, 69, 0.15); border-radius: 12px; border: 2px solid #dc3545;">
                        <i class="fa-solid fa-circle-xmark" style="font-size: 1.75rem; color: #dc3545;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h2 style="margin: 0 0 0.35rem 0; font-size: 1.65em; color: #fff;">Payment Rejected</h2>
                        <p style="margin: 0 0 0.35rem 0; color: #aaa; font-size: 1em;">
                            <strong style="color: #ffcc00;"><?= $planName ?></strong> Membership • Submitted <?= $formattedDate ?>
                        </p>
                        <p style="margin: 0; color: #888; font-size: 0.9em;">
                            Reference ID: <span style="color: #999; font-weight: 600; font-family: 'Courier New', monospace;">#<?= str_pad($membershipRequest['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </p>
                    </div>
                </div>

                <?php if (!empty($membershipRequest['remarks'])): ?>
                    <!-- Rejection Reason Card -->
                    <div style="background: rgba(220, 53, 69, 0.08); border: 1px solid rgba(220, 53, 69, 0.3); padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 12px;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fa-solid fa-exclamation-triangle" style="color: #dc3545; font-size: 1.5rem; flex-shrink: 0;"></i>
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 0.5rem 0; color: #fff; font-size: 1.15em;">Reason for Rejection</h3>
                                <p style="margin: 0; color: #e0e0e0; line-height: 1.6; font-size: 0.95em;">
                                    <?= htmlspecialchars($membershipRequest['remarks']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Banner -->
                <div style="background: rgba(255, 204, 0, 0.08); border-left: 4px solid #ffcc00; padding: 1rem 1.25rem; border-radius: 8px; display: flex; align-items: center; gap: 1rem;">
                    <i class="fa-solid fa-circle-info" style="color: #ffcc00; font-size: 1.25rem; flex-shrink: 0;"></i>
                    <p style="margin: 0; color: #ccc; line-height: 1.5;">
                        You can resubmit your payment with the correct information or contact our support team for assistance.
                    </p>
                </div>
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
