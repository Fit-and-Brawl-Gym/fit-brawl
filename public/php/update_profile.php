<?php
session_start();
require_once '../../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';

    // Get current user
    $currentEmail = $_SESSION['email'];

    // Validate passwords match if provided
    if (!empty($newPassword)) {
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Passwords do not match.";
            header("Location: user_profile.php");
            exit;
        }
    }

    // Handle avatar upload or removal
    $avatar = null;
    if ($removeAvatar) {
        // Set to default avatar
        $avatar = 'default-avatar.png';
    } elseif (!empty($_FILES['avatar']['name'])) {
        $targetDir = "../../uploads/avatars/";

        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $fileExtension;
        $targetFile = $targetDir . $newFileName;

        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExtension, $allowedTypes)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
                $avatar = $newFileName;
            }
        }
    }

    // Build update query
    if (!empty($newPassword) && $avatar) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, avatar=? WHERE email=?");
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $avatar, $currentEmail);
    } elseif (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=? WHERE email=?");
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $currentEmail);
    } elseif ($avatar) {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, avatar=? WHERE email=?");
        $stmt->bind_param("ssss", $username, $email, $avatar, $currentEmail);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE email=?");
        $stmt->bind_param("sss", $username, $email, $currentEmail);
    }

    if ($stmt->execute()) {
        // Update session
        $_SESSION['name'] = $username;
        $_SESSION['email'] = $email;
        if ($avatar) {
            $_SESSION['avatar'] = $avatar;
        }

        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }

    header("Location: user_profile.php");
    exit;
}
?>
