<?php
/**
 * Trainer Header Include File
 * This file contains the HTML header for trainer pages
 * Shows only Home, Schedule, Feedback, and Profile navigation links
 *
 * Required variables that should be set before including this file:
 * - $pageTitle: The page title (e.g., "Fit and Brawl - Trainer Dashboard")
 * - $currentPage: The current page identifier (e.g., "home", "schedule", "feedback", "profile")
 * - $additionalCSS: (optional) Array of additional CSS files to include
 * - $additionalJS: (optional) Array of additional JS files to include
 */

// Set default values if not provided
if (!isset($pageTitle)) {
    $pageTitle = "Fit and Brawl - Trainer";
}

if (!isset($currentPage)) {
    $currentPage = "";
}

// Determine avatar source for logged-in trainers
if (!isset($avatarSrc)) {
    $avatarSrc = '../../../images/account-icon.svg';
    $hasCustomAvatar = false;
    if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
        $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
        $avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../../images/account-icon.svg";
    }
} else {
    // If avatarSrc is already set, determine if it's custom
    $hasCustomAvatar = isset($avatarSrc) && strpos($avatarSrc, 'uploads/avatars') !== false;
}

// Check if SessionManager is available
$isLoggedIn = false;
if (class_exists('SessionManager')) {
    $isLoggedIn = SessionManager::isLoggedIn();
} else {
    $isLoggedIn = isset($_SESSION['email']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/components/footer.css">
    <link rel="stylesheet" href="../../css/components/header.css">
    <link rel="stylesheet" href="../../css/components/trainer-nav.css">
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $cssFile): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="shortcut icon" href="../../../images/favicon-trainers.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../../js/header-dropdown.js"></script>
    <script src="../../js/hamburger-menu.js"></script>
    <?php if ($isLoggedIn): ?>
    <link rel="stylesheet" href="../../css/components/session-warning.css">
    <script src="../../js/session-timeout.js"></script>
    <?php endif; ?>
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $jsFile): ?>
    <script src="<?= htmlspecialchars($jsFile) ?>"></script>
        <?php endforeach; ?>
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
                    <img src="../../../images/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo">
                </a>
                <a href="index.php">
                    <img src="../../../images/header-title.svg" alt="FITXBRAWL" class="logo-title">
                </a>
            </div>
            <nav class="nav-bar trainer-nav">
                <ul>
                    <li>
                        <a href="index.php" <?= $currentPage === 'home' ? 'class="active"' : '' ?> title="Home">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li>
                        <a href="schedule.php" <?= $currentPage === 'schedule' ? 'class="active"' : '' ?> title="Schedule">
                            <i class="fas fa-calendar-alt"></i>
                        </a>
                    </li>
                    <li>
                        <a href="feedback.php" <?= $currentPage === 'feedback' ? 'class="active"' : '' ?> title="Feedback">
                            <i class="fas fa-comments"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php if (isset($_SESSION['email'])): ?>
                <!-- Logged-in dropdown -->
                <div class="account-dropdown">
                    <img src="<?= $avatarSrc ?>" alt="Account" class="account-icon <?= !$hasCustomAvatar ? 'default-icon' : '' ?>">
                    <div class="dropdown-menu">
                        <a href="profile.php">Profile</a>
                        <a href="../logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not logged-in -->
                <a href="../login.php" class="account-link">
                    <img src="../../../images/account-icon.svg" alt="Account" class="account-icon default-icon">
                </a>
            <?php endif; ?>
        </div>
    </header>
