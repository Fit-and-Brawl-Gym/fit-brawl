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

    if (isset($_SESSION['user_id'])) {
        $hasActiveMembership = false;
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
                $stmt->bind_param("i", $user_id);
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
                $stmt->bind_param("i", $user_id);
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
}

// Determine avatar source for logged-in users
if (!isset($avatarSrc)) {
    $avatarSrc = '../../images/account-icon.png';
    if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
        $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
        $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.png";
    }
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
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $cssFile): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/header-dropdown.js"></script>
    <script src="../js/hamburger-menu.js"></script>
    <?php if ($isLoggedIn): ?>
    <link rel="stylesheet" href="../css/components/session-warning.css">
    <script src="../js/session-timeout.js"></script>
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
                <a href="index.php">
                    <img src="../../images/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo">
                </a>
                <a href="index.php">
                    <img src="../../images/header-title.svg" alt="FITXBRAWL" class="logo-title">
                </a>
            </div>
            <nav class="nav-bar">
                <ul>
                    <li><a href="index.php" <?= $currentPage === 'home' ? 'class="active"' : '' ?>>Home</a></li>
                    <li><a href="<?= htmlspecialchars($membershipLink) ?>" <?= $currentPage === 'membership' ? 'class="active"' : '' ?>>Membership</a></li>
                    <li><a href="equipment.php" <?= $currentPage === 'equipment' ? 'class="active"' : '' ?>>Equipment</a></li>
                    <li><a href="products.php" <?= $currentPage === 'products' ? 'class="active"' : '' ?>>Products</a></li>
                    <li><a href="contact.php" <?= $currentPage === 'contact' ? 'class="active"' : '' ?>>Contact</a></li>
                    <li><a href="feedback.php" <?= $currentPage === 'feedback' ? 'class="active"' : '' ?>>Feedback</a></li>
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
                    <img src="../../images/account-icon.png" alt="Account" class="account-icon">
                </a>
            <?php endif; ?>
        </div>
    </header>
