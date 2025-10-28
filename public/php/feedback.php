<?php

session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';
if ((isset($_GET['api']) && $_GET['api'] === 'true')) {
    header('Content-Type: application/json');
    include '../../includes/db_connect.php';
}

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['api']) && $_GET['api'] === 'true')) {
    header('Content-Type: application/json');
    include '../../includes/db_connect.php';

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'];
        $message = $data['message'];

        $sql = "INSERT INTO feedback (user_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $message);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Feedback submitted"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        $stmt->close();
        exit;
    }

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
            $stmt->bind_param("i", $user_id);
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
            $stmt->bind_param("i", $user_id);
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
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.png";
}

// Set variables for header
$pageTitle = "Fit and Brawl - Feedback";
$currentPage = "feedback";
$additionalCSS = ["../css/pages/feedback.css?=v2"];

// Include header
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main>

        <div class="feedback-container">
            <div class="feedback-section" id="feedback-section">

            </div>
        </div>
        <div class="feedback-button">
            <a href="feedback-form.php" class="floating-btn">
                Share your feedback!
            </a>

        </div>
    </main>

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

    <script src="../js/feedback.js"></script>
</body>

</html>
