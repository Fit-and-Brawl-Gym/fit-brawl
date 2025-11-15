<?php
session_start();

// Redirect non-logged-in users to login page
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: ../login.php");
    exit;
}

require_once '../../../includes/db_connect.php';
require_once '../../../includes/session_manager.php';
require_once __DIR__ . '/../../../includes/config.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in and is a trainer
if (!SessionManager::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Fetch user data
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get trainer information
$trainer_id = null;
$trainer_name = '';
$trainer_specialization = '';
$next_payout = 'N/A';
$password_changed = true; // Default to true to hide notification if not found

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    // Get trainer details from trainers table by EMAIL (since username != trainer name)
    $trainer_query = "SELECT id, name, specialization, password_changed FROM trainers WHERE email = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($trainer_query);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $trainer_id = $row['id'];
            $trainer_name = $row['name'];
            $trainer_specialization = $row['specialization'];
            $password_changed = (bool) $row['password_changed'];
        }
        $stmt->close();
        
    }

    // Calculate next payout (example: 1st of next month)
    $next_month = date('F j, Y', strtotime('first day of next month'));
    $next_payout = $next_month;
}

// Determine avatar source
$hasCustomAvatar = $user['avatar'] !== 'default-avatar.png' && !empty($user['avatar']);
$avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($user['avatar']) : "../../../images/account-icon.svg";

// Set variables for header
$pageTitle = "Trainer Profile - Fit and Brawl";
$currentPage = "profile";
$additionalCSS = [PUBLIC_PATH . "/css/pages/user-profile.css"];

// Include header
require_once '../../../includes/trainer_header.php';
?>

<!--Main-->
<main class="profile-main">
    <!-- Password Change Notification -->
    <?php if (!$password_changed): ?>
        <div class="alert alert-warning"
            style="background-color: #fff3cd; border-left: 4px solid #d5ba2b; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: center; gap: 15px;">
            <i class="fa-solid fa-exclamation-triangle" style="font-size: 24px; color: #856404;"></i>
            <div style="flex: 1;">
                <strong style="color: #856404; display: block; margin-bottom: 5px;">Security Notice: Default Password
                    Detected</strong>
                <p style="margin: 0; color: #856404;">
                    You are currently using the default password assigned by the administrator.
                    For your account security, please change your password immediately using the form below.
                </p>
            </div>
            <button
                onclick="document.getElementById('toggleEditBtn').click(); document.getElementById('new_password').focus();"
                style="background-color: #d5ba2b; color: #002f3f; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                <i class="fa-solid fa-key"></i> Change Now
            </button>
        </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="profile-avatar-container">
            <img src="<?= $avatarSrc ?>" alt="Profile Picture"
                class="profile-avatar <?= !$hasCustomAvatar ? 'default-icon' : '' ?>">
        </div>
        <div class="profile-info">
            <h2 class="profile-username"><?= htmlspecialchars($trainer_name ?: $user['username']) ?></h2>
            <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
            <p class="profile-role"> Trainer</p>
            <div class="profile-actions">
                <button class="btn-edit-profile" id="toggleEditBtn">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
                <a href="../logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </section>

    <!-- Profile Content Grid -->
    <div class="profile-content">
        <!-- Trainer Status -->
        <section class="profile-section">
            <h3>Trainer Status</h3>
            <div class="info-row">
                <span class="info-label">Class Type</span>
                <span class="info-value highlight"><?= htmlspecialchars($trainer_specialization ?: 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Next Payout</span>
                <span class="info-value"><?= htmlspecialchars($next_payout) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Trainer ID</span>
                <span class="info-value">#<?= htmlspecialchars($trainer_id ?: 'N/A') ?></span>
            </div>
        </section>

        <!-- Account Information -->
        <section class="profile-section">
            <h3>Account Information</h3>
            <ul class="activity-list">
                <li class="activity-item">
                    <div class="activity-details">
                        <strong>Account Type:</strong> Trainer<br>
                        <strong>Member Since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?><br>
                        <strong>Status:</strong> <span style="color: var(--color-success);">Active</span>
                    </div>
                </li>
            </ul>
        </section>
    </div>

    <!-- Edit Profile Section (Initially Hidden) -->
    <section class="edit-profile-section" id="editProfileSection">
        <h3
            style="color: #d5ba2b; font-family: 'zuume-rough-bold', sans-serif; font-size: 2rem; margin-bottom: 30px; text-transform: uppercase;">
            Edit Profile
        </h3>
        <form method="POST" action="../update_profile.php" enctype="multipart/form-data" class="edit-profile-form">
            <!-- Avatar Upload -->
            <div class="form-group full-width">
                <label>Profile Picture</label>
                <div class="avatar-upload">
                    <img src="<?= $avatarSrc ?>" alt="Avatar Preview"
                        class="avatar-preview <?= !$hasCustomAvatar ? 'default-icon' : '' ?>" id="avatarPreview">
                    <div class="upload-btn-wrapper">
                        <div class="upload-btn">
                            <i class="fas fa-camera"></i> Choose Photo
                        </div>
                        <input type="file" name="avatar" id="avatarInput" accept="image/png,image/jpeg,image/jpg">
                    </div>
                    <small class="file-size-hint">Maximum file size: 2MB</small>
                    <button type="button" class="btn-remove-avatar <?= $hasCustomAvatar ? 'show' : '' ?>"
                        id="removeAvatarBtn">
                        <i class="fas fa-trash"></i> Remove Photo
                    </button>
                    <input type="hidden" name="remove_avatar" id="removeAvatarFlag" value="0">
                </div>
            </div>

            <!-- Username -->
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>"
                    required>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <!-- New Password -->
            <div class="form-group">
                <label for="new_password">New Password (Leave blank to keep current)</label>
                <input type="password" name="new_password" id="new_password" placeholder="Enter new password">
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password">
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="btn-cancel" id="cancelEditBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </section>
</main>

<script src="<?= PUBLIC_PATH ?>/js/user-profile.js"></script>

<?php require_once '../../../includes/trainer_footer.php'; ?>
