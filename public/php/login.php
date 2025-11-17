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
require_once __DIR__ . '/../../includes/csp_nonce.php';
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';
require_once __DIR__ . '/../../includes/mail_config.php';
require_once __DIR__ . '/../../includes/encryption.php'; // Add encryption support

// Generate CSP nonces for this request
CSPNonce::generate();

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
    require_once __DIR__ . '/../../includes/redirect_validator.php';
    RedirectValidator::init();

    $role = $_SESSION['role'] ?? 'member';
    if ($role === 'admin') {
        RedirectValidator::redirect('admin/admin.php');
    } elseif ($role === 'trainer') {
        RedirectValidator::redirect('trainer/schedule.php');
    } else {
        RedirectValidator::redirect('loggedin-index.php');
    }
}

$errorMessage = null;
$retryAfterSeconds = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = test_input($_POST['email'] ?? '');
    $password = test_input($_POST['password'] ?? '');

    // Create base rate limit identifier with IP
    $ipIdentifier = 'login|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $inputIdentifier = strtolower($email);

    // We'll check/log against both the input AND any associated account identifiers
    $rateLimitIdentifiers = [$ipIdentifier . '|' . $inputIdentifier];

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
            // Pre-fetch the account to get both email AND username for unified rate limiting
            $accountIdentifiers = [];
            $accountEmail = null; // Store actual email for notifications
            $lookupStmt = $conn->prepare("SELECT email, username FROM users WHERE email = ? OR username = ? LIMIT 1");
            if ($lookupStmt) {
                $lookupStmt->bind_param("ss", $email, $email);
                $lookupStmt->execute();
                $lookupResult = $lookupStmt->get_result();
                if ($lookupResult->num_rows > 0) {
                    $accountRow = $lookupResult->fetch_assoc();
                    $accountEmail = $accountRow['email']; // Store the actual email
                    $accountIdentifiers[] = $ipIdentifier . '|' . strtolower($accountRow['email']);
                    $accountIdentifiers[] = $ipIdentifier . '|' . strtolower($accountRow['username']);
                }
                $lookupStmt->close();
            }

            // If account exists, check rate limits for BOTH email and username
            // This prevents bypassing the lock by alternating between email/username
            $checkIdentifiers = !empty($accountIdentifiers) ? $accountIdentifiers : $rateLimitIdentifiers;

            $isBlocked = false;
            $maxRetryAfter = 0;

            foreach ($checkIdentifiers as $identifier) {
                $blockInfo = isLoginBlocked($conn, $identifier, LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW_SECONDS);
                if ($blockInfo['blocked']) {
                    $isBlocked = true;
                    $maxRetryAfter = max($maxRetryAfter, $blockInfo['retry_after']);
                }
            }

            if ($isBlocked) {
                $retryAfterSeconds = (int) $maxRetryAfter;
                $minutes = max(1, ceil($retryAfterSeconds / 60));
                $errorMessage = [
                    'title' => 'Too many attempts. Login temporarily locked.',
                ];

                $lockoutIdentifier = hash('sha256', $inputIdentifier);
                if (!isset($_SESSION['lockout_notified'])) {
                    $_SESSION['lockout_notified'] = [];
                }

                $lastNotified = $_SESSION['lockout_notified'][$lockoutIdentifier] ?? 0;
                $shouldNotify = time() - $lastNotified >= max(60, $retryAfterSeconds / 2);

                // Use the actual account email if available, otherwise fall back to input if it's a valid email
                $notificationEmail = $accountEmail ?? (filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null);

                if ($shouldNotify && $notificationEmail) {
                    if (function_exists('sendAccountLockNotification')) {
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        sendAccountLockNotification($notificationEmail, $retryAfterSeconds, $ip, LOGIN_MAX_ATTEMPTS);
                    }
                    $_SESSION['lockout_notified'][$lockoutIdentifier] = time();
                }
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
                        // Log failed attempt for all associated identifiers
                        foreach (!empty($accountIdentifiers) ? $accountIdentifiers : $rateLimitIdentifiers as $id) {
                            logFailedLoginAttempt($conn, $id);
                        }
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

                        // Register session in tracker (after user_id is set)
                        if (file_exists(__DIR__ . '/../../includes/session_tracker.php')) {
                            require_once __DIR__ . '/../../includes/session_tracker.php';
                            SessionTracker::init($conn);
                            SessionTracker::registerSession($user['id'], session_id());
                        }

                        clearLoginAttempts($conn, $rateLimitIdentifier);

                        // Also clear attempts for account identifiers if they exist
                        if (!empty($accountIdentifiers)) {
                            foreach ($accountIdentifiers as $id) {
                                clearLoginAttempts($conn, $id);
                            }
                        }

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
                        // Log failed attempt for all associated identifiers
                        foreach (!empty($accountIdentifiers) ? $accountIdentifiers : $rateLimitIdentifiers as $id) {
                            logFailedLoginAttempt($conn, $id);
                        }
                        $errorMessage = [
                            'title' => 'Login failed',
                            'body' => 'Incorrect email or password.'
                        ];
                    }
                } else {
                    // Account not found - log against input identifier only
                    foreach ($rateLimitIdentifiers as $id) {
                        logFailedLoginAttempt($conn, $id);
                    }
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

<script <?= CSPNonce::getScriptNonceAttr() ?>>
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
