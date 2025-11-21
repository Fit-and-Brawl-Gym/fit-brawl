<?php
require_once '../../../includes/init.php';
require_once '../../../includes/mail_config.php';
require_once '../../../includes/file_upload_security.php';
require_once '../../../includes/activity_logger.php';

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize activity logger
ActivityLogger::init($conn);

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
    $status = 'Active'; // Default status is always Active

    // Get day-offs from form
    $day_offs = isset($_POST['day_offs']) ? $_POST['day_offs'] : [];
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($specialization)) {
        $error = 'Please fill in all required fields.';
    } elseif (!preg_match("/^9[0-9]{9}$/", $phone)) {
        $error = 'Phone number must start with 9 and be 10 digits (e.g., 9171234567).';
    } elseif (count($day_offs) !== 2) {
        $error = 'You must select exactly 2 days off per week.';
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
            // Handle photo upload securely
            $photo = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../../uploads/trainers/';
                $uploadHandler = SecureFileUpload::imageUpload($upload_dir, 5);

                $result = $uploadHandler->uploadFile($_FILES['photo']);

                if ($result['success']) {
                    $photo = $result['filename'];
                } else {
                    $error = $result['message'];
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

                    // Insert day-off schedule
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $day_off_insert = "INSERT INTO trainer_day_offs (trainer_id, day_of_week, is_day_off) VALUES (?, ?, ?)";
                    $day_stmt = $conn->prepare($day_off_insert);
                    
                    foreach ($days as $day) {
                        $is_day_off = in_array($day, $day_offs) ? 1 : 0;
                        $day_stmt->bind_param("isi", $trainer_id, $day, $is_day_off);
                        $day_stmt->execute();
                    }
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
                            mkdir($avatars_dir, 0750, true);
                        }
                        copy('../../../uploads/trainers/' . $photo, $avatars_dir . $photo);
                    } else {
                        $avatar = 'default-avatar.png';
                    }

                    // Create user account with email as username
                    $user_query = "INSERT INTO users (username, email, password, role, avatar, is_verified)
                                VALUES (?, ?, ?, 'trainer', ?, 1)";
                    $stmt = $conn->prepare($user_query);
                    $stmt->bind_param("ssss", $generated_username, $email, $hashed_password, $avatar);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to create user account.');
                    }

                    // Log activity
                    $log_query = "INSERT INTO trainer_activity_log (trainer_id, admin_id, action, details) VALUES (?, ?, 'Added', ?)";
                    $day_offs_str = implode(', ', $day_offs);
                    $details = "New trainer added: $name ($specialization) with username: $generated_username. Day-offs: $day_offs_str";
                    $stmt = $conn->prepare($log_query);
                    $stmt->bind_param("iis", $trainer_id, $admin_id, $details);
                    $stmt->execute();

                    // Log to main activity log
                    ActivityLogger::log('trainer_created', $name, $trainer_id, "New trainer '$name' (#$trainer_id) added with specialization: $specialization. Day-offs: $day_offs_str");

                    // Send email with credentials
                    $email_sent = sendTrainerCredentialsEmail($email, $name, $email, $generated_password);


                    // Commit transaction
                    $conn->commit();

                    // Store credentials in session for display
                    $_SESSION['new_trainer_username'] = $email;
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
    <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/trainer-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
<main>
<!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="admin-main">
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
                            <div class="phone-input-wrapper">
                                <span class="phone-prefix">+63</span>
                                <input type="tel" id="phone" name="phone" placeholder="9123456789" required
                                    value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                                    maxlength="10" pattern="9[0-9]{9}"
                                    title="Phone number must start with 9 and be 10 digits">
                            </div>
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
                    <h3 class="section-title">Weekly Schedule <span class="required">*</span></h3>
                    <p class="section-description">Select exactly 2 days off per week for this trainer</p>

                    <div class="days-grid">
                        <?php 
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        $selected_days = isset($_POST['day_offs']) ? $_POST['day_offs'] : [];
                        foreach ($days as $day): 
                            $is_checked = in_array($day, $selected_days);
                            $short_day = substr($day, 0, 3);
                        ?>
                            <label class="day-checkbox <?= $is_checked ? 'checked' : '' ?>">
                                <input type="checkbox" 
                                       name="day_offs[]" 
                                       value="<?= $day ?>" 
                                       <?= $is_checked ? 'checked' : '' ?>>
                                <div class="day-label">
                                    <span class="day-full"><?= $day ?></span>
                                    <span class="day-short"><?= $short_day ?></span>
                                </div>
                                <div class="checkbox-indicator">
                                    <i class="fas fa-times"></i>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="day-off-counter">
                        <i class="fas fa-calendar-xmark"></i>
                        <span id="dayOffCount">0</span> day(s) off selected (Required: 2)
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
    </div>
</main>

    <script>
        // Day-off selection handling
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.day-checkbox input[type="checkbox"]');
            const counter = document.getElementById('dayOffCount');
            const form = document.querySelector('.trainer-form');

            function updateDayOffCount() {
                const checkedCount = document.querySelectorAll('.day-checkbox input[type="checkbox"]:checked').length;
                counter.textContent = checkedCount;
                
                // Update visual state
                checkboxes.forEach(checkbox => {
                    const label = checkbox.closest('.day-checkbox');
                    if (checkbox.checked) {
                        label.classList.add('checked');
                    } else {
                        label.classList.remove('checked');
                    }
                });

                // Update counter color
                if (checkedCount === 2) {
                    counter.parentElement.style.color = 'var(--admin-status-success)';
                } else {
                    counter.parentElement.style.color = 'var(--admin-status-danger)';
                }
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateDayOffCount);
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                const checkedCount = document.querySelectorAll('.day-checkbox input[type="checkbox"]:checked').length;
                
                if (checkedCount !== 2) {
                    e.preventDefault();
                    alert('You must select exactly 2 days off per week.');
                    return false;
                }
            });

            // Initialize count
            updateDayOffCount();

            // Phone number validation
            const phoneInput = document.getElementById('phone');
            
            if (phoneInput) {
                // Only allow numbers
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value;
                    
                    // Remove all non-digit characters
                    value = value.replace(/\D/g, '');
                    
                    // Ensure it starts with 9
                    if (value.length > 0 && value[0] !== '9') {
                        value = '9' + value;
                    }
                    
                    // Limit to 10 digits
                    if (value.length > 10) {
                        value = value.substring(0, 10);
                    }
                    
                    e.target.value = value;
                });
                
                // Prevent non-numeric keypresses
                phoneInput.addEventListener('keypress', function(e) {
                    const char = String.fromCharCode(e.which);
                    if (!/[0-9]/.test(char)) {
                        e.preventDefault();
                    }
                });
                
                // Auto-add 9 if empty and user starts typing
                phoneInput.addEventListener('focus', function(e) {
                    if (e.target.value === '') {
                        e.target.value = '9';
                    }
                });
                
                // Validate on blur
                phoneInput.addEventListener('blur', function(e) {
                    const value = e.target.value;
                    if (value === '9' || value === '') {
                        e.target.value = '';
                    } else if (value.length < 10) {
                        e.target.setCustomValidity('Phone number must be 10 digits starting with 9');
                    } else {
                        e.target.setCustomValidity('');
                    }
                });
                
                // Clear custom validity on input
                phoneInput.addEventListener('input', function(e) {
                    e.target.setCustomValidity('');
                });
            }
        });
    </script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
</body>

</html>
