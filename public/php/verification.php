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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - Fit and Brawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/verification.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/verification.js" defer></script>
</head>
<body>
    <!--Header-->
    <header>
        <div class="wrapper">
            <div class="title">
                <a href="index.php">
                    <img src="../../images/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo">
                </a>
                <a href="index.php">
                    <img src="../../images/header-title.svg" alt="FITXBRAWL" class="logo-title">
                </a>
            </div>
            <nav class="nav-bar">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="membership.php">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </nav>
            <?php if(isset($_SESSION['email'])): ?>
                <!-- Logged-in dropdown -->
                <div class="account-dropdown">
                    <img src="../../uploads/avatars/<?= htmlspecialchars($_SESSION['avatar']) ?>"
             alt="Account" class="account-icon">
                    <div class="dropdown-menu">
                        <a href="user_profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not logged-in -->
                <a href="login.php" class="account-link">
                    <img src="../../images/account-icon.svg" alt="Account" class="account-icon">
                </a>
            <?php endif; ?>
        </div>
    </header>

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

    <!--Footer-->
    <footer>
        <div class="container footer-flex">
            <div class="footer-logo-block">
                <img src="../../images/footer-title.png" alt="FITXBRAWL" class="footer-logo-title">
            </div>
            <div class="footer-menu-block">
                <div class="footer-menu-title">MENU</div>
                <ul class="footer-menu-list">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="membership.php">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </div>
            <div class="footer-contact-block">
                <div class="footer-contact-title">CONTACT</div>
                <div class="footer-contact-details">
                    1832 Oroquieta Rd, Santa Cruz, Manila,<br>
                    1008 Metro Manila<br><br>
                    Gmail: fitxbrawl@gmail.com
                </div>
            </div>
            <div class="footer-hours-block">
                <div class="footer-hours-title">OPENING HOURS</div>
                <div class="footer-hours-details">
                    Sun–Fri: 9AM to 10PM<br>
                    Saturday: 10AM to 7PM
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Fit X Brawl, All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
