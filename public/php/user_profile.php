<?php
session_start();

// Redirect non-logged-in users to login page
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../../includes/db_connect.php';

// Check membership status for header
require_once __DIR__ . '/../../includes/membership_check.php';

$hasActiveMembership = false;
$hasAnyRequest = false;
$gracePeriodDays = 3;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');

    // Check user_memberships table
    if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT request_status, membership_status, end_date, plan_name
            FROM user_memberships
            WHERE user_id = ?
            ORDER BY date_submitted DESC
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $requestStatus = $row['request_status'] ?? null;
                $membershipStatus = $row['membership_status'] ?? null;
                $endDate = $row['end_date'] ?? null;

                $hasAnyRequest = true;

                if ($requestStatus === 'approved' && $endDate) {
                    $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

                    if ($expiryWithGrace >= $today) {

                        $hasActiveMembership = true;
                        $hasAnyRequest = false;
                    }
                }
            }

            $stmt->close();
        }


    } elseif ($conn->query("SHOW TABLES LIKE 'subscriptions'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT status, end_date
            FROM subscriptions
            WHERE user_id = ? AND status IN ('Approved','approved')
            ORDER BY date_submitted DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $status = strtolower($row['status']);
                $endDate = $row['end_date'] ?? null;
                $hasAnyRequest = true;

                if ($status === 'approved' && $endDate) {
                    $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

                    if ($expiryWithGrace >= $today) {
                        $hasActiveMembership = true;
                        $hasAnyRequest = false;
                    }
                }
            }

            $stmt->close();
        }
    }
}


if ($hasActiveMembership) {
    $membershipLink = 'reservations.php';
} elseif ($hasAnyRequest) {
    $membershipLink = 'membership-status.php';
} else {
    $membershipLink = 'membership.php';
}
require_once __DIR__ . '/../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Fetch user data
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// TODO: Fetch real membership and activity data from database
// For now using mock data

// Fetch membership plan and next payment from user_memberships or subscriptions
$membershipPlan = isset($row['plan_name']) ? $row['plan_name'] : "N/A";
$nextPayment = isset($endDate) ? date('F j, Y', strtotime($endDate)) : "N/A";

// Removed gym streak calculation per request

// Fetch last training session
$lastTrainingDate = $lastTrainingType = $lastTrainerName = "N/A";
$activityQuery = $conn->prepare("SELECT activity_date, activity_type, trainer_name FROM training_sessions WHERE user_id = ? ORDER BY activity_date DESC LIMIT 1");
if ($activityQuery) {
    $activityQuery->bind_param("i", $user_id);
    $activityQuery->execute();
    $activityResult = $activityQuery->get_result();
    if ($row = $activityResult->fetch_assoc()) {
        $lastTrainingDate = date('F j, Y', strtotime($row['activity_date']));
        $lastTrainingType = $row['activity_type'];
        $lastTrainerName = $row['trainer_name'];
    }
    $activityQuery->close();
}

$pageTitle = "My Profile - Fit and Brawl";
$currentPage = "user_profile";
// Determine avatar source
$hasCustomAvatar = $user['avatar'] !== 'default-avatar.png' && !empty($user['avatar']);
$avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($user['avatar']) : "../../images/account-icon.svg";

// Set additional files for header
$additionalCSS = ['../css/pages/user-profile.css'];
$additionalJS = ['../js/user-profile.js'];

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>

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

            </section>

            <!-- Recent Activity -->
            <section class="profile-section">
                <h3>Recent Activity</h3>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-details">
                            <strong>Last Training Session:</strong> <?= htmlspecialchars($lastTrainingDate) ?> <br>
                            <strong>Type:</strong> <?= htmlspecialchars($lastTrainingType) ?><br>
                            <strong>Trainer:</strong> <?= htmlspecialchars($lastTrainerName) ?>
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
            <form method="POST" action="update_profile.php" enctype="multipart/form-data" class="edit-profile-form">
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

                <!-- Current Password (required to change password) -->
                <div class="form-group password-field full-width" id="currentPasswordGroup" style="display: none;">
                    <label for="current_password">Current Password (Required to change password)</label>
                    <input type="password" name="current_password" id="current_password" placeholder="Enter current password">
                    <div class="current-password-warning" id="currentPasswordWarning" aria-live="polite"></div>
                </div>

                <!-- New Password -->
                <div class="form-group password-field new-password">
                    <label for="new_password">New Password (Leave blank to keep current)</label>
                    <div class="input-group-wrapper">
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password">
                        <div class="password-requirements-modal" id="profilePasswordRequirements" aria-hidden="true">
                            <div class="password-requirements-header">
                                <h4>Password Requirements</h4>
                            </div>
                            <div class="password-requirements-list">
                                <div class="requirement-item" data-req="length">
                                    <span class="requirement-icon">•</span>
                                    <span class="requirement-text">At least 8 characters</span>
                                </div>
                                <div class="requirement-item" data-req="uppercase">
                                    <span class="requirement-icon">•</span>
                                    <span class="requirement-text">One uppercase letter</span>
                                </div>
                                <div class="requirement-item" data-req="lowercase">
                                    <span class="requirement-icon">•</span>
                                    <span class="requirement-text">One lowercase letter</span>
                                </div>
                                <div class="requirement-item" data-req="number">
                                    <span class="requirement-icon">•</span>
                                    <span class="requirement-text">One number</span>
                                </div>
                                <div class="requirement-item" data-req="special">
                                    <span class="requirement-icon">•</span>
                                    <span class="requirement-text">One special (!@#$%^&*)</span>
                                </div>
                                <div class="same-as-current-warning" id="sameAsCurrentWarning">
                                    ⚠️ Cannot be the same as current password
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group password-field confirm-password">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-group-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password"
                            placeholder="Confirm new password">
                        <div class="password-match-message" id="profilePasswordMatch" aria-hidden="true"></div>
                    </div>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

    <script src="../js/header-dropdown.js"></script>
    <script src="../js/user-profile.js"></script>
</body>

</html>
