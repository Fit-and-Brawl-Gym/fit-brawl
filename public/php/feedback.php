<?php

require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

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


if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['api']) && $_GET['api'] === 'true')) {
    header('Content-Type: application/json');
    include __DIR__ . '/../../includes/db_connect.php';

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'];
        $message = $data['message'];

        $sql = "INSERT INTO feedback (user_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $user_id, $message);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Feedback submitted"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        $stmt->close();
        exit;
    }

    // Get filter parameters
    $plan_filter = isset($_GET['plan']) ? trim($_GET['plan']) : 'all';
    $sort_by = isset($_GET['sort']) ? trim($_GET['sort']) : 'recent';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Check if is_visible column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM feedback LIKE 'is_visible'");
    $hasVisibleColumn = $checkColumn->num_rows > 0;

    // Check if helpful_count column exists
    $checkHelpfulColumn = $conn->query("SHOW COLUMNS FROM feedback LIKE 'helpful_count'");
    $hasHelpfulColumn = $checkHelpfulColumn->num_rows > 0;

    // Build SQL - ALWAYS include id column
    $sql = "SELECT f.id, f.user_id, f.username, f.message, f.avatar, f.date";

    if ($hasVisibleColumn) {
        $sql .= ", f.is_visible";
    } else {
        $sql .= ", 1 as is_visible";
    }

    if ($hasHelpfulColumn) {
        $sql .= ", f.helpful_count, f.not_helpful_count";
    } else {
        $sql .= ", 0 as helpful_count, 0 as not_helpful_count";
    }

    // Add user vote status
    if ($current_user_id) {
        $sql .= ", fv.vote_type as user_vote";
    } else {
        $sql .= ", NULL as user_vote";
    }

    // Add membership plan
    $sql .= ", COALESCE(m.plan_name, 'No Plan') as plan_name
            FROM feedback f";

    if ($current_user_id) {
        $sql .= " LEFT JOIN feedback_votes fv ON f.id = fv.feedback_id AND fv.user_id = ?";
    }

    $sql .= " LEFT JOIN users u ON f.user_id = u.id
             LEFT JOIN user_memberships um ON u.id = um.user_id AND um.membership_status = 'active'
             LEFT JOIN memberships m ON um.plan_id = m.id";

    if ($hasVisibleColumn) {
        $sql .= " WHERE f.is_visible = 1";
    } else {
        $sql .= " WHERE 1=1";
    }

    // Apply plan filter
    if ($plan_filter !== 'all') {
        $sql .= " AND m.plan_name = ?";
    }

    // Apply search filter
    if ($search !== '') {
        $sql .= " AND (f.message LIKE ? OR f.username LIKE ?)";
    }

    // Apply sorting
    if ($sort_by === 'relevant' && $hasHelpfulColumn) {
        $sql .= " ORDER BY f.helpful_count DESC, f.date DESC";
    } else {
        $sql .= " ORDER BY f.date DESC";
    }

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $params = [];
    $types = '';

    if ($current_user_id) {
        $params[] = $current_user_id;
        $types .= 'i';
    }

    if ($plan_filter !== 'all') {
        $params[] = $plan_filter;
        $types .= 's';
    }

    if ($search !== '') {
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }

    echo json_encode($feedbacks);
    $stmt->close();
    exit;
}

// Check membership status for header
require_once '../../includes/membership_check.php';

$hasActiveMembership = false;
$hasAnyRequest = false;
$gracePeriodDays = 3;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');

    // Check user_memberships table
    if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT request_status, membership_status, end_date
            FROM user_memberships
            WHERE user_id = ?
            ORDER BY date_submitted DESC
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $requestStatus = $row['request_status'] ?? null;
                $membershipStatus = $row['membership_status'] ?? null;
                $endDate = $row['end_date'] ?? null;

                $hasAnyRequest = true;

                if ($requestStatus === 'approved' && $endDate) {
                    $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

                    if ($expiryWithGrace >= $today) {

                        $hasActiveMembership = true;
                        $hasAnyRequest = false;
                    }
                }
            }

            $stmt->close();
        }


    } elseif ($conn->query("SHOW TABLES LIKE 'subscriptions'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT status, end_date
            FROM subscriptions
            WHERE user_id = ? AND status IN ('Approved','approved')
            ORDER BY date_submitted DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $status = strtolower($row['status']);
                $endDate = $row['end_date'] ?? null;
                $hasAnyRequest = true;

                if ($status === 'approved' && $endDate) {
                    $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

                    if ($expiryWithGrace >= $today) {
                        $hasActiveMembership = true;
                        $hasAnyRequest = false;
                    }
                }
            }

            $stmt->close();
        }
    }
}


if ($hasActiveMembership) {
    $membershipLink = 'reservations.php';
} elseif ($hasAnyRequest) {
    $membershipLink = 'membership-status.php';
} else {
    $membershipLink = 'membership.php';
}

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'account-icon.svg' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

// Set variables for header
$pageTitle = "Feedback - Fit and Brawl";
$currentPage = "feedback";
$additionalCSS = [PUBLIC_PATH . "/css/pages/feedback.css?=v2"];
$additionalJS = ['../js/feedback.js'];
$pageCsrfToken = CSRFProtection::generateToken();

// Include header
require_once '../../includes/header.php';
?>

<script>
    window.CSRF_TOKEN = <?= json_encode($pageCsrfToken); ?>;
</script>

