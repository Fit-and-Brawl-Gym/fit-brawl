<?php
/**
 * Header Include File
 * This file contains the HTML header for all pages
 * It handles both logged-in and non-logged-in states
 *
 * Required variables that should be set before including this file:
 * - $pageTitle: The page title (e.g., "Fit and Brawl - Contact")
 * - $currentPage: The current page identifier (e.g., "home", "membership", "equipment", "products", "contact", "feedback")
 * - $additionalCSS: (optional) Array of additional CSS files to include
 * - $additionalJS: (optional) Array of additional JS files to include
 *
 * Optional variables:
 * - $membershipLink: Dynamic membership link based on user status (defaults to 'membership.php')
 * - $avatarSrc: Avatar source path (will be computed if not provided)
 */

// Load environment configuration
require_once __DIR__ . '/config.php';

// Ensure session is started if SessionManager is available
if (class_exists('SessionManager')) {
    SessionManager::initialize();
}

// Set default values if not provided
if (!isset($pageTitle)) {
    $pageTitle = "Fit and Brawl";
}

if (!isset($currentPage)) {
    $currentPage = "";
}

// Calculate membership link if not already set
if (!isset($membershipLink)) {
    $membershipLink = 'membership.php';
    $hasActiveMembership = false;

    if (isset($_SESSION['user_id'])) {
        $hasAnyRequest = false;
        $gracePeriodDays = 3;
        $user_id = $_SESSION['user_id'];
        $today = date('Y-m-d');

        // Check user_memberships table
        if (isset($conn) && $conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
            $stmt = $conn->prepare("
                SELECT request_status, membership_status, end_date
                FROM user_memberships
                WHERE user_id = ?
                ORDER BY date_submitted DESC
                LIMIT 1
            ");

            if ($stmt) {
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $requestStatus = $row['request_status'] ?? null;
                    $membershipStatus = $row['membership_status'] ?? null;
                    $endDate = $row['end_date'] ?? null;

                    $hasAnyRequest = true;

                    if ($requestStatus === 'approved' && $endDate) {
                        $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

                        if ($expiryWithGrace >= $today) {
                            $hasActiveMembership = true;
                            $hasAnyRequest = false;
                        }
                    }
                }

                $stmt->close();
            }
        } elseif (isset($conn) && $conn->query("SHOW TABLES LIKE 'subscriptions'")->num_rows) {
            $stmt = $conn->prepare("
                SELECT status, end_date
                FROM subscriptions
                WHERE user_id = ? AND status IN ('Approved','approved')
                ORDER BY date_submitted DESC
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $status = strtolower($row['status']);
                    $endDate = $row['end_date'] ?? null;
                    $hasAnyRequest = true;

                    if ($status === 'approved' && $endDate) {
                        $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

                        if ($expiryWithGrace >= $today) {
                            $hasActiveMembership = true;
                            $hasAnyRequest = false;
                        }
                    }
                }

                $stmt->close();
            }
        }

        if ($hasActiveMembership) {
            $membershipLink = 'reservations.php';
        } elseif ($hasAnyRequest) {
            $membershipLink = 'membership-status.php';
        } else {
            $membershipLink = 'membership.php';
        }
    }
} else {
    // If membershipLink is already set, determine hasActiveMembership status
    $hasActiveMembership = ($membershipLink === 'reservations.php');
}

// Determine membership icon and title based on status
if (!isset($membershipIcon)) {
    $membershipIcon = 'fa-id-card';
    $membershipTitle = 'Membership';

    if (isset($hasActiveMembership) && $hasActiveMembership) {
        $membershipIcon = 'fa-calendar-alt';
        $membershipTitle = 'Schedule';
    }
}

