<?php
require_once '../../includes/db_connect.php';
session_start();

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if token exists in DB
    $stmt = $conn->prepare("SELECT * FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Token found → Verify the account
        $user = $result->fetch_assoc();
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $update->bind_param("s", $user['id']); // Changed "i" to "s" for VARCHAR ID
        $update->execute();

        $_SESSION['success_message'] = "Your email has been verified! You can now log in.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['register_error'] = "Invalid or expired verification link.";
        header("Location: sign-up.php");
        exit();
    }
} else {
    $_SESSION['register_error'] = "No verification token provided.";
    header("Location: sign-up.php");
    exit();
}
?>