<!--Main-->
<main>
    <!-- Page Header -->
    <div class="feedback-header">
        <h1 class="feedback-title">Member Feedback</h1>
        <p class="feedback-subtitle">See what our community has to say about their Fit X Brawl experience</p>
        <div class="header-underline"></div>
    </div>

    <!-- Filters Section -->
    <div class="filters-container">
        <div class="filters-wrapper">
            <!-- Search Bar -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search feedback by keywords..." />
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <!-- Plan Filter -->
                <div class="filter-group">
                    <label for="planFilter">
                        <i class="fas fa-filter"></i>
                        <span>Plan:</span>
                    </label>
                    <select id="planFilter" class="filter-select">
                        <option value="all">All Plans</option>
                        <option value="Gladiator">Gladiator</option>
                        <option value="Brawler">Brawler</option>
                        <option value="Champion">Champion</option>
                        <option value="Clash">Clash</option>
                        <option value="Resolution">Resolution</option>
                    </select>
                </div>

                <!-- Sort Filter -->
                <div class="filter-group">
                    <label for="sortFilter">
                        <i class="fas fa-sort"></i>
                        <span>Sort by:</span>
                    </label>
                    <select id="sortFilter" class="filter-select">
                        <option value="recent">Most Recent</option>
                        <option value="relevant">Most Helpful</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="feedback-container">
        <div class="feedback-section" id="feedback-section">
            <!-- Loading State -->
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading feedback...</p>
            </div>
        </div>
    </div>
    <div class="feedback-button fab-group">
        <button class="back-to-top" aria-label="Back to top">
            <i class="fas fa-chevron-up"></i>
        </button>
        <button class="floating-btn" id="openFeedbackModal">
            <i class="fas fa-comment-dots"></i>
            Share your feedback!
        </button>
    </div>
</main>

<!-- Feedback Modal -->
<div id="feedbackModal" class="feedback-modal">
    <div class="feedback-modal-content">
        <div class="feedback-modal-header">
            <h2>Share Your Experience</h2>
            <button class="close-modal" id="closeFeedbackModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="feedbackSubmitForm" class="feedback-modal-form">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- Non-logged in users see name and email fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="feedbackName">Name (Optional)</label>
                        <input type="text" id="feedbackName" name="name" placeholder="Enter your name">
                    </div>
                    <div class="form-group">
                        <label for="feedbackEmail">Email (Optional)</label>
                        <input type="email" id="feedbackEmail" name="email" placeholder="your.email@example.com">
                    </div>
                </div>
            <?php else: ?>
                <!-- Logged in users don't see name/email fields -->
                <div class="logged-in-notice">
                    <i class="fas fa-user-check"></i>
                    <?php
                    $displayName = htmlspecialchars($_SESSION['name'] ?? 'Member');
                    // Truncate long names with ellipsis (max 25 characters)
                    if (mb_strlen($displayName) > 25) {
                        $displayName = mb_substr($displayName, 0, 22) . '...';
                    }
                    ?>
                    <span>Posting as: <strong id="postingAsName"><?= $displayName ?></strong></span>
                </div>
                <div class="form-group-checkbox">
                    <label class="checkbox-label">
                        <input type="checkbox" id="postAnonymous" name="post_anonymous">
                        <span>Post as Anonymous</span>
                    </label>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="feedbackMessage">Your Feedback <span class="required">*</span></label>
                <textarea id="feedbackMessage" name="message" rows="6"
                    placeholder="Share your experience with our gym, trainers, facilities, or anything else..."
                    required></textarea>
                <div class="char-counter">
                    <span id="charCount">0</span> / 1000 characters
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" id="cancelFeedback">Cancel</button>
                <button type="submit" class="btn-submit" id="submitFeedback">
                    <i class="fas fa-paper-plane"></i>
                    Submit Feedback
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="success-modal">
    <div class="success-modal-content">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Thank You!</h3>
        <p>Your feedback has been submitted successfully.</p>
        <button class="btn-success-close" id="closeSuccessModal">Got it!</button>
    </div>
</div>

<!--Footer-->
<footer>
    <div class="container footer-flex">
        <div class="footer-logo-block">
            <img src="../../images/footer-title.png" alt="FITXBRAWL" class="footer-logo-title">
        </div>
        <div class="footer-menu-block">
            <div class="footer-menu-title">MENU</div>
            <ul class="footer-menu-list">
                <li><a href="index.php">Home</a></li>
                <li><a href="membership.php">Membership</a></li>
                <li><a href="equipment.php">Equipment</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="feedback.php">Feedback</a></li>
            </ul>
        </div>
        <div class="footer-contact-block">
            <div class="footer-contact-title">CONTACT</div>
            <div class="footer-contact-details">
                1832 Oroquieta Rd, Santa Cruz, Manila,<br>
                1008 Metro Manila<br><br>
                Gmail: fitxbrawl@gmail.com
            </div>
        </div>
        <div class="footer-hours-block">
            <div class="footer-hours-title">OPENING HOURS</div>
            <div class="footer-hours-details">
                Sun–Fri: 9AM to 10PM<br>
                Saturday: 10AM to 7PM
            </div>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; 2025 Fit X Brawl, All rights reserved.</p>
    </div>
</footer>
<script>
    const IMAGES_PATH = "<?= IMAGES_PATH ?>";
    const UPLOADS_PATH = "<?= UPLOADS_PATH ?>";
</script>
</body>

</html>
