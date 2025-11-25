<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';  // Add config for BASE_PATH
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/user_id_generator.php'; // Add ID generator
require_once __DIR__ . '/../../includes/password_policy.php';
require_once __DIR__ . '/../../includes/csp_nonce.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';
require_once __DIR__ . '/../../includes/encryption.php'; // Add encryption support
require_once __DIR__ . '/../../includes/mail_config.php'; // Use shared mail config with configureMailerSMTP
require_once __DIR__ . '/../../includes/email_queue.php'; // Fast email queue
include_once __DIR__ . '/../../includes/env_loader.php';
loadEnv(__DIR__ . '/../../.env');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';
// Email template helper (adds header/footer and AltBody)
require_once __DIR__ . '/../../includes/email_template.php';

// Initialize email queue
EmailQueue::init($conn);

// Generate CSP nonces for this request
CSPNonce::generate();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        $_SESSION['register_error'] = "Your session expired. Please resubmit the form.";
        header("Location: sign-up.php");
        exit();
    }

    // Rate limit signup attempts by IP
    $signupIdentifier = 'signup|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $blockInfo = isSignupBlocked($conn, $signupIdentifier, 5, 900);

    if ($blockInfo['blocked']) {
        $retryAfterMinutes = max(1, ceil($blockInfo['retry_after'] / 60));
        $_SESSION['register_error'] = "Too many signup attempts. Please wait {$retryAfterMinutes} minute" . ($retryAfterMinutes === 1 ? '' : 's') . " before trying again.";
        header("Location: sign-up.php");
        exit();
    }

    $name = test_input($_POST['name']);
    $email = test_input($_POST['email']);
    $password_input = test_input($_POST['password'] ?? '');
    $confirm_password = test_input($_POST['confirm_password'] ?? '');

    //Validate inputs
    if (empty($name) || empty($email) || empty($password_input) || empty($confirm_password)) {
        logSignupAttempt($conn, $signupIdentifier);
        $_SESSION['register_error'] = "All fields are required.";
        header("Location: sign-up.php");
        exit();
    }

    //Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logSignupAttempt($conn, $signupIdentifier);
        $_SESSION['register_error'] = "Please enter a valid email address.";
        header("Location: sign-up.php");
        exit();
    }

    //Check if email domain exists
    $emailDomain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($emailDomain, "MX")) {
        logSignupAttempt($conn, $signupIdentifier);
        $_SESSION['register_error'] = "Invalid email domain. Please use a real email address.";
        header("Location: sign-up.php");
        exit();
    }

    //Check password match
    if ($password_input !== $confirm_password) {
        logSignupAttempt($conn, $signupIdentifier);
        $_SESSION['register_error'] = "Passwords do not match.";
        header("Location: sign-up.php");
        exit();
    }

    // Validate password requirements
    $passwordErrors = PasswordPolicy::validate($password_input);
    if (!empty($passwordErrors)) {
        logSignupAttempt($conn, $signupIdentifier);
        $_SESSION['register_error'] = implode("\n", $passwordErrors);
        header("Location: sign-up.php");
        exit();
    }

    //Hash password
    $password = password_hash($password_input, PASSWORD_DEFAULT);
    $role = "member";

    //Check for duplicate username or email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $name, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        logSignupAttempt($conn, $signupIdentifier);
        $_SESSION['register_error'] = "Username or email already exists.";
        header("Location: sign-up.php");
        exit();
    }

    $verificationToken = bin2hex(random_bytes(32));

    // Start transaction to prevent duplicate IDs during concurrent signups
    $conn->begin_transaction();

    try {
        // Generate formatted user ID based on role (with FOR UPDATE lock)
        $userId = generateFormattedUserId($conn, $role);

        // Encrypt email before storing
        $encryptedEmail = Encryption::encrypt($email);

        // Insert user with verification token and formatted ID
        $insertQuery = $conn->prepare("
            INSERT INTO users (id, username, email, email_encrypted, password, role, verification_token, is_verified)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $insertQuery->bind_param("sssssss", $userId, $name, $email, $encryptedEmail, $password, $role, $verificationToken);

        if (!$insertQuery->execute()) {
            throw new Exception("Failed to insert user");
        }

        // Commit transaction
        $conn->commit();

        // Build verification URL based on environment
        // For localhost: http://localhost/fit-brawl/public/php/verify-email.php
        // For production: https://domain.com/php/verify-email.php (DocumentRoot is /public)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];

        // On production, PUBLIC_PATH is empty (DocumentRoot is already /public)
        // On localhost, PUBLIC_PATH is /fit-brawl/public
        if (ENVIRONMENT === 'production') {
            // Production: just /php/verify-email.php
            $verificationLink = $protocol . '://' . $host . '/php/verify-email.php?token=' . $verificationToken;
        } else {
            // Localhost: /fit-brawl/public/php/verify-email.php
            $verificationLink = $protocol . '://' . $host . PUBLIC_PATH . '/php/verify-email.php?token=' . $verificationToken;
        }

        $mail = new PHPMailer(true);
        try {
            // Use email queue for faster response (email sends in background)
            $html = "<h2>Welcome to FitXBrawl, " . htmlspecialchars($name) . "!</h2>"
                . "<p>Click the link below to verify your email:</p>"
                . "<p><a href='" . htmlspecialchars($verificationLink) . "'>" . htmlspecialchars($verificationLink) . "</a></p>"
                . "<p>This link will confirm your account registration.</p>";

            // Queue email for background sending (returns immediately)
            EmailQueue::queue($email, 'Verify Your Email - FitXBrawl', $html, $name, null, 1);

            $_SESSION['success_message'] = "Account created! Please check your email to verify your account.";
            $_SESSION['verification_email'] = $email; // Store email for resend functionality
            header("Location: sign-up.php");
            exit();

        } catch (Exception $e) {
            $_SESSION['register_error'] = "Account created but verification email could not be sent. Error: " . $e->getMessage();
            header("Location: sign-up.php");
            exit();
        }

    } catch (Exception $e) {
        // Rollback transaction on any error
        $conn->rollback();
        $_SESSION['register_error'] = "Registration failed. Please try again. Error: " . $e->getMessage();
        header("Location: sign-up.php");
        exit();
    }
}
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatAlertText($text) {
    return nl2br(htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'));
}

$pageTitle = "Sign Up - Fit and Brawl";
$currentPage = "sign_up";
?>

<?php
// Set page variables for header
$pageTitle = "Sign Up - Fit and Brawl";
$currentPage = "signup";
$additionalCSS = [
    '../css/components/alert.css?v=' . time(),
    '../css/pages/sign-up.css?v=5',
    '../css/components/terms-modal.css'
];
// No need to add hamburger.js - it's already loaded as hamburger-menu.js in header.php
$additionalJS = [
    '../js/password-validation.js',
    '../js/signup-error-handler.js',
    '../js/resend-verification.js'
];

$signupErrorMessage = $_SESSION['register_error'] ?? null;
$signupSuccessMessage = $_SESSION['success_message'] ?? null;
$signupVerificationEmail = $_SESSION['verification_email'] ?? null;

if (isset($_SESSION['register_error'])) {
    unset($_SESSION['register_error']);
}
if (isset($_SESSION['success_message'])) {
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['verification_email'])) {
    unset($_SESSION['verification_email']);
}

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>

    <!--Main-->
    <main class="signup-main">
        <section class="signup-hero">
            <div class="hero-content">
                <div class="hero-line"></div>
                <h1 class="hero-title">
                    A <span class="yellow">STRONG BODY</span> STARTS<br>
                    WITH A <span class="yellow">STRONG MIND</span>
                </h1>
                <div class="hero-underline"></div>
            </div>

            <div class="signup-modal">
                <div class="modal-header">
                    <h2>Create an account</h2>
                </div>

                <form action="sign-up.php" method="post" class="signup-form" id="signupForm">
                    <?= CSRFProtection::getTokenField(); ?>
                    <!-- Error/Success Messages - Displayed prominently at the top -->
                    <div id="messageContainer" class="message-container">
                        <?php if (!empty($signupErrorMessage)): ?>
                            <div class="alert-box alert-box--error" id="errorMessageBox" role="alert">
                                <div class="alert-icon" aria-hidden="true">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="alert-content">
                                    <p class="alert-title">We couldn't create your account</p>
                                    <p class="alert-text"><?= formatAlertText($signupErrorMessage); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($signupSuccessMessage)): ?>
                            <div class="alert-box alert-box--success" id="successMessageBox" role="status">
                                <div class="alert-icon" aria-hidden="true">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="alert-content">
                                    <p class="alert-title">Account created</p>
                                    <p class="alert-text"><?= formatAlertText($signupSuccessMessage); ?></p>

                                    <?php if (!empty($signupVerificationEmail)): ?>
                                        <div class="alert-actions">
                                            <button type="button" class="resend-verification-btn" id="resendVerificationBtn" data-email="<?= htmlspecialchars($signupVerificationEmail); ?>">
                                                <i class="fas fa-envelope"></i>
                                                Resend Verification Email
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h3>ARE YOU READY TO BECOME THE BETTER VERSION OF YOURSELF?</h3>

                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" placeholder="Name" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>

                    <div class="input-group password-input-group">
                        <i class="fas fa-key"></i>
                        <input type="password" id="passwordInput" name="password" placeholder="Password"
                               class="password-field"
                               autocomplete="new-password" autocapitalize="off" autocorrect="off"
                               spellcheck="false" required>
                        <i class="fas fa-eye eye-toggle" id="togglePassword"></i>

                        <!-- Password Requirements Modal -->
                        <div class="password-requirements-modal" id="passwordRequirementsModal">
                            <div class="password-requirements-header">
                                <h4>Password Requirements</h4>
                            </div>
                            <div class="password-requirements-list">
                            <div class="requirement-item" id="req-length">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">At least 12 characters</span>
                            </div>
                            <div class="requirement-item" id="req-uppercase">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">One uppercase letter</span>
                            </div>
                            <div class="requirement-item" id="req-lowercase">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">One lowercase letter</span>
                            </div>
                            <div class="requirement-item" id="req-number">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">One number</span>
                            </div>
                            <div class="requirement-item" id="req-special">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">One special character (!@#$%^&*?)</span>
                            </div>
                        </div>
                        <div class="strength-indicator" id="strengthIndicator">
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="strengthBarFill"></div>
                            </div>
                            <span class="strength-text" id="strengthText">Strength: Weak</span>
                        </div>
                    </div>
                    </div>

                    <div class="input-group password-input-group">
                        <i class="fas fa-key"></i>
                        <input type="password" id="confirmPasswordInput" name="confirm_password" placeholder="Confirm Password"
                               class="password-field"
                               autocomplete="new-password" autocapitalize="off" autocorrect="off"
                               spellcheck="false" required>
                        <i class="fas fa-eye eye-toggle" id="toggleConfirmPassword"></i>
                    </div>

                    <!-- Password Match Message -->
                    <div class="password-match-message" id="passwordMatchMessage"></div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="terms-checkbox" name="terms" required>
                            <span class="checkmark"></span>
                            Agree to&nbsp;<a href="#" class="terms-link" id="terms-link">Terms and Conditions</a>
                        </label>
                    </div>

                    <button type="submit" name="signup" class="signup-btn">Sign up</button>

                    <p class="login-link">
                        Already have an account? <a href="login.php">Sign in here.</a>
                    </p>
                </form>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

    <!-- Terms and Conditions Modal -->
    <div class="terms-modal-overlay">
        <div class="terms-modal-container">
            <div class="terms-modal-header">
                <h2>Terms and Condition</h2>
                <button class="terms-close-btn">&times;</button>
            </div>

            <div class="terms-modal-body">
                <div class="terms-sidebar">
                    <!-- Mobile Dropdown Navigation -->
                    <div class="terms-mobile-nav" data-active="#use-of-website">
                        <button type="button" class="terms-dropdown-trigger" aria-expanded="false">
                            <span class="terms-dropdown-label">1. Use of Our Website</span>
                            <span class="terms-dropdown-icon" aria-hidden="true"></span>
                        </button>
                        <ul class="terms-dropdown-list" hidden>
                            <li><button type="button" data-target="#use-of-website">1. Use of Our Website</button></li>
                            <li><button type="button" data-target="#booking-scheduling">2. Booking and Scheduling</button></li>
                            <li><button type="button" data-target="#payments-fees">3. Payments and Fees</button></li>
                            <li><button type="button" data-target="#cancellations-refunds">4. Cancellations and Refunds</button></li>
                            <li><button type="button" data-target="#health-safety">5. Health and Safety</button></li>
                            <li><button type="button" data-target="#memberships">6. Memberships and Subscriptions</button></li>
                            <li><button type="button" data-target="#intellectual-property">7. Intellectual Property</button></li>
                            <li><button type="button" data-target="#limitation-liability">8. Limitation of Liability</button></li>
                            <li><button type="button" data-target="#privacy-policy">9. Privacy Policy</button></li>
                            <li><button type="button" data-target="#changes-terms">10. Changes to These Terms</button></li>
                        </ul>
                    </div>

                    <!-- Desktop Sidebar Navigation -->
                    <nav class="terms-desktop-nav">
                        <ul>
                            <li><a href="#use-of-website" class="active">1. Use of Our Website</a></li>
                            <li><a href="#booking-scheduling">2. Booking and Scheduling</a></li>
                            <li><a href="#payments-fees">3. Payments and Fees</a></li>
                            <li><a href="#cancellations-refunds">4. Cancellations and Refunds</a></li>
                            <li><a href="#health-safety">5. Health and Safety</a></li>
                            <li><a href="#memberships">6. Memberships and Subscriptions</a></li>
                            <li><a href="#intellectual-property">7. Intellectual Property</a></li>
                            <li><a href="#limitation-liability">8. Limitation of Liability</a></li>
                            <li><a href="#privacy-policy">9. Privacy Policy</a></li>
                            <li><a href="#changes-terms">10. Changes to These Terms</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="terms-content">
                    <p class="terms-last-updated">Last Updated: October 7, 2025</p>
                    <p class="terms-intro">Welcome to Fit and Brawl! These Terms and Conditions ("Terms") govern your use of our website, <a href="<?= 'index.php' ?>" class="terms-link"> fitxbrawl.com</a>  and all related services offered by Fit and Brawl. By accessing or using our website, scheduling sessions, or purchasing services, you agree to comply with these Terms.</p>

                    <h3 id="use-of-website">1. Use of Our Website</h3>
                    <ul>
                        <li>You must be at least 18 years old (or have parental consent) to create an account and use our online services.</li>
                        <li>You agree to provide accurate, complete, and current information when creating an account or booking sessions.</li>
                        <li>You are responsible for maintaining the confidentiality of your account and password.</li>
                    </ul>

                    <h3 id="booking-scheduling">2. Booking and Scheduling</h3>
                    <ul>
                        <li>You may schedule workouts, classes, or personal training sessions through our website.</li>
                        <li>All bookings are subject to availability and confirmation by our staff.</li>
                        <li>You agree to arrive on time for your scheduled sessions. Late arrivals may result in reduced session time.</li>
                    </ul>

                    <h3 id="payments-fees">3. Payments and Fees</h3>
                    <ul>
                        <li>All payments must be made through our secure online payment system.</li>
                        <li>Prices for services are listed on our website and may change without prior notice.</li>
                        <li>Payments are non-refundable except as required by law or under specific promotional terms.</li>
                    </ul>

                    <h3 id="cancellations-refunds">4. Cancellations and Refunds</h3>
                    <ul>
                        <li>You may cancel or reschedule a session at least 24 hours before the appointment time.</li>
                        <li>Late cancellations or "no-shows" may result in a cancellation fee or forfeiture of the session.</li>
                        <li>Refund requests will be reviewed and processed at our discretion.</li>
                    </ul>

                    <h3 id="health-safety">5. Health and Safety</h3>
                    <ul>
                        <li>By using our services, you confirm that you are physically fit and have consulted a medical professional if needed.</li>
                        <li>You agree to follow all gym rules, safety guidelines, and instructions from our trainers.</li>
                        <li>We are not responsible for any injuries, accidents, or health issues that occur during participation in our programs.</li>
                    </ul>

                    <h3 id="memberships">6. Memberships and Subscriptions</h3>
                    <ul>
                        <li>If you purchase a membership, you agree to the terms of your selected plan (duration, renewal, and cancellation policies).</li>
                        <li>Membership fees are billed according to the plan you choose.</li>
                        <li>Automatic renewals may apply unless you cancel before the renewal date.</li>
                    </ul>

                    <h3 id="intellectual-property">7. Intellectual Property</h3>
                    <ul>
                        <li>All content on this website (text, images, logos, videos, etc.) is the property of Fit and Brawl and is protected by copyright law.</li>
                        <li>You may not reproduce, distribute, or modify any content without our written consent.</li>
                    </ul>

                    <h3 id="limitation-liability">8. Limitation of Liability</h3>
                    <ul>
                        <li>Fit and Brawl is not liable for any indirect, incidental, or consequential damages arising from your use of our website or services.</li>
                        <li>We do not guarantee that our website will always be available, error-free, or secure.</li>
                    </ul>

                    <h3 id="privacy-policy">9. Privacy Policy</h3>
                    <ul>
                        <li>We respect your privacy and will not share your data with third parties except as required by law.</li>
                    </ul>

                    <h3 id="changes-terms">10. Changes to These Terms</h3>
                    <ul>
                        <li>We may update these Terms from time to time.</li>
                        <li>Any changes will be posted on this page with an updated "Last Updated" date.</li>
                        <li>Continued use of our website or services after such changes means you accept the revised Terms.</li>
                    </ul>
                </div>
            </div>

            <div class="terms-modal-footer">
                <button class="terms-decline-btn">Decline</button>
                <button class="terms-accept-btn">Accept</button>
            </div>
        </div>
    </div>

    <script src="../js/terms-modal.js"></script>
</body>
</html>
