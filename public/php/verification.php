<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/mail_config.php';
require_once '../../includes/csrf_protection.php';

// Redirect if no reset email in session
if(!isset($_SESSION['reset_email'])) {
    require_once __DIR__ . '/../../includes/redirect_validator.php';
    RedirectValidator::init();
    RedirectValidator::redirect('forgot-password.php');
}

$alertMessage = null;

// Generate and send OTP if not already sent
if(!isset($_SESSION['otp_sent'])) {
    $otp = sprintf("%06d", random_int(0, 999999));
    $expiry = date('Y-m-d H:i:s', strtotime('5 minutes'));

    $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $expiry, $_SESSION['reset_email']);

    if($stmt->execute() && sendOTPEmail($_SESSION['reset_email'], $otp)) {
        $_SESSION['otp_sent'] = true;
    } else {
        $alertMessage = [
            'type' => 'error',
            'title' => 'Unable to send code',
            'text' => 'We couldn\'t send the verification code. Please try again.'
        ];
    }
}

// Verify OTP
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        $alertMessage = [
            'type' => 'error',
            'title' => 'Invalid Request',
            'text' => 'Security token validation failed. Please try again.'
        ];
    } else {
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

            // Clear resend counter
            unset($_SESSION['otp_resend_count']);

            // Set flag to clear session storage after redirect
            $_SESSION['clear_otp_timer'] = true;

            require_once __DIR__ . '/../../includes/redirect_validator.php';
            RedirectValidator::init();
            RedirectValidator::redirect('change-password.php');
        } else {
            $alertMessage = [
                'type' => 'error',
                'title' => 'Code expired',
                'text' => 'Your verification code has expired. Please request a new one.'
            ];
        }
    } else {
        $alertMessage = [
            'type' => 'error',
            'title' => 'Invalid code',
            'text' => 'The verification code you entered is incorrect. Please try again.'
        ];
    }
    }
}

$pageTitle = "Verify Account - Fit and Brawl";
$currentPage = "verification";
$additionalCSS = [
    '../css/components/alert.css?v=' . time(),
    '../css/pages/verification.css'
];
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

                <?php if (!empty($alertMessage)): ?>
                    <div class="alert-box alert-box--<?= htmlspecialchars($alertMessage['type']); ?>" role="alert">
                        <div class="alert-icon" aria-hidden="true">
                            <i class="fas fa-<?= $alertMessage['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                        </div>
                        <div class="alert-content">
                            <p class="alert-title"><?= htmlspecialchars($alertMessage['title']); ?></p>
                            <p class="alert-text"><?= nl2br(htmlspecialchars($alertMessage['text'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="verification-form" method="POST">
                    <?= CSRFProtection::getTokenField(); ?>
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
