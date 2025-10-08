<?php
session_start();
require_once '../../includes/db_connect.php';

// Include PHPMailer for email sending
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);

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

    //Check password match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $_SESSION['register_error'] = "Passwords do not match.";
        header("Location: sign-up.php");
        exit();
    }

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "member";

    //Check duplicates
    $checkUser = $conn->query("SELECT username FROM users WHERE username = '$name'");
    if ($checkUser->num_rows > 0) {
        $_SESSION['register_error'] = "Username already exists.";
        header("Location: sign-up.php");
        exit();
    }

    $checkEmail = $conn->query("SELECT email FROM users WHERE email = '$email'");
    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = "Email already exists.";
        header("Location: sign-up.php");
        exit();
    }

    //Generate a unique verification token
    $verificationToken = bin2hex(random_bytes(32));

    //Insert user with verification token
    $insertQuery = $conn->prepare("
        INSERT INTO users (username, email, password, role, verification_token, is_verified)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $insertQuery->bind_param("sssss", $name, $email, $password, $role, $verificationToken);

    if ($insertQuery->execute()) {
        //Send verification email
        $verificationLink = "http://localhost/fit-brawl/public/php/verify-email.php?token=" . $verificationToken;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // your SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'fitxbrawl.gym@gmail.com'; // your Gmail
            $mail->Password = 'oxck mxfc cpoj wpra';   // use Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('fitxbrawl.gym@gmail.com', 'Fit & Brawl Gym');
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
    <link rel="shortcut icon" href="../../logo/plm-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
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
</body>
</html>
