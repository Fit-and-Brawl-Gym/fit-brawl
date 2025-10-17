<?php
// Check if this is an API request
session_start();

if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    include '../../includes/db_connect.php';

    $sql = "SELECT id, name, status, category, description, image_path FROM equipment";
    $result = $conn->query($sql);

    $equipment = [];
    while ($row = $result->fetch_assoc()) {
        $equipment[] = $row;
    }

    echo json_encode($equipment);
    exit;
}

require_once '../../includes/db_connect.php';

// Check membership status for header
require_once '../../includes/membership_check.php';

require_once '../../includes/session_manager.php'; 

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

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
    <title>Fit and Brawl - Equipment</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/equipment.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/header-dropdown.js"></script>
    <?php if(SessionManager::isLoggedIn()): ?>
    <link rel="stylesheet" href="../css/components/session-warning.css">
    <script src="../js/session-timeout.js"></script>
    <?php endif; ?>
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
                    <li><a href="equipment.php" class="active">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
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
        <div class="bg"></div>
 <!-- HERO -->
    <section class="equipment-hero">
        <div style="max-width:1200px;margin:0 auto;padding:6px 24px">
        <h1 class="title"><strong style="color:var(--color-accent)">PLAN</strong> YOUR WORKOUT</h1>
        <h1 class="title">WITH <strong style="color:var(--color-accent)">CONFIDENCE</strong></h1>
        <p class="subtitle"> Choose the <strong style="color:var(--color-accent)">EQUIPMENT</strong> best for you!</p>
        </div>
    </section>

    <!--Main-->
    <main>
          <div class="bg"></div>
        <div class="equipment-panel">
        <!-- equipment Heading -->
         <div class="panel-header">
            <h2>equipment availability</h2>
         </div>

        <!-- Categories -->
         <div class="categories-row">
            <div class="category-chip" data-cat="supplements">
                <img src="../../images/cardio-icon.svg" alt="Cardio Icon">
                <p>Cardio</p>
            </div>
            <div class="category-chip" data-cat="hydration">
                <img src="../../images/flexibility-icon.svg" alt="Flexibility Icon">
                <p>Flexibility</p>
            </div>
            <div class="category-chip" data-cat="snacks">
                <img src="../../images/core-icon.svg" alt="Core Icon">
                <p>Core</p>
            </div>
            <div class="category-chip" data-cat="boxing gloves">
                <img src="../../images/strength-icon.svg" alt="Strength Icon">
                <p>Strength Training</p>
            </div>
            <div class="category-chip" data-cat="boxing gloves">
                <img src="../../images/functional-icon.svg" alt="Functional Icon">
                <p>Functional Training</p>
            </div>
         </div>

        <!-- Search Product -->
        <div class="controls">
        <div class="search">
            <input type="search" id="q" placeholder="Search equipment..." aria-label="Search equipment">
        </div>
        <div style="width:210px">
            <select id="statusFilter">
            <option value="all">Filter by Status</option>
            <option value="available">Available</option>
            <option value="out-of-order">Out of order</option>
            <option value="low">Maintenance</option>
            </select>
        </div>
        </div>
        <!-- Equipment Lists -->
        <div id="equipment-container">
           
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

    <script src="../js/equipment.js?=v1"></script>
</body>
</html>
