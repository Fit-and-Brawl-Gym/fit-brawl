<?php
session_start();

// Redirect non-logged-in users to login page
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: ../login.php");
    exit;
}

require_once '../../../includes/db_connect.php';
require_once '../../../includes/session_manager.php';

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

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['name'] ?? '';

    // Get trainer details from trainers table
    $trainer_query = "SELECT id, name, specialization FROM trainers WHERE name = ? OR LOWER(name) = LOWER(?)";
    $stmt = $conn->prepare($trainer_query);
    if ($stmt) {
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $trainer_id = $row['id'];
            $trainer_name = $row['name'];
            $trainer_specialization = $row['specialization'];
        }
        $stmt->close();
    }

    // Calculate next payout (example: 1st of next month)
    $next_month = date('F j, Y', strtotime('first day of next month'));
    $next_payout = $next_month;
}

// Determine avatar source
$hasCustomAvatar = $user['avatar'] !== 'default-avatar.png' && !empty($user['avatar']);
$avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($user['avatar']) : "../../../images/account-icon.png";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FitXBrawl Trainer</title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/pages/user-profile.css">
    <link rel="stylesheet" href="../../css/components/footer.css">
    <link rel="stylesheet" href="../../css/components/header.css">
    <link rel="shortcut icon" href="../../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
        rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../../js/header-dropdown.js"></script>
    <script src="../../js/hamburger-menu.js"></script>
    <?php if(SessionManager::isLoggedIn()): ?>
    <link rel="stylesheet" href="../../css/components/session-warning.css">
    <script src="../../js/session-timeout.js"></script>
    <?php endif; ?>
</head>

<body>
    <!--Header-->
    <header>
        <div class="wrapper">
            <button class="hamburger-menu" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="title">
                <a href="index.php">
                    <img src="../../../images/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo">
                </a>
                <a href="index.php">
                    <img src="../../../images/header-title.svg" alt="FITXBRAWL" class="logo-title">
                </a>
            </div>
            <nav class="nav-bar">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="schedule.php">Schedule</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </nav>
            <div class="account-dropdown">
                <img src="<?= $avatarSrc ?>" alt="Account" class="account-icon">
                <div class="dropdown-menu">
                    <a href="profile.php">Profile</a>
                    <a href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!--Main-->
    <main class="profile-main">
        <!-- Profile Header -->
        <section class="profile-header">
            <div class="profile-avatar-container">
                <img src="<?= $avatarSrc ?>" alt="Profile Picture"
                    class="profile-avatar <?= !$hasCustomAvatar ? 'default-icon' : '' ?>">
            </div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['username']) ?></h1>
                <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
                <p class="profile-role"><i class="fas fa-dumbbell"></i> Trainer</p>
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
                            <input type="file" name="avatar" id="avatarInput" accept="image/*">
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
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>"
                        required>
                </div>

                <!-- New Password -->
                <div class="form-group">
                    <label for="new_password">New Password (Leave blank to keep current)</label>
                    <input type="password" name="new_password" id="new_password" placeholder="Enter new password">
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password"
                        placeholder="Confirm new password">
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

    <script src="../../js/user-profile.js"></script>

<?php require_once '../../../includes/trainer_footer.php'; ?>
