<?php
session_start();

require_once '../../includes/db_connect.php';

// Check membership status for header
require_once '../../includes/membership_check.php';

require_once '../../includes/session_manager.php'; 


$hasActiveMembership = false;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
        // Get the latest membership request
        $stmt = $conn->prepare("
            SELECT request_status, membership_status, end_date
            FROM user_memberships
            WHERE user_id = ?
            ORDER BY date_submitted DESC
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $requestStatus = $row['request_status'] ?? null;
                $membershipStatus = $row['membership_status'] ?? null;
                $endDate = $row['end_date'] ?? null;

                // Only approved AND not expired should count
                if (
                    $requestStatus === 'approved' &&
                    $membershipStatus === 'active' &&
                    $endDate >= date('Y-m-d')
                ) {
                    $hasActiveMembership = true;
                }
            }

            $stmt->close();
        }

    } elseif ($conn->query("SHOW TABLES LIKE 'subscriptions'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT id
            FROM subscriptions
            WHERE user_id = ? AND status IN ('Approved','approved')
            ORDER BY date_submitted DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $hasActiveMembership = ($result && $result->num_rows > 0);
            $stmt->close();
        }
    }
}

// Set membership link
$membershipLink = $hasActiveMembership ? 'reservations.php' : 'membership.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$status = "";
$fnameErr = $lnameErr = $emailErr = $phoneErr = $messageErr = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'] ?? null;
    $fname = test_input($_POST['first-name'] ?? '');
    $lname = test_input($_POST['last-name'] ?? '');
    $email = test_input($_POST['email'] ?? '');
    $phoneNum = test_input($_POST['phone'] ?? '');
    $message = test_input($_POST['message'] ?? '');

    if (empty($fname)){
        $fnameErr = "First name is required";
    } else{
        if (!preg_match("/^[a-zA-Z-' ]*$/",$fname)) {
            $nameErr = "Only letters and white space allowed";
        }
    }
    if (empty($lname)){
        $lnameErr = "Last name is required";
    } else{
        if (!preg_match("/^[a-zA-Z-' ]*$/",$lname)) {
            $nameErr = "Only letters and white space allowed";
        }
    }
    if (empty($email)){
        $emailErr = "Email is required";
    } else{
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }
    if (empty($phoneNum)) {
        $phoneErr = "Phone number is required";
    } else{
        if (!preg_match("/^[0-9]{10,15}$/", $phoneNum)) {
            $phoneErr = "Invalid phone number format";
        }
    }
    if (empty($message)) {
        $messageErr = "Message is required";

    }   

    if (empty($fnameErr) && empty($lnameErr) && empty($emailErr) && empty($phoneErr) && empty($messageErr)) {
    $sql = "INSERT INTO contact (first_name, last_name, email, phone_number, message, date_submitted) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $fname, $lname, $email, $phoneNum, $message);

    if ($stmt->execute()) {
        $status = "Your message has been sent successfully.";

        $fname = $lname = $email = $phoneNum = $message = '';
    } else {
        $status = "Database error: " . $stmt->error;
    }
}
}

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/profile-icon.svg";
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit and Brawl - Contact</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/contact.css">
    <link rel="stylesheet" href="../css/components/form.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/header-dropdown.js"></script>
    <script src="../js/hamburger-menu.js"></script>
    <?php if(SessionManager::isLoggedIn()): ?>
    <link rel="stylesheet" href="../css/components/session-warning.css">
    <script src="../js/session-timeout.js"></script>
    <?php endif; ?>
</head>

<body>
    <!--Header-->
    <header>
        <div class="wrapper">
            <button class="hamburger-menu" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
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
                    <li><a href="<?= $membershipLink ?>">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php" class="active">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </nav>
            <?php if (isset($_SESSION['email'])): ?>
                <!-- Logged-in dropdown -->
                <div class="account-dropdown">
                    <img src="<?= $avatarSrc ?>" alt="Account" class="account-icon">
                    <div class="dropdown-menu">
                        <a href="user_profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not logged-in -->
                <a href="login.php" class="account-link">
                    <img src="../../images/profile-icon.svg" alt="Account" class="account-icon">
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!--Main-->
    <main>
        <div class="bg"></div>
        <div class="glowing-bg"></div>
        <div class="contact-container">
            <div class="contact-section">
                <div class="contact-header">
                    <h1>Contact Us</h1>
                </div>
                <form method="post" class="contact-form" id="contactForm">
                <div class="contact-details">
                    <?php if(!empty($status)) : ?>
                        <div class="success"><?= htmlspecialchars($status) ?></div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name</label>
                            <input type="text" id="first-name" name="first-name" placeholder="" value="<?= htmlspecialchars($fname ?? '') ?>">
                            <?php if(!empty($fnameErr)) : ?>
                        <div class="status"><?= htmlspecialchars($fnameErr) ?></div>
                    <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="last-name">Last Name</label>
                            <input type="text" id="last-name" name="last-name" placeholder="" value="<?= htmlspecialchars($lname ?? '') ?>">
                            <?php if(!empty($lnameErr)) : ?>
                        <div class="status"><?= htmlspecialchars($lnameErr) ?></div>
                    <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="" value="<?= htmlspecialchars($email ?? '') ?>">
                            <?php if(!empty($emailErr)) : ?>
                        <div class="status"><?= htmlspecialchars($emailErr) ?></div>
                    <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="" value="<?= htmlspecialchars($phoneNum ?? '') ?>">
                            <?php if(!empty($phoneErr)) : ?>
                        <div class="status"><?= htmlspecialchars($phoneErr) ?></div>
                    <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea id="message" name="message" placeholder="Leave us a message..." value="<?= htmlspecialchars($message ?? '') ?>"></textarea>
                        <?php if(!empty($messageErr)) : ?>
                        <div class="status"><?= htmlspecialchars($messageErr) ?></div>
                    <?php endif; ?>
                    </div>
                    <div class="submit-button">
                        <button type="submit">Submit</button>
                    </div>
                </div>
            </form>
            </div>
        </div>
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