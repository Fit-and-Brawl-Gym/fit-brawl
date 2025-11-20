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

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Check if already logged in and redirect
if (SessionManager::isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'member';
    if ($role === 'admin') {
        header("Location: admin/admin.php");
        exit;
    } elseif ($role === 'trainer') {
        header("Location: trainer/index.php");
        exit;
    } else {
        header("Location: loggedin-index.php");
        exit;
    }
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = test_input($_POST['email'] ?? '');
    $password = test_input($_POST['password'] ?? '');

    // Check if database connection is available
    if (!isset($conn) || !$conn) {
        $error = "Database connection error. Please try again later.";
    } else {
        // Fetch user by email OR username
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        if (!$stmt) {
            $error = "Database error. Please try again later.";
        } else {
            $stmt->bind_param("ss", $email, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if ($user['is_verified'] == 0) {
                    $error = "Please verify your email before logging in.";
                } elseif (password_verify($password, $user['password'])) {
                    // Start the session using SessionManager
                    SessionManager::startSession($email);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['avatar'] = $user['avatar'];

                    // Remember Me
                    if (isset($_POST['remember'])) {
                        try {
                            $token = bin2hex(random_bytes(32));
                            $token_hash = password_hash($token, PASSWORD_DEFAULT);

                            $stmtToken = $conn->prepare("INSERT INTO remember_password (user_id, token_hash) VALUES (?, ?)");
                            if ($stmtToken) {
                                $stmtToken->bind_param("is", $user['id'], $token_hash);
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
                        header("Location: trainer/index.php");
                    } else {
                        header("Location: loggedin-index.php");
                    }
                    exit;

                } else {
                    $error = "Incorrect email or password.";
                }
            } else {
                $error = "Incorrect email or password.";
            }
            $stmt->close();
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
$additionalCSS = [PUBLIC_PATH . "/css/pages/login.css?v=" . time()];
$additionalJS = [PUBLIC_PATH . 
        "/js/hamburger-menu.js", 
         PUBLIC_PATH . "/js/password-validation.js"];

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>

<!--Main-->
<main class="login-main">
    <section class="login-hero">
        <div class="hero-content">
            <div class="hero-line"></div>
            <div class="hero-title-flex">
                <p>STRONG TODAY</p>
                <p style="color: #d5ba2b">STRONGER EVERYDAY </p>
            </div>
            <div class="hero-underline"></div>
        </div>

        <div class="login-modal">
            <div class="modal-header">
                <h2>Log in to your account</h2>
            </div>

            <form method="post" class="login-form" id="loginForm">
                <?php if (!empty($error)): ?>
                    <div class="error-box"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="input-container">
                    <input type="text" name="email" placeholder="Username or Email"
                        value="<?= htmlspecialchars($_COOKIE['email'] ?? '') ?>" required>
                    
                    <div class="password-container input-group">
                        <input type="password" id="passwordInput" name="password" placeholder="Password" required>
                        <i class="fas fa-eye eye-toggle" id="togglePassword"></i>
                    </div>
            
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