<?php
session_start();
require_once '../../includes/db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = test_input(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Store email in session and redirect to verification page
        $_SESSION['reset_email'] = $email;
        header("Location: verification.php");  // Changed from change-password.php
        exit;
    } else {
        $error = "Email address not found in our records.";
    }
}
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


$pageTitle = "Forgot Password - Fit and Brawl";
$currentPage = "forgot_password";
$additionalCSS = ['../css/pages/forgot-password.css'];
$additionalJS = [];
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main class="forgot-password-main">
        <section class="forgot-password-hero">
            <div class="forgot-password-modal">
                <div class="modal-header">
                    <h2>Enter email to verify your account</h2>
                </div>

                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <form class="forgot-password-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <h3>Enter your registered email address to proceed with the password reset.</h3>

                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>

                    <button type="submit" class="forgot-password-btn">Continue</button>
                </form>
            </div>
        </section>
    </main>
