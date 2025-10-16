<?php
session_start();

// Redirect non-logged-in users to login page
if(!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

require_once '../../includes/db_connect.php';

// Check membership status for header
require_once '../../includes/membership_check.php';

// Fetch user data
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// TODO: Fetch real membership and activity data from database
// For now using mock data
$membershipPlan = "GLADIATOR";
$nextPayment = "November 15, 2025";
$gymStreak = 7; // days

$lastTrainingDate = "October 15, 2025";
$lastTrainingType = "Boxing";
$lastTrainerName = "Coach Thei";

// Determine avatar source
$hasCustomAvatar = $user['avatar'] !== 'default-avatar.png' && !empty($user['avatar']);
$avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($user['avatar']) : "../../images/profile-icon.svg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FitXBrawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/user-profile.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/header-dropdown.js"></script>
    <script src="../js/hamburger-menu.js"></script>
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
                    <img src="../../images/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo">
                </a>
                <a href="index.php">
                    <img src="../../images/header-title.svg" alt="FITXBRAWL" class="logo-title">
                </a>
            </div>
            <nav class="nav-bar">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="<?= $membershipLink ?>">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </nav>
            <div class="account-dropdown">
                <img src="<?= $avatarSrc ?>"
                     alt="Account" class="account-icon">
                <div class="dropdown-menu">
                    <a href="user_profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!--Main-->
    <main class="profile-main">
        <!-- Profile Header -->
        <section class="profile-header">
            <div class="profile-avatar-container">
                <img src="<?= $avatarSrc ?>"
                     alt="Profile Picture" class="profile-avatar <?= !$hasCustomAvatar ? 'default-icon' : '' ?>">
            </div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['username']) ?></h1>
                <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
                <div class="profile-actions">
                    <button class="btn-edit-profile" id="toggleEditBtn">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </section>

        <!-- Profile Content Grid -->
        <div class="profile-content">
            <!-- Membership Status -->
            <section class="profile-section">
                <h3>Membership Status</h3>
                <div class="info-row">
                    <span class="info-label">Membership Plan</span>
                    <span class="info-value highlight"><?= htmlspecialchars($membershipPlan) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Next Payment</span>
                    <span class="info-value"><?= htmlspecialchars($nextPayment) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Gym Streak</span>
                    <span class="info-value highlight">
                        <i class="fas fa-fire"></i> <?= $gymStreak ?> Days
                    </span>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="profile-section">
                <h3>Recent Activity</h3>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-header">
                            <span class="activity-title">Last Training Session</span>
                            <span class="activity-date"><?= htmlspecialchars($lastTrainingDate) ?></span>
                        </div>
                        <div class="activity-details">
                            <strong>Type:</strong> <?= htmlspecialchars($lastTrainingType) ?><br>
                            <strong>Trainer:</strong> <?= htmlspecialchars($lastTrainerName) ?>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

        <!-- Edit Profile Section (Initially Hidden) -->
        <section class="edit-profile-section" id="editProfileSection">
            <h3 style="color: #d5ba2b; font-family: 'zuume-rough-bold', sans-serif; font-size: 2rem; margin-bottom: 30px; text-transform: uppercase;">
                Edit Profile
            </h3>
            <form method="POST" action="update_profile.php" enctype="multipart/form-data" class="edit-profile-form">
                <!-- Avatar Upload -->
                <div class="form-group full-width">
                    <label>Profile Picture</label>
                    <div class="avatar-upload">
                        <img src="<?= $avatarSrc ?>"
                             alt="Avatar Preview" class="avatar-preview <?= !$hasCustomAvatar ? 'default-icon' : '' ?>" id="avatarPreview">
                        <div class="upload-btn-wrapper">
                            <div class="upload-btn">
                                <i class="fas fa-camera"></i> Choose Photo
                            </div>
                            <input type="file" name="avatar" id="avatarInput" accept="image/*">
                        </div>
                        <small class="file-size-hint">Maximum file size: 2MB</small>
                        <button type="button" class="btn-remove-avatar <?= $hasCustomAvatar ? 'show' : '' ?>" id="removeAvatarBtn">
                            <i class="fas fa-trash"></i> Remove Photo
                        </button>
                        <input type="hidden" name="remove_avatar" id="removeAvatarFlag" value="0">
                    </div>
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username"
                           value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email"
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <!-- New Password -->
                <div class="form-group">
                    <label for="new_password">New Password (Leave blank to keep current)</label>
                    <input type="password" name="new_password" id="new_password"
                           placeholder="Enter new password">
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

    <!--Footer-->
    <footer>
        <div class="container footer-flex">
            <div class="footer-logo-block">
                <img src="../../images/footer-title.png" alt="FITXBRAWL" class="footer-logo-title">
            </div>
            <div class="footer-menu-block">
                <div class="footer-menu-title">MENU</div>
                <ul class="footer-menu-list">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="membership.php">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </div>
            <div class="footer-contact-block">
                <div class="footer-contact-title">CONTACT</div>
                <div class="footer-contact-details">
                    1832 Oroquieta Rd, Santa Cruz, Manila,<br>
                    1008 Metro Manila<br><br>
                    Gmail: fitxbrawl@gmail.com
                </div>
            </div>
            <div class="footer-hours-block">
                <div class="footer-hours-title">OPENING HOURS</div>
                <div class="footer-hours-details">
                    Sun–Fri: 9AM to 10PM<br>
                    Saturday: 10AM to 7PM
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Fit X Brawl, All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/header-dropdown.js"></script>
    <script src="../js/user-profile.js"></script>
</body>
</html>
