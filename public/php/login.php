<?php
// Set anti-cache headers to prevent Firefox from caching session state
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Use absolute paths based on __DIR__ to ensure includes work regardless of working directory
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

if (!defined('LOGIN_MAX_ATTEMPTS')) {
    define('LOGIN_MAX_ATTEMPTS', 5);
}

if (!defined('LOGIN_WINDOW_SECONDS')) {
    define('LOGIN_WINDOW_SECONDS', 15 * 60); // 15 minutes
}

// Check if already logged in and redirect
if (SessionManager::isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'member';
    if ($role === 'admin') {
        header("Location: admin/admin.php");
        exit;
    } elseif ($role === 'trainer') {
        header("Location: trainer/schedule.php");
        exit;
    } else {
        header("Location: loggedin-index.php");
        exit;
    }
}

$errorMessage = null;
$retryAfterSeconds = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = test_input($_POST['email'] ?? '');
    $password = test_input($_POST['password'] ?? '');
    $rateLimitIdentifier = strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        $errorMessage = [
            'title' => 'Session expired',
            'body' => 'Your session expired. Please refresh the page and try again.'
        ];
    } else {
        // Check if database connection is available
        if (!isset($conn) || !$conn) {
            $errorMessage = [
                'title' => 'System error',
                'body' => 'Database connection error. Please try again later.'
            ];
        } else {
            $blockInfo = isLoginBlocked($conn, $rateLimitIdentifier, LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW_SECONDS);

            if ($blockInfo['blocked']) {
                $retryAfterSeconds = (int) ($blockInfo['retry_after'] ?? 0);
                $minutes = max(1, ceil($retryAfterSeconds / 60));
                $errorMessage = [
                    'title' => 'Too many attempts. Login temporarily locked.',
                ];
            } else {
                // Fetch user by email OR username
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
                if (!$stmt) {
                    $errorMessage = [
                        'title' => 'System error',
                        'body' => 'Database error. Please try again later.'
                    ];
                } else {
                    $stmt->bind_param("ss", $email, $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();

                    if ($user['is_verified'] == 0) {
                        logFailedLoginAttempt($conn, $rateLimitIdentifier);
                        $errorMessage = [
                            'title' => 'Verify your email',
                            'body' => 'Please verify your email before logging in.'
                        ];
                    } elseif (password_verify($password, $user['password'])) {
                        // Start the session using SessionManager
                        SessionManager::startSession($email);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['name'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['avatar'] = $user['avatar'];

                        clearLoginAttempts($conn, $rateLimitIdentifier);

                        // Remember Me
                        if (isset($_POST['remember'])) {
                            try {
                                $token = bin2hex(random_bytes(32));
                                $token_hash = password_hash($token, PASSWORD_DEFAULT);

                                $stmtToken = $conn->prepare("INSERT INTO remember_password (user_id, token_hash) VALUES (?, ?)");
                                if ($stmtToken) {
                                    $stmtToken->bind_param("ss", $user['id'], $token_hash);
                                    if ($stmtToken->execute()) {
                                        $_SESSION['remember_password'] = $token;
                                    }
                                    $stmtToken->close();
                                }
                            } catch (Exception $e) {
                                // Log error but don't fail login if remember me fails
                                error_log("Remember me token error: " . $e->getMessage());
                            }
                        }

                        if ($user['role'] === 'admin') {
                            header("Location: admin/admin.php");
                        } elseif ($user['role'] === 'trainer') {
                            header("Location: trainer/schedule.php");
                        } else {
                            header("Location: loggedin-index.php");
                        }
                        exit;

                    } else {
                        logFailedLoginAttempt($conn, $rateLimitIdentifier);
                        $errorMessage = [
                            'title' => 'Login failed',
                            'body' => 'Incorrect email or password.'
                        ];
                    }
                } else {
                    logFailedLoginAttempt($conn, $rateLimitIdentifier);
                    $errorMessage = [
                        'title' => 'Login failed',
                        'body' => 'Incorrect email or password.'
                    ];
                }
                    $stmt->close();
                }
            }
        }
    }
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Set variables for header
$pageTitle = "Login - Fit and Brawl";
$currentPage = "login";
$additionalCSS = [
    PUBLIC_PATH . "/css/components/alert.css?v=" . time(),
    PUBLIC_PATH . "/css/pages/login.css?v=" . time()
];
// No need to add hamburger-menu.js - it's already loaded in header.php
$additionalJS = [];

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>

<!--Main-->
<main class="login-main">
    <section class="login-hero">
        <div class="hero-content">
            <div class="hero-line"></div>
            <h1 class="hero-title">
                STRONG TODAY <span class="yellow"> STRONGER </span> TOMORROW
            </h1>
            <div class="hero-underline"></div>
        </div>

        <div class="login-modal">
            <div class="modal-header">
                <h2>Sign in to access your account</h2>
            </div>

            <form method="post" class="login-form" id="loginForm">
                <h3>ARE YOU READY TO FOR THE NEXT CHALLENGE?</h3>
                <?= CSRFProtection::getTokenField(); ?>
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert-box alert-box--error" role="alert">
                        <div class="alert-icon" aria-hidden="true">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <p class="alert-title"><?= htmlspecialchars($errorMessage['title'] ?? 'Something went wrong') ?></p>
                            <p class="alert-text"><?= htmlspecialchars($errorMessage['body'] ?? '') ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="hidden" id="loginRetryAfter" value="<?= (int)$retryAfterSeconds ?>">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="text" name="email" placeholder="Username or Email"
                        value="<?= htmlspecialchars($_COOKIE['email'] ?? '') ?>" required>
                </div>

                <div class="input-group password-group">
                    <div class="icon-left">
                        <i class="fas fa-key"></i>
                    </div>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                </div>

                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="login-btn">Log-in</button>

                <p class="signup-link">
                    Don't have an account yet? <a href="sign-up.php">Create an account.</a>
                </p>
            </form>
        </div>
    </section>
</main>

<script>
    (function () {
        const retryInput = document.getElementById('loginRetryAfter');
        if (!retryInput) return;
        let remaining = parseInt(retryInput.value, 10);
        if (!remaining || remaining <= 0) return;

    const form = document.getElementById('loginForm');
    const submitBtn = form ? form.querySelector('.login-btn') : null;
    const errorBox = form ? form.querySelector('.alert-box--error') : null;
    const contentEl = errorBox ? errorBox.querySelector('.alert-content') : null;
    if (!form || !submitBtn || !errorBox || !contentEl) return;

        submitBtn.disabled = true;
        submitBtn.classList.add('is-disabled');

    const countdownEl = document.createElement('p');
    countdownEl.className = 'lockout-countdown';
    contentEl.appendChild(countdownEl);

        const updateCountdown = () => {
            if (remaining <= 0) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('is-disabled');
                countdownEl.textContent = 'You can try logging in again now.';
                clearInterval(timer);
                return;
            }
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            const minsPart = minutes > 0 ? `${minutes} minute${minutes === 1 ? '' : 's'}` : '';
            const secsPart = `${seconds} second${seconds === 1 ? '' : 's'}`;
            const spacing = minsPart && secsPart ? ' ' : '';
            countdownEl.textContent = `Please wait ${minsPart}${spacing}${secsPart} before trying again.`;
            remaining -= 1;
        };

        updateCountdown();
        const timer = setInterval(updateCountdown, 1000);
    })();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
