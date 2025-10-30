<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/mail_config.php';

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$error = '';
$success = '';
$generated_username = '';
$generated_password = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = $_POST['specialization'];
    $bio = trim($_POST['bio']);
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
    $status = $_POST['status'];

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($specialization)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM trainers WHERE email = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'A trainer with this email already exists.';
        } else {
            // Handle photo upload
            $photo = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../../uploads/trainers/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png'];

                if (in_array($file_ext, $allowed_ext)) {
                    $photo = uniqid('trainer_') . '.' . $file_ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo);
                } else {
                    $error = 'Only JPG, JPEG, and PNG files are allowed.';
                }
            }

            if (empty($error)) {
                // Start transaction
                $conn->begin_transaction();

                try {
                    // Insert trainer
                    $insert_query = "INSERT INTO trainers (name, email, phone, specialization, bio, photo, emergency_contact_name, emergency_contact_phone, status, password_changed)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("sssssssss", $name, $email, $phone, $specialization, $bio, $photo, $emergency_contact_name, $emergency_contact_phone, $status);

                    if (!$stmt->execute()) {
                        throw new Exception('Failed to add trainer.');
                    }

                    $trainer_id = $stmt->insert_id;

                    // Generate username from name (e.g., "John Doe" -> "john.doe")
                    $username_base = strtolower(str_replace(' ', '.', trim($name)));
                    $username_base = preg_replace('/[^a-z0-9._]/', '', $username_base); // Remove special chars

                    // Check if username exists and make it unique
                    $generated_username = $username_base;
                    $counter = 1;
                    while (true) {
                        $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
                        $check_user->bind_param("s", $generated_username);
                        $check_user->execute();
                        if ($check_user->get_result()->num_rows == 0) {
                            break;
                        }
                        $generated_username = $username_base . $counter;
                        $counter++;
                    }

                    // Generate default password: "Trainer" + trainer_id
                    $generated_password = "Trainer" . $trainer_id;
                    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

                    // Copy photo to avatars folder if exists
                    $avatar = $photo;
                    if ($photo && file_exists('../../../uploads/trainers/' . $photo)) {
                        $avatars_dir = '../../../uploads/avatars/';
                        if (!file_exists($avatars_dir)) {
                            mkdir($avatars_dir, 0777, true);
                        }
                        copy('../../../uploads/trainers/' . $photo, $avatars_dir . $photo);
                    } else {
                        $avatar = 'default-avatar.png';
                    }

                    // Create user account
                    $user_query = "INSERT INTO users (username, email, password, role, avatar, is_verified)
                                   VALUES (?, ?, ?, 'trainer', ?, 1)";
                    $stmt = $conn->prepare($user_query);
                    $stmt->bind_param("ssss", $generated_username, $email, $hashed_password, $avatar);

                    if (!$stmt->execute()) {
                        throw new Exception('Failed to create user account.');
                    }

                    // Log activity
                    $log_query = "INSERT INTO trainer_activity_log (trainer_id, admin_id, action, details) VALUES (?, ?, 'Added', ?)";
                    $details = "New trainer added: $name ($specialization) with username: $generated_username";
                    $stmt = $conn->prepare($log_query);
                    $stmt->bind_param("iis", $trainer_id, $admin_id, $details);
                    $stmt->execute();

                    // Send email with credentials
                    $email_sent = sendTrainerCredentialsEmail($email, $name, $generated_username, $generated_password);

                    // Commit transaction
                    $conn->commit();

                    // Store credentials in session for display
                    $_SESSION['new_trainer_username'] = $generated_username;
                    $_SESSION['new_trainer_password'] = $generated_password;
                    $_SESSION['new_trainer_name'] = $name;
                    $_SESSION['email_sent'] = $email_sent;

                    header('Location: trainers.php?success=added');
                    exit;

                } catch (Exception $e) {
                    // Rollback on error
                    $conn->rollback();
                    $error = $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Trainer - Admin Panel</title>
    <link rel="icon" type="image/png" href="../../../images/favicon-admin.png">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/trainer-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>Add New Trainer</h1>
                <p class="subtitle">Add a new trainer to your gym team</p>
            </div>
            <a href="trainers.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Trainers
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data" class="trainer-form">
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required
                                value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" placeholder="+63-917-XXX-XXXX" required
                                value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="specialization">Specialization <span class="required">*</span></label>
                            <select id="specialization" name="specialization" required>
                                <option value="">Select Specialization</option>
                                <option value="Gym" <?= isset($_POST['specialization']) && $_POST['specialization'] === 'Gym' ? 'selected' : '' ?>>Gym</option>
                                <option value="MMA" <?= isset($_POST['specialization']) && $_POST['specialization'] === 'MMA' ? 'selected' : '' ?>>MMA</option>
                                <option value="Boxing" <?= isset($_POST['specialization']) && $_POST['specialization'] === 'Boxing' ? 'selected' : '' ?>>Boxing</option>
                                <option value="Muay Thai" <?= isset($_POST['specialization']) && $_POST['specialization'] === 'Muay Thai' ? 'selected' : '' ?>>Muay Thai</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="4"
                            placeholder="Brief description of the trainer's experience and expertise..."><?= isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : '' ?></textarea>
                        <span class="form-hint">Optional: Brief background about the trainer</span>
                    </div>

                    <div class="form-group">
                        <label for="photo">Profile Photo</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg">
                        <span class="form-hint">Accepted formats: JPG, JPEG, PNG</span>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Emergency Contact</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                                value="<?= isset($_POST['emergency_contact_name']) ? htmlspecialchars($_POST['emergency_contact_name']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Phone</label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                                placeholder="+63-917-XXX-XXXX"
                                value="<?= isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : '' ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Status</h3>

                    <div class="form-group">
                        <label for="status">Initial Status <span class="required">*</span></label>
                        <select id="status" name="status" required>
                            <option value="Active" selected>Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="trainers.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Trainer
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>
