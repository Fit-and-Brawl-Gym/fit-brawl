<?php
// Check if this is an API request

session_start();

if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    include '../../includes/db_connect.php';

    $sql = "SELECT id, name, stock, status FROM products";
    $result = $conn->query($sql);

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode($products);
    exit;
}

require_once '../../includes/db_connect.php';

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
    <title>Fit and Brawl - Products</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/products.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/header-dropdown.js"></script>
    <script src="../js/hamburger-menu.js"></script>
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
                    <li><a href="products.php" class="active">Products</a></li>
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

    <!-- HERO -->
    <section class="products-hero">
        <div style="max-width:1200px;margin:0 auto;padding:6px 24px">
        <h1 class="title"><strong style="color:var(--color-accent)">ESSENTIALS</strong> FOR EVERY</h1>
        <h1 class="title"><strong style="color:var(--color-accent)">REP, SET,</strong> AND <strong style="color:var(--color-accent)">GOAL<span class="exclamation">!</span></strong></h1>
        <p class="subtitle"> Check the available <strong style="color:var(--color-accent)">PRODUCTS</strong> in our store!</p>
        </div>
    </section>

    <!--Main-->
    <main class = "container">
        <div class="products-panel">

        <!-- Products Heading -->
         <div class="panel-header">
            <h2>Products</h2>
         </div>

        <!-- Categories -->
         <div class="categories-row">
            <div class="category-chip" data-cat="supplements">
                <img src="../../images/supplements-icon.svg" alt="Supplements">
                <p>Supplements</p>
            </div>
            <div class="category-chip" data-cat="hydration">
                <img src="../../images/hydration-icon.svg" alt="Hydration & Drinks">
                <p>Hydration and Drinks</p>
                </div>
            <div class="category-chip" data-cat="snacks">
                <img src="../../images/snacks-icon.svg" alt="Snacks">
                <p>Snacks</p>
                </div>
            <div class="category-chip" data-cat="boxing gloves">
                <img src="../../images/boxing-icon.svg" alt="Boxing and Muay Thai Gloves">
                <p>Boxing and Muay Thai</p>
                </div>
         </div>

        <!-- Search Product -->
        <div class="controls">
        <div class="search">
            <input type="search" id="q" placeholder="Search products..." aria-label="Search products">
        </div>
        <div style="width:210px">
            <select id="statusFilter">
            <option value="all">Filter by Status</option>
            <option value="in">In Stock</option>
            <option value="low">Low on Stock</option>
            <option value="out">Out of Stock</option>
            </select>
        </div>
        </div>

        <!-- Grid -->
        <div id="grid" class="grid"></div>

        </div>

    </main>

    <script src="../js/products.js"></script>

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
<script>
fetch("../api/product_api.php")
  .then(res => res.json())
  .then(console.log);
</script>
