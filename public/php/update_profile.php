<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/file_upload_security.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = test_input($_POST['username']);
    $email = test_input($_POST['email']);
    $newPassword = test_input($_POST['new_password']);
    $confirmPassword = test_input($_POST['confirm_password']);
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
        $uploadHandler = SecureFileUpload::imageUpload($targetDir, 2);

        $result = $uploadHandler->uploadFile($_FILES['avatar']);

        if ($result['success']) {
            $avatar = $result['filename'];
        } else {
            $_SESSION['error'] = $result['message'];
            header("Location: user_profile.php");
            exit;
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

        // If password was changed and user is a trainer, mark password as changed
        if (!empty($newPassword) && isset($_SESSION['role']) && $_SESSION['role'] === 'trainer') {
            $update_trainer = $conn->prepare("UPDATE trainers SET password_changed = 1 WHERE email = ?");
            $update_trainer->bind_param("s", $email);
            $update_trainer->execute();
        }

        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }

    // Redirect based on role
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'trainer') {
        header("Location: trainer/profile.php");
    } else {
        header("Location: user_profile.php");
    }
    exit;
}
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>
