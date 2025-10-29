<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in and is a trainer
if (!SessionManager::isLoggedIn() || !isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit;
}

// Set avatar source
$avatarSrc = '../../../images/account-icon.svg';
if (isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../../images/account-icon.png";
}

// Set variables for header
$pageTitle = "Fit and Brawl - Trainer Dashboard";
$currentPage = "home";
$additionalCSS = ["../../css/pages/loggedin-homepage.css"];

// Include header
require_once '../../../includes/trainer_header.php';
?>

    <!--Main-->
    <main>
        <section class="homepage-hero">
            <div class="hero-content">
                <div class="hero-underline top-line"></div>
                <h1>
                    BUILD A <span class="yellow">BODY</span> THAT<span class="apostrophe">&#39;</span>S<br>
                    BUILT FOR <span class="yellow">BATTLE</span>
                </h1>
                <p class="hero-sub"><span class="sub-underline">Ready to train champions?</span></p>
                <a href="schedule.php" class="hero-btn">View Schedule</a>
            </div>
        </section>
    </main>

<?php require_once '../../../includes/trainer_footer.php'; ?>
