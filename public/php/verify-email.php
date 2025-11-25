<?php
require_once '../../includes/db_connect.php';
session_start();

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    error_log("Email verification attempt with token: " . substr($token, 0, 10) . "...");

    // Check if token exists in DB
    $stmt = $conn->prepare("SELECT id, email, is_verified, verification_token FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Token found → Verify the account
        $user = $result->fetch_assoc();
        
        error_log("Token found for user: " . $user['email'] . ", already verified: " . ($user['is_verified'] ? 'yes' : 'no'));
        
        // Check if already verified
        if ($user['is_verified'] == 1) {
            $_SESSION['success_message'] = "Your email is already verified! You can log in.";
            header("Location: login.php");
            exit();
        }
        
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $update->bind_param("s", $user['id']);
        
        if ($update->execute()) {
            error_log("Successfully verified user: " . $user['email']);
            $_SESSION['success_message'] = "Your email has been verified! You can now log in.";
            header("Location: login.php");
            exit();
        } else {
            error_log("Failed to update verification status for user: " . $user['email']);
            $_SESSION['register_error'] = "Failed to verify email. Please try again.";
            header("Location: sign-up.php");
            exit();
        }
    } else {
        error_log("No user found with verification token: " . substr($token, 0, 10) . "...");
        $_SESSION['register_error'] = "Invalid or expired verification link.";
        header("Location: sign-up.php");
        exit();
    }
} else {
    error_log("No verification token provided in URL");
    $_SESSION['register_error'] = "No verification token provided.";
    header("Location: sign-up.php");
    exit();
}
?>
