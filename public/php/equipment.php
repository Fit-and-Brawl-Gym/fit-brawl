<?php
session_start();


if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    include '../../includes/db_connect.php';

    try {
        $sql = "SELECT id, name, category, status, description, image_path FROM equipment";
        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception($conn->error);
        }

        $equipment = [];
        while ($row = $result->fetch_assoc()) {
            $imageBase = '/fit-brawl/uploads/equipment/';
            $placeholder = '/fit-brawl/images/placeholder-equipment.jpg';

            $row['image_path'] = !empty($row['image_path'])
                ? (strpos($row['image_path'], '/fit-brawl/') === false
                    ? $imageBase . basename($row['image_path'])
                    : $row['image_path'])
                : $placeholder;

            $equipment[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $equipment]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}


// === MAIN PAGE ===
require_once '../../includes/db_connect.php';
require_once '../../includes/membership_check.php';
require_once '../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Redirect if not logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Avatar for header
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar
        ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar'])
        : "../../images/profile-icon.svg";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit and Brawl - Equipment</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/equipment.css?=v1">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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

        <!-- Equipment Gallery -->
        <div class="panel-header">
            <h2>Equipment Availability</h2>
        </div>

        <!-- category chips (filters) -->
        <div class="categories-row" id="category-filters">
            <div class="category-chip active" data-category="cardio">
                <img src="../../images/cardio-icon.svg" alt="Cardio">
                <p>Cardio</p>
            </div>
            <div class="category-chip" data-category="flexibility">
                <img src="../../images/flexibility-icon.svg" alt="Flexibility">
                <p>Flexibility</p>
            </div>
            <div class="category-chip" data-category="core">
                <img src="../../images/core-icon.svg" alt="Core">
                <p>Core</p>
            </div>
            <div class="category-chip" data-category="strength">
                <img src="../../images/strength-icon.svg" alt="Strength Training">
                <p>Strength Training</p>
            </div>
            <div class="category-chip" data-category="functional">
                <img src="../../images/functional-icon.svg" alt="Functional Training">
                <p>Functional Training</p>
            </div>
        </div>

        <!-- controls: search + status filter -->
        <div class="controls">
            <div class="search">
                <input type="search" id="equipmentSearch" placeholder="Search">
            </div>
            <div class="filter">
                <select id="statusFilter">
                    <option value="all">Filter by Status</option>
                    <option value="available">Available</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
        </div>

        <!-- equipment list -->
        <div id="equipment-container">
            <!-- JS will render equipment cards here -->
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

    <script>
        // Load equipment data
        fetch('equipment.php?api=true')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('equipment-container');
                container.innerHTML = data.map(item => `
                    <div class="equipment-card">
                        <h3>${item.name}</h3>
                        <p>Status: <span class="status-${item.equipment.toLowerCase().replace(/\s+/g, '-')}">${item.equipment}</span></p>
                    </div>
                `).join('');
            })
            .catch(error => console.error('Error loading equipment:', error));
    </script>
    <script src="../js/equipment.js"></script>
</body>
</html>
