<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in and is a trainer
if (!SessionManager::isLoggedIn() || !isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');

    // Check if is_visible column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM feedback LIKE 'is_visible'");
    $hasVisibleColumn = $checkColumn->num_rows > 0;

    // Build SQL - ALWAYS include id column
    if ($hasVisibleColumn) {
        $sql = "SELECT id, user_id, username, message, avatar, date, is_visible FROM feedback WHERE is_visible = 1 ORDER BY date DESC";
    } else {
        $sql = "SELECT id, user_id, username, message, avatar, date, 1 as is_visible FROM feedback ORDER BY date DESC";
    }

    $result = $conn->query($sql);

    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }

    echo json_encode($feedbacks);
    exit;
}

// Determine avatar source for logged-in users
$avatarSrc = '../../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../../images/account-icon.png";
}

// Set variables for header
$pageTitle = "Feedback - Fit and Brawl Trainer";
$currentPage = "feedback";
$additionalCSS = ["../../css/pages/feedback.css?=v2"];

// Include header
require_once '../../../includes/trainer_header.php';
?>
    <!--Main-->
    <main>
        <!-- Page Header -->
        <div class="feedback-header">
            <h1 class="feedback-title">Member Testimonials</h1>
            <p class="feedback-subtitle">See what our community has to say about their Fit X Brawl experience</p>
            <div class="header-underline"></div>
        </div>

        <div class="feedback-container">
            <div class="feedback-section" id="feedback-section">

            </div>
        </div>
    </main>

    <script src="../../js/feedback.js"></script>

<?php require_once '../../../includes/trainer_footer.php'; ?>
