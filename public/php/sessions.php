<?php
/**
 * Active Sessions Management Page
 * Allows users to view and revoke their active sessions
 */
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/session_tracker.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Initialize session tracker
SessionTracker::init($conn);

$pageTitle = "Active Sessions - Fit and Brawl";
$currentPage = "sessions";

// Set additional files for header
$additionalCSS = [];
$additionalJS = ['../js/sessions.js'];

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>

<main class="container" style="max-width: 900px; margin: 40px auto; padding: 20px;">
    <div class="page-header" style="margin-bottom: 30px;">
        <h1 style="font-size: 28px; margin-bottom: 8px;">Active Sessions</h1>
        <p style="color: #666; font-size: 14px;">Manage your active sessions across different devices</p>
    </div>

    <div class="sessions-container" id="sessionsContainer">
        <div style="text-align: center; padding: 40px; color: #999;">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 12px;"></i>
            <p>Loading sessions...</p>
        </div>
    </div>
</main>

<script>
// Make CSRF token available
const CSRF_TOKEN = '<?= htmlspecialchars(CSRFProtection::generateToken()) ?>';
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

