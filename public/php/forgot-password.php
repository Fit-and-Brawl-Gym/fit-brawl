<?php
session_start();
require_once '../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';
require_once __DIR__ . '/../../includes/encryption.php'; // Add encryption support

$alertMessage = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        $alertMessage = [
            'type' => 'error',
            'title' => 'Session expired',
            'text' => 'Your session expired. Please reload the page and try again.'
        ];
    } else {
    $email = test_input(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Store email in session and redirect to verification page
        $_SESSION['reset_email'] = $email;
        // Initialize resend counter
        $_SESSION['otp_resend_count'] = 0;
        header('Location: verification.php');
        exit();
    } else {
        $alertMessage = [
            'type' => 'error',
            'title' => 'Email not found',
            'text' => 'We couldn\'t find an account with that email address. Please try again.'
        ];
    }
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
$additionalCSS = [
    '../css/components/alert.css?v=' . time(),
    '../css/pages/forgot-password.css'
];
$additionalJS = [];
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main class="forgot-password-main">
        <section class="forgot-password-hero">
            <div class="hero-content">
                <div class="hero-line"></div>
                <h1 class="hero-title">
                    STRONG TODAY <span class="yellow">  STRONGER </span> TOMORROW
                </h1>
                <div class="hero-underline"></div>
            </div>

            <div class="forgot-password-modal">
                <div class="modal-header">
                    <h2>Enter email to verify your account</h2>
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

                <form class="forgot-password-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <?= CSRFProtection::getTokenField(); ?>
                    <h3>A LITTLE STEPBACK BEFORE THE BEST VERSION OF YOU!</h3>

                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>

                    <button type="submit" class="forgot-password-btn">Continue</button>
                </form>
            </div>
        </section>
    </main>

<?php require_once '../../includes/footer.php'; ?>
