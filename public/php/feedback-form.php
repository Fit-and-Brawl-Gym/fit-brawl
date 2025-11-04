<?php
session_start();

require_once '../../includes/db_connect.php';


if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in"]); //TODO: Show Modal instead of text
    exit;
}


$status = '';
$feedbackSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
   $user_id = test_input($_SESSION['user_id']);
    $username = test_input($_POST['name'] ?? '');
    $email = test_input($_POST['email'] ?? '');
    $message = test_input($_POST['message'] ?? '');
    $index = $_SESSION['anonymous_index'] ?? 1;



    $sql = "SELECT avatar FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
       if (empty($username )){
        $username = "Anonymous $index";
        $user_avatar = "../../images/account-icon.svg";
    } else{
        // Use default icon if avatar is empty or default
        if (empty($row['avatar']) || $row['avatar'] === 'default-avatar.png' || $row['avatar'] === '') {
            $user_avatar = "../../images/account-icon.svg";
        } else {
            $user_avatar = $row['avatar'];
        }
    }
    if (empty($email)){
        $email = "anon@gmail.com";
    }

    if (empty($message)) {
        echo json_encode(["status" => "error", "message" => "Message cannot be empty"]);
        exit;
    }
        $_SESSION['anonymous_index'] = $index + 1;
        $sql = "INSERT INTO feedback (user_id, username, email, avatar, message, date)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $username, $email, $user_avatar, $message);

        if ($stmt->execute()) {
            $feedbackSuccess = true;
        } else {
           $status = "Error: " . $stmt->error;
        }
    } else {
        $status = "Error: User not found.";
    }
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
require_once '../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$pageTitle = "Feedback Form - Fit and Brawl";
$currentPage = "feedback_form";
$additionalCSS = ['../css/pages/feedback-form.css', '../css/components/form.css'];
$additionalJS = ['../js/feedback-form.js'];
require_once '../../includes/header.php';
?>

     <!--Main-->
    <main>
        <div class="bg"></div>
            <div class="glowing-bg"></div>
        <div class="contact-container">
            <div class="contact-section">
                <div class="contact-header">
                    <h1>Share your feedback</h1>
                </div>
                  <form method="post" class="feedback-form" id="feedbackForm">
                      <div class="contact-details">
                      <?php if(!empty($status) && !$feedbackSuccess) : ?>
                          <div class="status"><?= htmlspecialchars($status) ?></div>
                      <?php endif; ?>
                      <div class="form-row">
                          <div class="form-group">
                              <label for="name"></label>
                              <input type="text" id="name" name="name" placeholder="Name (Optional)">
                          </div>
                          <div class="form-group">
                              <label for="email"></label>
                              <input type="text" id="email" name="email" placeholder="Email (Optional)">
                          </div>
                      </div>
                      <div class="form-group">
                          <textarea id="message" name="message" placeholder="Leave us a message..." required></textarea>
                      </div>
                      <div class="buttons">
                          <a href="feedback.php">Cancel</a>
                          <button type="submit" name="feedback" class="feedback-btn">Submit</button>
                      </div>
                      </div>
                  </form>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="success-modal" data-show="<?= $feedbackSuccess ? 'true' : 'false' ?>">
        <div class="success-content">
            <div class="success-header">
                <i class="fas fa-check-circle"></i>
                <h3>Thank You<span class="exclamation">!</span> Your feedback has been submitted.</h3>
            </div>
            <p class="success-message"> You will be redirected to the feedback page in <span id="countdown">5</span> seconds.</p>
            <div class="success-buttons">
                <button class="success-btn" id="redirectNow">
                    <i class="fas fa-arrow-right"></i> Go Now
                </button>
            </div>
        </div>
    </div>

<?php require_once '../../includes/footer.php'; ?>
