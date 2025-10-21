<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Get user email before destroying session
$userEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;

if ($userEmail) {
    // Update last logout time in database
    $query = "UPDATE users SET last_logout = NOW() WHERE email = ?";
    
    // Add error checking for prepare statement
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        if ($stmt->bind_param("s", $userEmail)) {
            $stmt->execute();
        } else {
            error_log("Binding parameters failed: " . $stmt->error);
        }
        $stmt->close();
    }
}

// Clear session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
