<?php
// CRITICAL: Set anti-cache headers FIRST before ANY output
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Get user email before destroying session
$userEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;
$sessionActive = isset($_SESSION['email']) && !empty($_SESSION['email']);

// Log the logout action in database if user was logged in
if ($userEmail && $sessionActive) {
    try {
        // Update last logout time in database
        $query = "UPDATE users SET last_logout = NOW() WHERE email = ?";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            error_log("Logout - Prepare failed: " . $conn->error);
        } else {
            if (!$stmt->bind_param("s", $userEmail)) {
                error_log("Logout - Binding parameters failed: " . $stmt->error);
            } elseif (!$stmt->execute()) {
                error_log("Logout - Execute failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Logout - Exception: " . $e->getMessage());
    }
}

// Use SessionManager's logout method which handles all cleanup
// It will redirect to index.php (homepage) with cache-busting
SessionManager::logout('');
?>
