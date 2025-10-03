<?php
session_start();
require_once '../../includes/db_connect.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Handle profile picture upload
    $avatar = $_SESSION['avatar'] ?? '../../images/default-avatar.png';
    if (!empty($_FILES['avatar']['name'])) {
        $targetDir = "../../uploads/";
        $targetFile = $targetDir . basename($_FILES["avatar"]["name"]);
        move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile);
        $avatar = $targetFile;
    }

    // Update user in DB
    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    if ($hashedPassword) {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, avatar=? WHERE email=?");
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $avatar, $_SESSION['email']);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, avatar=? WHERE email=?");
        $stmt->bind_param("ssss", $username, $email, $avatar, $_SESSION['email']);
    }
    $stmt->execute();

    // Update session
    $_SESSION['name'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['avatar'] = $avatar;

    header("Location: user-profile.php"); // go back to profile
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../../public/css/pages/user-profile.css">
</head>
<body>
<main class="profile-edit-main">
    <h2>Edit Profile</h2>
    <form method="POST" enctype="multipart/form-data" class="profile-edit-form">
        
        <!-- Avatar -->
        <label for="avatar">Profile Picture</label>
        <input type="file" name="avatar" id="avatar" accept="image/*">

        <!-- Username -->
        <label for="username">Username</label>
        <input type="text" name="username" id="username" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>

        <!-- Email -->
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>"  readonly>

        <!-- Forgot Password -->
        <div class="change-password">
            <a href="change-password.php" class="btn-change-password">Change Password?</a>
        </div>

        <!-- Buttons -->
        <div class="form-actions">
            <button type="submit" class="btn-save">Save Changes</button>
            <a href="user_profile.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</main>
</body>
</html>