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

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

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
    } else {
        $error = "Passwords do not match!";
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
$additionalCSS = ['../css/pages/change-password.css'];

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

                <!-- New password -->
                <div class="input-group password-group">
                    <div class="icon-left">
                        <i class="fas fa-key"></i>
                    </div>
                    <input type="password" name="new_password" placeholder="New Password" required>
                </div>

                <!-- Confirm new password -->
                <div class="input-group password-group">
                    <div class="icon-left">
                        <i class="fas fa-key"></i>
                    </div>
                    <input type="password" name="confirm_password" placeholder="Re-enter New Password" required>
                </div>

                <button type="submit" class="change-password-btn">Change Password</button>
                <a href="user_profile.php" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </section>
    </main>

<?php require_once '../../includes/footer.php'; ?>