// Determine avatar source for logged-in users
if (!isset($avatarSrc)) {
    $avatarSrc = IMAGES_PATH . '/account-icon.svg';
    $hasCustomAvatar = false;
    if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
        $avatarFile = $_SESSION['avatar'];
        $hasCustomAvatar = $avatarFile !== 'account-icon.svg' && $avatarFile !== 'account-icon-white.svg' && !empty($avatarFile);
        if ($hasCustomAvatar) {
            // Verify file exists before using it
            $realPath = __DIR__ . '/../uploads/avatars/' . $avatarFile;
            if (file_exists($realPath)) {
                $avatarSrc = UPLOADS_PATH . "/avatars/" . htmlspecialchars($avatarFile) . '?v=' . time();
            } else {
                $avatarSrc = IMAGES_PATH . "/account-icon.svg";
                $hasCustomAvatar = false;
            }
        } else {
            $avatarSrc = IMAGES_PATH . "/account-icon.svg";
        }
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

// SEO defaults - can be overridden in individual pages
if (!isset($metaDescription)) {
    $metaDescription = "Fit and Brawl Gym - Build a body that's built for battle. Premier boxing, MMA, and Muay Thai training facility. Professional trainers, modern equipment, flexible membership plans.";
}
if (!isset($metaKeywords)) {
    $metaKeywords = "boxing gym, MMA training, Muay Thai, fitness center, combat sports, gym membership, martial arts, personal training";
}
if (!isset($ogImage)) {
    $ogImage = IMAGES_PATH . "/website-preview-image.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- CSRF Token for AJAX requests -->
    <?php if (isset($csrfToken)): ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <?php endif; ?>

    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
    <meta name="author" content="Fit and Brawl Gym">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Preload Critical Resources -->
    <link rel="preload" href="<?= PUBLIC_PATH ?>/css/global.css" as="style">
    <link rel="preload" href="<?= PUBLIC_PATH ?>/css/components/header.css" as="style">
    <link rel="preload" href="<?= IMAGES_PATH ?>/fnb-logo-yellow.svg" as="image">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/css/global.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/css/mobile-responsive.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/css/components/footer.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/css/components/header.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/css/components/member-nav.css">
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $cssFile): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Favicons and Touch Icons -->
    <link rel="shortcut icon" href="<?= IMAGES_PATH ?>/favicon-members.png" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= IMAGES_PATH ?>/favicon-members.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= IMAGES_PATH ?>/favicon-members.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= IMAGES_PATH ?>/favicon-members.png">
    <link rel="manifest" href="<?= PUBLIC_PATH ?>/site.webmanifest">
    <meta name="theme-color" content="#002f3f">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Core Scripts -->
    <script>
        // Make PUBLIC_PATH available to JavaScript
        window.PUBLIC_PATH = <?= json_encode(PUBLIC_PATH) ?>;
    </script>
    <script src="<?= PUBLIC_PATH ?>/js/header-dropdown.js" defer></script>
    <script src="<?= PUBLIC_PATH ?>/js/hamburger-menu.js" defer></script>
    <?php if ($isLoggedIn): ?>
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/css/components/session-warning.css">
    <script src="<?= PUBLIC_PATH ?>/js/session-timeout.js"></script>
    <?php endif; ?>
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $jsFile): ?>
    <script src="<?= htmlspecialchars($jsFile) ?>" defer></script>
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
                <a href="<?= $isLoggedIn ? 'loggedin-index.php' : 'index.php' ?>">
                    <img src="<?= IMAGES_PATH ?>/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo">
                </a>
                <a href="<?= $isLoggedIn ? 'loggedin-index.php' : 'index.php' ?>">
                    <img src="<?= IMAGES_PATH ?>/header-title.svg" alt="FITXBRAWL" class="logo-title">
                </a>
            </div>
            <nav class="nav-bar member-nav">
                <ul>
                    <li>
                        <a href="<?= $isLoggedIn ? 'loggedin-index.php' : 'index.php' ?>" <?= $currentPage === 'home' ? 'class="active"' : '' ?> title="Home">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li>
                        <a href="<?= htmlspecialchars($membershipLink) ?>" <?= ($currentPage === 'membership' || $currentPage === 'reservations') ? 'class="active"' : '' ?> title="<?= htmlspecialchars($membershipTitle) ?>">
                            <i class="fas <?= htmlspecialchars($membershipIcon) ?>"></i>
                        </a>
                    </li>
                    <li>
                        <a href="equipment.php" <?= $currentPage === 'equipment' ? 'class="active"' : '' ?> title="Equipment">
                            <i class="fas fa-dumbbell"></i>
                        </a>
                    </li>
                    <li>
                        <a href="products.php" <?= $currentPage === 'products' ? 'class="active"' : '' ?> title="Products">
                            <i class="fas fa-jar"></i>
                        </a>
                    </li>
                    <li>
                        <a href="contact.php" <?= $currentPage === 'contact' ? 'class="active"' : '' ?> title="Contact">
                            <i class="fas fa-envelope"></i>
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
                    <div class="account-info">
                        <span class="username-display"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
                        <?php
                        // On all pages except user_profile, always show default if missing or empty
                        $isProfilePage = (basename($_SERVER['PHP_SELF']) === 'user_profile.php');
                        $avatarToShow = $avatarSrc;
                        // Enhanced error handling for missing/invalid avatar
                        $avatarFile = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : '';
                        $avatarPath = UPLOADS_PATH . "/avatars/" . htmlspecialchars($avatarFile);
                        $avatarExists = false;
                        if ($hasCustomAvatar && !empty($avatarFile) && $avatarFile !== 'account-icon.svg' && $avatarFile !== 'account-icon-white.svg') {
                            // Check if file exists on disk using __DIR__ for reliable path resolution
                            $realPath = __DIR__ . '/../uploads/avatars/' . $avatarFile;
                            $avatarExists = file_exists($realPath);
                        }
                        if (!$isProfilePage && (!$hasCustomAvatar || empty($avatarFile) || $avatarFile === 'account-icon-white.svg' || !$avatarExists)) {
                            $avatarToShow = IMAGES_PATH . '/account-icon.svg';
                        } elseif ($avatarExists) {
                            // Add cache-busting parameter to force browser to reload new avatar
                            $avatarToShow = $avatarPath . '?v=' . time();
                        }
                        ?>
                        <img src="<?= $avatarToShow ?>" alt="Account" class="account-icon <?= (!$hasCustomAvatar || empty($_SESSION['avatar']) || !$avatarExists) ? 'default-icon' : '' ?>">
                    </div>
                    <div class="dropdown-menu">
                        <a href="user_profile.php">Profile</a>
                        <a href="<?= PUBLIC_PATH ?>/php/logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not logged-in - Auth buttons -->
                <div class="auth-buttons">
                    <a href="sign-up.php" class="btn-signup">Sign Up</a>
                    <a href="login.php" class="btn-signin">Log In</a>
                </div>
            <?php endif; ?>
        </div>
    </header>
