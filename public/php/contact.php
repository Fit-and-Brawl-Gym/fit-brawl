<?php
session_start();

require_once __DIR__ . '/../../includes/db_connect.php';

// Check membership status for header
require_once __DIR__ . '/../../includes/membership_check.php';

require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/config.php';



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

// Check if user is logged in and get their info
$isLoggedIn = isset($_SESSION['user_id']);
$userFullName = '';
$userEmail = '';

if ($isLoggedIn) {
    $userFullName = $_SESSION['name'] ?? '';
    $userEmail = $_SESSION['email'] ?? '';
}

$status = "";
$statusType = "";
$fname = "";
$lname = "";
$email = "";
$phoneNum = "";
$message = "";
$fnameErr = $lnameErr = $emailErr = $phoneErr = $messageErr = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'] ?? null;

    // If logged in, use session data for name and email
    if ($isLoggedIn) {
        $fullName = $userFullName;
        // Split the username into first and last name (simple approach)
        $nameParts = explode(' ', $fullName, 2);
        $fname = $nameParts[0] ?? '';
        $lname = $nameParts[1] ?? '';
        $email = $userEmail;
    } else {
        // For non-logged in users, get from form
        $fname = test_input($_POST['first-name'] ?? '');
        $lname = test_input($_POST['last-name'] ?? '');
        $email = test_input($_POST['email'] ?? '');
    }

    $phoneNum = test_input($_POST['phone'] ?? '');
    $message = test_input($_POST['message'] ?? '');

    // Validation
    if (!$isLoggedIn) {
        if (empty($fname)) {
            $fnameErr = "First name is required";
        } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $fname)) {
            $fnameErr = "Only letters and white space allowed";
        }

        if (empty($lname)) {
            $lnameErr = "Last name is required";
        } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $lname)) {
            $lnameErr = "Only letters and white space allowed";
        }

        if (empty($email)) {
            $emailErr = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    if (empty($phoneNum)) {
        $phoneErr = "Phone number is required";
    } elseif (!preg_match("/^9[0-9]{9}$/", $phoneNum)) {
        $phoneErr = "Phone number must start with 9 and be 10 digits";
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
            $status = "Your message has been sent successfully! We'll get back to you soon.";
            $statusType = "success";
            $phoneNum = $message = '';
            if (!$isLoggedIn) {
                $fname = $lname = $email = '';
            }
        } else {
            $status = "Something went wrong. Please try again later.";
            $statusType = "error";
        }
        $stmt->close();
    } else {
        $statusType = "error";
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
$additionalCSS = [PUBLIC_PATH . "/css/pages/contact.css"];

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>
<!--Main-->
<main class="contact-page">
    <div class="page-header-section">
        <div class="page-header-content">
            <div class="page-header-text">
                <h1 class="page-title">Get In <span class="highlight">Touch</span></h1>
                <p class="page-subtitle">Have questions or feedback? We'd love to hear from you. <br> Send us a message
                    and we'll respond as soon as possible.</p>
            </div>
        </div>
    </div>

    <div class="contact-container">
        <div class="contact-content">
            <!-- Contact Info Cards -->
            <div class="contact-info-section">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Visit Us</h3>
                        <p>1832 Oroquieta Rd, Santa Cruz, Manila<br>1008 Metro Manila<br><strong>Mon-Sun:</strong> 7AM -
                            12NN</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Call Us</h3>
                        <p>+63 912 345 6789</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email Us</h3>
                        <p>fitxbrawl.gym@gmail.com</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-section">
                <div class="form-header">
                    <h2>Send Us a Message</h2>
                    <?php if ($isLoggedIn): ?>
                        <p class="welcome-text">Welcome back, <strong><?= htmlspecialchars($userFullName) ?></strong>!</p>
                    <?php else: ?>
                        <p>Fill out the form below and we'll get back to you shortly.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($status)): ?>
                    <div class="alert alert-<?= $statusType ?>">
                        <i class="fas fa-<?= $statusType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($status) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="contact-form" id="contactForm">
                    <?php if (!$isLoggedIn): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first-name">
                                    <i class="fas fa-user"></i>
                                    First Name <span class="required">*</span>
                                </label>
                                <input type="text" id="first-name" name="first-name" placeholder="Enter your first name"
                                    value="<?= htmlspecialchars($fname) ?>" class="<?= !empty($fnameErr) ? 'error' : '' ?>">
                                <?php if (!empty($fnameErr)): ?>
                                    <span class="error-message"><?= htmlspecialchars($fnameErr) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="last-name">
                                    <i class="fas fa-user"></i>
                                    Last Name <span class="required">*</span>
                                </label>
                                <input type="text" id="last-name" name="last-name" placeholder="Enter your last name"
                                    value="<?= htmlspecialchars($lname) ?>" class="<?= !empty($lnameErr) ? 'error' : '' ?>">
                                <?php if (!empty($lnameErr)): ?>
                                    <span class="error-message"><?= htmlspecialchars($lnameErr) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i>
                                    Email Address <span class="required">*</span>
                                </label>
                                <input type="email" id="email" name="email" placeholder="your.email@example.com"
                                    value="<?= htmlspecialchars($email) ?>" class="<?= !empty($emailErr) ? 'error' : '' ?>">
                                <?php if (!empty($emailErr)): ?>
                                    <span class="error-message"><?= htmlspecialchars($emailErr) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="phone">
                                    <i class="fas fa-phone"></i>
                                    Phone Number <span class="required">*</span>
                                </label>
                                <div class="phone-input-wrapper <?= !empty($phoneErr) ? 'error' : '' ?>">
                                    <span class="phone-prefix">+63</span>
                                    <input type="tel" id="phone" name="phone" placeholder="9123456789"
                                        value="<?= htmlspecialchars($phoneNum) ?>" maxlength="10" pattern="9[0-9]{9}"
                                        title="Phone number must start with 9 and be 10 digits">
                                </div>
                                <?php if (!empty($phoneErr)): ?>
                                    <span class="error-message"><?= htmlspecialchars($phoneErr) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                Phone Number <span class="required">*</span>
                            </label>
                            <div class="phone-input-wrapper <?= !empty($phoneErr) ? 'error' : '' ?>">
                                <span class="phone-prefix">+63</span>
                                <input type="tel" id="phone" name="phone" placeholder="9123456789"
                                    value="<?= htmlspecialchars($phoneNum) ?>" maxlength="10" pattern="9[0-9]{9}"
                                    title="Phone number must start with 9 and be 10 digits">
                            </div>
                            <?php if (!empty($phoneErr)): ?>
                                <span class="error-message"><?= htmlspecialchars($phoneErr) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="message">
                            <i class="fas fa-comment-dots"></i>
                            Your Message <span class="required">*</span>
                        </label>
                        <textarea id="message" name="message" rows="6" placeholder="Tell us what's on your mind..."
                            class="<?= !empty($messageErr) ? 'error' : '' ?>"><?= htmlspecialchars($message) ?></textarea>
                        <?php if (!empty($messageErr)): ?>
                            <span class="error-message"><?= htmlspecialchars($messageErr) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
// Phone number input validation and formatting
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');
    
    if (phoneInput) {
        // Only allow numbers
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove all non-digit characters
            value = value.replace(/\D/g, '');
            
            // Ensure it starts with 9
            if (value.length > 0 && value[0] !== '9') {
                value = '9' + value;
            }
            
            // Limit to 10 digits
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            e.target.value = value;
        });
        
        // Prevent non-numeric keypresses
        phoneInput.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/[0-9]/.test(char)) {
                e.preventDefault();
            }
        });
        
        // Auto-add 9 if empty and user starts typing
        phoneInput.addEventListener('focus', function(e) {
            if (e.target.value === '') {
                e.target.value = '9';
            }
        });
        
        // Validate on blur
        phoneInput.addEventListener('blur', function(e) {
            const value = e.target.value;
            if (value === '9' || value === '') {
                e.target.value = '';
            } else if (value.length < 10) {
                e.target.setCustomValidity('Phone number must be 10 digits starting with 9');
            } else {
                e.target.setCustomValidity('');
            }
        });
        
        // Clear custom validity on input
        phoneInput.addEventListener('input', function(e) {
            e.target.setCustomValidity('');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
