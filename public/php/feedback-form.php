<?php
session_start();

require_once '../../includes/db_connect.php';


if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in"]); //TODO: Show Modal instead of text
    exit;
}


$status = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
   $user_id = $_SESSION['user_id'];
    $username = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $index = $_SESSION['anonymous_index'] ?? 1;

    

    $sql = "SELECT avatar FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
       if (empty($username )){
        $username = "Anonymous $index";
        $user_avatar = "../../images/profile-icon.svg";
    } else{
        $user_avatar = $row['avatar'];
    }
    if (empty($email)){
        $email = "anon@gmail.com";
    }
 
    if (empty($message)) {
        echo json_encode(["status" => "error", "message" => "Message cannot be empty"]);
        exit;
    }   
        $_SESSION['anonymous_index'] = $index + 1;
        $sql = "INSERT INTO feedback (user_id, username, email, avatar, message, date) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $username, $email, $user_avatar, $message);

        if ($stmt->execute()) {
            $status = "Thanks for your feedback!";
        } else {
           $status = "Error: " . $stmt->error;
        }
    } else {
        $status = "Error: User not found.";
    }
}

// Check membership status for header
require_once '../../includes/membership_check.php';

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/profile-icon.svg";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit and Brawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/feedback-form.css">
    <link rel="stylesheet" href="../css/components/form.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../logo/plm-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/header-dropdown.js"></script>
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
                    <li><a href="<?= $membershipLink ?>">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php" class="active">Feedback</a></li>
                </ul>
            </nav>
            <?php if(isset($_SESSION['email'])): ?>
                <!-- Logged-in dropdown -->
                <div class="account-dropdown">
                    <img src="<?= $avatarSrc ?>"
                         alt="Account" class="account-icon">
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
                    <h1>Share your feedback</h1>
                </div>
                <form method="post" class="feedback-form" id="feedbackForm">
                    <div class="contact-details">
                    <?php if(!empty($status)) : ?>
                        <div class="status"><?= htmlspecialchars($status) ?></div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="icon">
                                    <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                                </svg>
                            </label>
                            <input type="text" id="name" name="name" placeholder="Name (Optional)">
                        </div>
                        <div class="form-group">
                            <label for="last-name" class="email-label">
                                <svg class="icon email-icon" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
                                    <path fill="#fff" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2
                                    2 0 0 0-2-2zm0 4.2l-8 4.8-8-4.8V6l8
                                    4.8L20 6v2.2z"/>
                                </svg>
                            </label>
                            <input type="text" id="email" name="email" placeholder="Email (Optional)">
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea id="message" name="message" placeholder="Leave us a message..." required></textarea>
                    </div>
                    <div class="buttons">
                        <a href="feedback.php">Cancel</a>
                        <button type="submit" name="feedback" class="feedback-btn">Submit</button>
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
