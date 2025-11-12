<?php
session_start();
require_once '../../includes/db_connect.php';

// Check if user came from verification process
if(!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = test_input($_POST['new_password']);
    $confirm_password = test_input($_POST['confirm_password']);
    // Password Validation Function (same rules as sign-up)
    function validatePassword($password) {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[!@#$%^&*?]/', $password)) {
            $errors[] = "Password must contain at least one special character (!@#$%^&*?)";
        }

        return $errors;
    }

    // Check passwords match first
    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Validate password requirements
        $passwordErrors = validatePassword($new_password);
        if (!empty($passwordErrors)) {
            $error = implode("<br>", $passwordErrors);
        } else {
            // Ensure the new password is not the same as the current password
            $email = $_SESSION['reset_email'];
            $getStmt = $conn->prepare("SELECT password FROM users WHERE email = ? LIMIT 1");
            $getStmt->bind_param("s", $email);
            if ($getStmt->execute()) {
                $res = $getStmt->get_result();
                $row = $res->fetch_assoc();
                $currentHash = $row['password'] ?? null;
            } else {
                $currentHash = null;
            }

            if ($currentHash && password_verify($new_password, $currentHash)) {
                $error = "New password must be different from your current password.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $email);

                if ($stmt->execute()) {
                    // Clear reset email session
                    unset($_SESSION['reset_email']);
                    $_SESSION['password_changed'] = true;
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Error updating password. Please try again.";
                }
            }
        }
    }
}
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>

<?php
// Set page variables for header
$pageTitle = "Change Password - Fit and Brawl";
$currentPage = "";
// Include password validation JS to reuse the same client-side checks as sign-up
$additionalCSS = [
    // Keep page-specific CSS but also include the sign-up styles so the password modal matches exactly
    '../css/pages/change-password.css',
    '../css/pages/sign-up.css?v=5',
    '../css/components/terms-modal.css'
];
$additionalJS = [
    '../js/password-validation.js'
];

// Include header
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main class="change-password-main">
    <section class="change-password-hero">
        <div class="hero-content">
            <div class="hero-line"></div>
            <h1 class="hero-title">
                STRONG TODAY <span class="yellow"> STRONGER </span> TOMORROW
            </h1>
            <div class="hero-underline"></div>
        </div>

        <div class="change-password-modal">
            <div class="modal-header">
                <h2>Change your password</h2>
            </div>

            <form method="POST" class="change-password-form">
                <h3>A LITTLE STEP BACK BEFORE THE BEST VERSION OF YOU!</h3>

                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- New password -->
                <div class="input-group password-input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" id="passwordInput" name="new_password" placeholder="New Password"
                           autocomplete="new-password" autocapitalize="off" autocorrect="off"
                           spellcheck="false" data-form-type="other" required>
                    <i class="fas fa-eye eye-toggle" id="togglePassword"></i>
                </div>

                <!-- Password Requirements Modal (reused from sign-up) -->
                <div class="password-requirements-modal" id="passwordRequirementsModal">
                    <div class="password-requirements-header">
                        <h4>Password Requirements</h4>
                    </div>
                    <div class="password-requirements-list">
                        <div class="requirement-item" id="req-length">
                            <span class="requirement-icon">✗</span>
                            <span class="requirement-text">At least 8 characters</span>
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

                <!-- Confirm new password -->
                <div class="input-group password-input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" id="confirmPasswordInput" name="confirm_password" placeholder="Re-enter New Password"
                           autocomplete="new-password" autocapitalize="off" autocorrect="off"
                           spellcheck="false" data-form-type="other" required>
                    <i class="fas fa-eye eye-toggle" id="toggleConfirmPassword"></i>
                </div>

                <!-- Password Match Message -->
                <div class="password-match-message" id="passwordMatchMessage"></div>

                <button type="submit" class="change-password-btn">Change Password</button>
                <a href="user_profile.php" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </section>
    </main>

<?php
// Clear OTP timer from sessionStorage if redirected from verification
if (isset($_SESSION['clear_otp_timer'])) {
    echo '<script>sessionStorage.removeItem("otpExpiryTime");</script>';
    unset($_SESSION['clear_otp_timer']);
}

require_once '../../includes/footer.php';
?>
