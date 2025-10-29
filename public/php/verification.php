<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/mail_config.php';

// Redirect if no reset email in session
if(!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit;
}

$error = '';
$success = '';

// Generate and send OTP if not already sent
if(!isset($_SESSION['otp_sent'])) {
    $otp = sprintf("%06d", random_int(0, 999999));
    $expiry = date('Y-m-d H:i:s', strtotime('5 minutes'));

    $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $expiry, $_SESSION['reset_email']);

    if($stmt->execute() && sendOTPEmail($_SESSION['reset_email'], $otp)) {
        $_SESSION['otp_sent'] = true;
    } else {
        $error = "Failed to send OTP. Please try again.";
    }
}

// Verify OTP
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];
    $email = $_SESSION['reset_email'];

    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user && $user['otp'] == $entered_otp) {
        if(strtotime($user['otp_expiry']) >= time()) {
            // Clear OTP and session storage
            $stmt = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            echo "<script>sessionStorage.removeItem('otpExpiryTime');</script>";
            header("Location: change-password.php");
            exit;
        } else {
            $error = "OTP has expired. Please request a new one.";
        }
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}

$pageTitle = "Verify Account - Fit and Brawl";
$currentPage = "verification";
$additionalCSS = ['../css/pages/verification.css'];
$additionalJS = ['../js/verification.js'];
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main class="verification-main">
        <section class="verification-hero">
            <div class="hero-content">
                <div class="hero-line"></div>
                <h1 class="hero-title">
                    STRONG TODAY <span class="yellow">STRONGER</span> TOMORROW
                </h1>
                <div class="hero-underline"></div>
            </div>

            <div class="verification-modal">
                <div class="modal-header">
                    <h2>Verify Your Account</h2>
                </div>

                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <form class="verification-form" method="POST">
                    <h3>CHECK YOUR EMAIL FOR THE <br>VERIFICATION CODE</h3>

                    <div class="verification-input-container">
                        <div class="otp-input-wrapper">
                            <i class="fas fa-key"></i>
                            <input type="text"
                                name="otp"
                                id="otp"
                                maxlength="6"
                                placeholder="000000"
                                required
                                pattern="\d{6}">
                        </div>
                        <button type="button" id="resend-otp" class="resend-btn">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>

                    <div id="countdown"></div>
                    <button type="submit" class="verify-btn">Verify Code</button>
                </form>
            </div>
        </section>
    </main>

<?php require_once '../../includes/footer.php'; ?>
