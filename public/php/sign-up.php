<?php
session_start();
require_once '../../includes/db_connect.php';
include_once __DIR__ . '/../../includes/env_loader.php';
loadEnv(__DIR__ . '/../../.env');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {
    $name = test_input($_POST['name']);
    $email = test_input($_POST['email']);
    $password_input = test_input($_POST['password'] ?? '');
    $confirm_password = test_input($_POST['confirm_password'] ?? '');

    //Validate inputs
    if (empty($name) || empty($email) || empty($password_input) || empty($confirm_password)) {
        $_SESSION['register_error'] = "All fields are required.";
        header("Location: sign-up.php");
        exit();
    }

    //Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Please enter a valid email address.";
        header("Location: sign-up.php");
        exit();
    }

    //Check if email domain exists
    $emailDomain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($emailDomain, "MX")) {
        $_SESSION['register_error'] = "Invalid email domain. Please use a real email address.";
        header("Location: sign-up.php");
        exit();
    }

    // Password Validation Function
    function validatePassword($password) {
    $errors = [];

    // must have at least 8 characters
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    // must contain at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    // must contain at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }

    // must contain at least one number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    // must contain at least one special character
    if (!preg_match('/[!@#$%^&*]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*)";
    }

    return $errors;
}

    //Check password match
    if ($password_input !== $confirm_password) {
        $_SESSION['register_error'] = "Passwords do not match.";
        header("Location: sign-up.php");
        exit();
    }

    // Validate password requirements
    $passwordErrors = validatePassword($password_input);
    if (!empty($passwordErrors)) {
        $_SESSION['register_error'] = implode("<br>", $passwordErrors);
        header("Location: sign-up.php");
        exit();
    }

    //Hash password
    $password = password_hash($password_input, PASSWORD_DEFAULT);
    $role = "member";

    //Check for duplicate username or email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $name, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Username or email already exists.";
        header("Location: sign-up.php");
        exit();
    }

    $verificationToken = bin2hex(random_bytes(32));

    // Insert user with verification token
    $insertQuery = $conn->prepare("
        INSERT INTO users (username, email, password, role, verification_token, is_verified)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $insertQuery->bind_param("sssss", $name, $email, $password, $role, $verificationToken);

    if ($insertQuery->execute()) {
        $verificationLink = "http://localhost/fit-brawl/public/php/verify-email.php?token=" . $verificationToken;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = getenv('EMAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('EMAIL_USER');
            $mail->Password = getenv('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = getenv('EMAIL_PORT');

            $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl Gym');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - Fit & Brawl Gym';
            $mail->Body = "
                <h2>Welcome to Fit & Brawl Gym, $name!</h2>
                <p>Click the link below to verify your email:</p>
                <a href='$verificationLink'>$verificationLink</a>
                <p>This link will confirm your account registration.</p>
            ";

            $mail->send();

            $_SESSION['success_message'] = "Account created! Please check your email to verify your account.";
            header("Location: sign-up.php");
            exit();

        } catch (Exception $e) {
            $_SESSION['register_error'] = "Account created but verification email could not be sent. Error: " . $mail->ErrorInfo;
            header("Location: sign-up.php");
            exit();
        }

    } else {
        $_SESSION['register_error'] = "Database error: " . $conn->error;
        header("Location: sign-up.php");
        exit();
    }
}
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function showError($error) {
    return !empty($error) ? "<p class='error-message'>$error</p>" : "";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Fit and Brawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/sign-up.css?v=1">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="stylesheet" href="../css/components/terms-modal.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/hamburger.js"></script>
</head>
<body>
    <!--Header-->
    <header>
        <div class="wrapper">
            <button class="hamburger-menu" id="hamburgerMenu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
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
    <main class="signup-main">
        <section class="signup-hero">
            <div class="hero-content">
                <div class="hero-line"></div>
                <h1 class="hero-title">
                    A <span class="yellow">STRONG BODY</span> STARTS<br>
                    WITH A <span class="yellow">STRONG MIND</span>
                </h1>
                <div class="hero-underline"></div>
            </div>

            <div class="signup-modal">
                <div class="modal-header">
                    <h2>Create an account</h2>
                </div>

                <form action="sign-up.php" method="post" class="signup-form">
                    <h3>ARE YOU READY TO BECOME THE BETTER VERSION OF YOURSELF?</h3>

                    <?= showError($_SESSION['register_error'] ?? ''); ?>
                    <?php unset($_SESSION['register_error']); ?>
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <p class="success-message"><?= $_SESSION['success_message']; ?></p>
                    <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" placeholder="Name" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-key"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-key"></i>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" required>
                            <span class="checkmark"></span>
                            Agree to&nbsp;<a href="#" class="terms-link">Terms and Conditions</a>
                        </label>
                    </div>

                    <button type="submit" name="signup" class="signup-btn">Sign up</button>

                    <p class="login-link">
                        Already have an account? <a href="login.php">Sign in here.</a>
                    </p>
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

    <!-- Terms and Conditions Modal -->
    <div class="terms-modal-overlay">
        <div class="terms-modal-container">
            <div class="terms-modal-header">
                <h2>Terms and Condition</h2>
                <button class="terms-close-btn">&times;</button>
            </div>

            <div class="terms-modal-body">
                <div class="terms-sidebar">
                    <nav>
                        <ul>
                            <li><a href="#use-of-website" class="active">1. Use of Our Website</a></li>
                            <li><a href="#booking-scheduling">2. Booking and Scheduling</a></li>
                            <li><a href="#payments-fees">3. Payments and Fees</a></li>
                            <li><a href="#cancellations-refunds">4. Cancellations and Refunds</a></li>
                            <li><a href="#health-safety">5. Health and Safety</a></li>
                            <li><a href="#memberships">6. Memberships and Subscriptions</a></li>
                            <li><a href="#intellectual-property">7. Intellectual Property</a></li>
                            <li><a href="#limitation-liability">8. Limitation of Liability</a></li>
                            <li><a href="#privacy-policy">9. Privacy Policy</a></li>
                            <li><a href="#changes-terms">10. Changes to These Terms</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="terms-content">
                    <p class="terms-last-updated">Last Updated: October 7, 2025</p>
                    <p class="terms-intro">Welcome to Fit and Brawl! These Terms and Conditions ("Terms") govern your use of our website, [Wala pang website link], and all related services offered by Fit and Brawl. By accessing or using our website, scheduling sessions, or purchasing services, you agree to comply with these Terms.</p>

                    <h3 id="use-of-website">1. Use of Our Website</h3>
                    <ul>
                        <li>You must be at least 18 years old (or have parental consent) to create an account and use our online services.</li>
                        <li>You agree to provide accurate, complete, and current information when creating an account or booking sessions.</li>
                        <li>You are responsible for maintaining the confidentiality of your account and password.</li>
                    </ul>

                    <h3 id="booking-scheduling">2. Booking and Scheduling</h3>
                    <ul>
                        <li>You may schedule workouts, classes, or personal training sessions through our website.</li>
                        <li>All bookings are subject to availability and confirmation by our staff.</li>
                        <li>You agree to arrive on time for your scheduled sessions. Late arrivals may result in reduced session time.</li>
                    </ul>

                    <h3 id="payments-fees">3. Payments and Fees</h3>
                    <ul>
                        <li>All payments must be made through our secure online payment system.</li>
                        <li>Prices for services are listed on our website and may change without prior notice.</li>
                        <li>Payments are non-refundable except as required by law or under specific promotional terms.</li>
                    </ul>

                    <h3 id="cancellations-refunds">4. Cancellations and Refunds</h3>
                    <ul>
                        <li>You may cancel or reschedule a session at least 24 hours before the appointment time.</li>
                        <li>Late cancellations or "no-shows" may result in a cancellation fee or forfeiture of the session.</li>
                        <li>Refund requests will be reviewed and processed at our discretion.</li>
                    </ul>

                    <h3 id="health-safety">5. Health and Safety</h3>
                    <ul>
                        <li>By using our services, you confirm that you are physically fit and have consulted a medical professional if needed.</li>
                        <li>You agree to follow all gym rules, safety guidelines, and instructions from our trainers.</li>
                        <li>We are not responsible for any injuries, accidents, or health issues that occur during participation in our programs.</li>
                    </ul>

                    <h3 id="memberships">6. Memberships and Subscriptions</h3>
                    <ul>
                        <li>If you purchase a membership, you agree to the terms of your selected plan (duration, renewal, and cancellation policies).</li>
                        <li>Membership fees are billed according to the plan you choose.</li>
                        <li>Automatic renewals may apply unless you cancel before the renewal date.</li>
                    </ul>

                    <h3 id="intellectual-property">7. Intellectual Property</h3>
                    <ul>
                        <li>All content on this website (text, images, logos, videos, etc.) is the property of Fit and Brawl and is protected by copyright law.</li>
                        <li>You may not reproduce, distribute, or modify any content without our written consent.</li>
                    </ul>

                    <h3 id="limitation-liability">8. Limitation of Liability</h3>
                    <ul>
                        <li>Fit and Brawl is not liable for any indirect, incidental, or consequential damages arising from your use of our website or services.</li>
                        <li>We do not guarantee that our website will always be available, error-free, or secure.</li>
                    </ul>

                    <h3 id="privacy-policy">9. Privacy Policy</h3>
                    <ul>
                        <li>We respect your privacy and will not share your data with third parties except as required by law.</li>
                    </ul>

                    <h3 id="changes-terms">10. Changes to These Terms</h3>
                    <ul>
                        <li>We may update these Terms from time to time.</li>
                        <li>Any changes will be posted on this page with an updated "Last Updated" date.</li>
                        <li>Continued use of our website or services after such changes means you accept the revised Terms.</li>
                    </ul>
                </div>
            </div>

            <div class="terms-modal-footer">
                <button class="terms-decline-btn">Decline</button>
                <button class="terms-accept-btn">Accept</button>
            </div>
        </div>
    </div>

    <script src="../js/terms-modal.js"></script>
</body>
</html>
