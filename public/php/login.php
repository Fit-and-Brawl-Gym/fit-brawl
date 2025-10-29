<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = test_input($_POST['email'] ?? '');
    $password = test_input($_POST['password'] ?? '');

    // Fetch user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['is_verified'] == 0) {
            $error = "Please verify your email before logging in.";
        }
        elseif (password_verify($password, $user['password'])) {
            // Start the session using SessionManager
            SessionManager::startSession($email);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'];

            // Remember Me
            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));
                $token_hash = password_hash($token, PASSWORD_DEFAULT);

                $stmtToken = $conn->prepare("INSERT INTO remember_password (user_id, token_hash) VALUES (?, ?)");
                if (!$stmtToken) die("Prepare failed: " . $conn->error);

                $stmtToken->bind_param("is", $user['id'], $token_hash);
                if (!$stmtToken->execute()) die("Insert token failed: " . $stmtToken->error);

                $_SESSION['remember_password'] = $token;
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
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Set variables for header
$pageTitle = "Login - Fit and Brawl";
$currentPage = "login";
$additionalCSS = ["../css/pages/login.css?v=1"];
$additionalJS = ["../js/hamburger.js"];

// Include header
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main class="login-main">
        <section class="login-hero">
            <div class="hero-content">
                <div class="hero-line"></div>
                <h1 class="hero-title">
                    STRONG TODAY <span class="yellow">  STRONGER </span> TOMORROW
                </h1>
                <div class="hero-underline"></div>
            </div>

            <div class="login-modal">
                <div class="modal-header">
                    <h2>Sign in to access your account</h2>
                </div>

                <form method="post" class="login-form" id="loginForm">
                    <h3>ARE YOU READY TO FOR THE NEXT CHALLENGE?</h3>
                    <?php if(!empty($error)) : ?>
                        <div class="error-box"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email"
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

<?php require_once '../../includes/footer.php'; ?>
