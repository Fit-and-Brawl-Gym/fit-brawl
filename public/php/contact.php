<?php
session_start();

require_once '../../includes/db_connect.php';

// Check membership status for header
require_once '../../includes/membership_check.php';

require_once '../../includes/session_manager.php';



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

// Initialize session manager
SessionManager::initialize();

$status = "";
$fnameErr = $lnameErr = $emailErr = $phoneErr = $messageErr = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'] ?? null;
    $fname = test_input($_POST['first-name'] ?? '');
    $lname = test_input($_POST['last-name'] ?? '');
    $email = test_input($_POST['email'] ?? '');
    $phoneNum = test_input($_POST['phone'] ?? '');
    $message = test_input($_POST['message'] ?? '');

    if (empty($fname)) {
        $fnameErr = "First name is required";
    } else {
        if (!preg_match("/^[a-zA-Z-' ]*$/", $fname)) {
            $nameErr = "Only letters and white space allowed";
        }
    }
    if (empty($lname)) {
        $lnameErr = "Last name is required";
    } else {
        if (!preg_match("/^[a-zA-Z-' ]*$/", $lname)) {
            $nameErr = "Only letters and white space allowed";
        }
    }
    if (empty($email)) {
        $emailErr = "Email is required";
    } else {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }
    if (empty($phoneNum)) {
        $phoneErr = "Phone number is required";
    } else {
        if (!preg_match("/^[0-9]{10,15}$/", $phoneNum)) {
            $phoneErr = "Invalid phone number format";
        }
    }
    if (empty($message)) {
        $messageErr = "Message is required";

    }

    if (empty($fnameErr) && empty($lnameErr) && empty($emailErr) && empty($phoneErr) && empty($messageErr)) {
        $sql = "INSERT INTO contact (first_name, last_name, email, phone_number, message, date_submitted)
            VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $fname, $lname, $email, $phoneNum, $message);

        if ($stmt->execute()) {
            $status = "Your message has been sent successfully.";

            $fname = $lname = $email = $phoneNum = $message = '';
        } else {
            $status = "Database error: " . $stmt->error;
        }
    }
}

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Set variables for header
$pageTitle = "Fit and Brawl - Contact";
$currentPage = "contact";
$additionalCSS = ["../css/pages/contact.css", "../css/components/form.css"];

// Include header
require_once '../../includes/header.php';
?>
<!--Main-->
<main>
    <div class="bg"></div>
    <div class="glowing-bg"></div>
    <div class="contact-container">
        <div class="contact-section">
            <div class="contact-header">
                <h1>Contact Us</h1>
            </div>
            <form method="post" class="contact-form" id="contactForm">
                <div class="contact-details">
                    <?php if (!empty($status)): ?>
                        <div class="success"><?= htmlspecialchars($status) ?></div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name</label>
                            <input type="text" id="first-name" name="first-name" placeholder="Excel"
                                value="<?= htmlspecialchars($fname ?? '') ?>">
                            <?php if (!empty($fnameErr)): ?>
                                <div class="status"><?= htmlspecialchars($fnameErr) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="last-name">Last Name</label>
                            <input type="text" id="last-name" name="last-name" placeholder="Bondoc"
                                value="<?= htmlspecialchars($lname ?? '') ?>">
                            <?php if (!empty($lnameErr)): ?>
                                <div class="status"><?= htmlspecialchars($lnameErr) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="excelpogi@gmail.com"
                                value="<?= htmlspecialchars($email ?? '') ?>">
                            <?php if (!empty($emailErr)): ?>
                                <div class="status"><?= htmlspecialchars($emailErr) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="09123456789"
                                value="<?= htmlspecialchars($phoneNum ?? '') ?>">
                            <?php if (!empty($phoneErr)): ?>
                                <div class="status"><?= htmlspecialchars($phoneErr) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea id="message" name="message" placeholder="Leave us a message..."
                            value="<?= htmlspecialchars($message ?? '') ?>"></textarea>
                        <?php if (!empty($messageErr)): ?>
                            <div class="status"><?= htmlspecialchars($messageErr) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="submit-button">
                        <button type="submit">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
