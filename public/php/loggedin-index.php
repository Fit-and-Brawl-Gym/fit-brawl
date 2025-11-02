<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['email']) && isset($_SESSION['remember_password'])) {
    $token = $_SESSION['remember_password'];

    $result = $conn->query("SELECT * FROM remember_password");
    while ($row = $result->fetch_assoc()) {
        if (password_verify($token, $row['token_hash'])) {
            $stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmtUser->bind_param("i", $row['user_id']);
            $stmtUser->execute();
            $user = $stmtUser->get_result()->fetch_assoc();

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];
            }
            break;
        }
    }
}

// Redirect non-logged-in users
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit;
}

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
// Set avatar source
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

// Set variables for header
$pageTitle = "Homepage - Fit and Brawl";
$currentPage = "home";
$additionalCSS = ["../css/pages/loggedin-homepage.css"];

// Include header
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main>
        <section class="homepage-hero">
            <div class="hero-content">
                <div class="hero-underline top-line"></div>
                <h1>
                    BUILD A <span class="yellow">BODY</span> THAT<span class="apostrophe">&#39;</span>S<br>
                    BUILT FOR <span class="yellow">BATTLE</span>
                </h1>
                <p class="hero-sub"><span class="sub-underline">Ready for the battle?</span></p>
                <a href="<?= htmlspecialchars($membershipLink) ?>" class="hero-btn">View Schedule</a>
            </div>
        </section>
    </main>

<?php require_once '../../includes/footer.php'; ?>
