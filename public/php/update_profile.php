<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/file_upload_security.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';
require_once __DIR__ . '/../../includes/password_policy.php';
require_once __DIR__ . '/../../includes/password_history.php';
require_once __DIR__ . '/../../includes/encryption.php'; // Add encryption support

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$profileRedirect = (isset($_SESSION['role']) && $_SESSION['role'] === 'trainer') ? 'trainer/profile.php' : 'user_profile.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        $_SESSION['error'] = "Your session expired. Please try again.";
        header("Location: $profileRedirect");
        exit;
    }

    $username = test_input($_POST['username']);
    $email = test_input($_POST['email']);
    $currentPassword = test_input($_POST['current_password']);
    $newPassword = test_input($_POST['new_password']);
    $confirmPassword = test_input($_POST['confirm_password']);
    $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';
    $passwordHistoryContext = null;

    if (empty($username) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please provide a valid name and email.";
        header("Location: $profileRedirect");
        exit;
    }

    // Get current user
    $currentEmail = $_SESSION['email'];

    // Validate passwords match if provided
    if (!empty($newPassword)) {
        // Check if current password is provided
        if (empty($currentPassword)) {
            $_SESSION['error'] = "Current password is required to change your password.";
            header("Location: $profileRedirect");
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Passwords do not match.";
            header("Location: $profileRedirect");
            exit;
        }

        $passwordErrors = PasswordPolicy::validate($newPassword);
        if (!empty($passwordErrors)) {
            $_SESSION['error'] = implode("<br>", $passwordErrors);
            header("Location: $profileRedirect");
            exit;
        }

        // Get current password hash from database
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $currentEmail);
    $stmt->execute();
    $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $currentPasswordHash = $row['password'];
            $userId = $row['id'] ?? null;

            // Verify current password is correct
            if (!password_verify($currentPassword, $currentPasswordHash)) {
                $_SESSION['error'] = "Current password is incorrect.";
                $stmt->close();
                header("Location: $profileRedirect");
                exit;
            }

            // Verify if new password matches the current password
            if (password_verify($newPassword, $currentPasswordHash)) {
                $_SESSION['error'] = "New password cannot be the same as your current password.";
                $stmt->close();
                header("Location: $profileRedirect");
                exit;
            }

            if ($userId && PasswordHistory::hasBeenUsed($conn, $userId, $newPassword)) {
                $_SESSION['error'] = "Please choose a password you haven't used recently.";
                $stmt->close();
                header("Location: $profileRedirect");
                exit;
            }

            if ($userId) {
                $passwordHistoryContext = [
                    'user_id' => $userId,
                    'hash' => $currentPasswordHash
                ];
            }
        }
        $stmt->close();
    }

    // Handle avatar upload or removal
    $avatar = null;
    if ($removeAvatar) {
        // Set to default avatar
        $avatar = 'default-avatar.png';
    } elseif (!empty($_FILES['avatar']['name'])) {
        // Use absolute path to uploads directory
        $targetDir = __DIR__ . "/../../uploads/avatars/";

        // Ensure directory exists with proper permissions
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        // Try to ensure writable permissions (may fail in some environments)
        @chmod($targetDir, 0775);

        $uploadHandler = SecureFileUpload::imageUpload($targetDir, 2);

        $result = $uploadHandler->uploadFile($_FILES['avatar']);

        if ($result['success']) {
            $avatar = $result['filename'];
        } else {
            $_SESSION['error'] = $result['message'];
            header("Location: $profileRedirect");
            exit;
        }
    }

    // Encrypt email before updating
    $encryptedEmail = Encryption::encrypt($email);

    // Build update query
    if (!empty($newPassword) && $avatar) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, email_encrypted=?, password=?, avatar=? WHERE email=?");
        $stmt->bind_param("ssssss", $username, $email, $encryptedEmail, $hashedPassword, $avatar, $currentEmail);
    } elseif (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, email_encrypted=?, password=? WHERE email=?");
        $stmt->bind_param("sssss", $username, $email, $encryptedEmail, $hashedPassword, $currentEmail);
    } elseif ($avatar) {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, email_encrypted=?, avatar=? WHERE email=?");
        $stmt->bind_param("sssss", $username, $email, $encryptedEmail, $avatar, $currentEmail);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, email_encrypted=? WHERE email=?");
        $stmt->bind_param("ssss", $username, $email, $encryptedEmail, $currentEmail);
    }

    $passwordChanged = !empty($newPassword);
    if ($stmt->execute()) {
        if ($passwordChanged && $passwordHistoryContext) {
            PasswordHistory::record($conn, $passwordHistoryContext['user_id'], $passwordHistoryContext['hash']);
        }
        // Update session
        $_SESSION['name'] = $username;
        $_SESSION['email'] = $email;
        if ($avatar) {
            $_SESSION['avatar'] = $avatar;
        }

        // If password was changed and user is a trainer, mark password as changed
        if ($passwordChanged && isset($_SESSION['role']) && $_SESSION['role'] === 'trainer') {
            $update_trainer = $conn->prepare("UPDATE trainers SET password_changed = 1 WHERE email = ?");
            $update_trainer->bind_param("s", $email);
            $update_trainer->execute();
        }

        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }

    // Redirect based on role
    header("Location: $profileRedirect");
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
