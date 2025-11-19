<?php
require_once '../../../includes/init.php';
require_once '../../../includes/file_upload_security.php';
require_once '../../../includes/activity_logger.php';
require_once '../../../includes/csrf_protection.php';

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

// Get trainer ID
$trainer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$trainer_id) {
    header('Location: trainers.php');
    exit;
}

// Fetch trainer details
$query = "SELECT * FROM trainers WHERE id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();
$trainer = $result->fetch_assoc();

if (!$trainer) {
    header('Location: trainers.php?error=notfound');
    exit;
}

// Fetch current day-offs
$day_offs_query = "SELECT day_of_week FROM trainer_day_offs WHERE trainer_id = ? AND is_day_off = TRUE";
$stmt = $conn->prepare($day_offs_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$day_offs_result = $stmt->get_result();
$current_day_offs = [];
while ($row = $day_offs_result->fetch_assoc()) {
    $current_day_offs[] = $row['day_of_week'];
}

// Determine current "default" shift (most common active shift_type) to preselect in form
$current_default_shift = 'morning';
$shift_mode_query = "SELECT shift_type, COUNT(*) AS cnt FROM trainer_shifts WHERE trainer_id = ? AND is_active = 1 GROUP BY shift_type ORDER BY cnt DESC LIMIT 1";
$stmt = $conn->prepare($shift_mode_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$shift_mode_res = $stmt->get_result();
if ($row = $shift_mode_res->fetch_assoc()) {
    $current_default_shift = $row['shift_type'] ?: 'morning';
}

// Optionally fetch existing shift break times for informational display (take first active shift of that type)
$current_break_start = null;
$current_break_end = null;
$break_query = "SELECT break_start_time, break_end_time FROM trainer_shifts WHERE trainer_id = ? AND is_active = 1 AND shift_type = ? LIMIT 1";
$stmt = $conn->prepare($break_query);
$stmt->bind_param("is", $trainer_id, $current_default_shift);
$stmt->execute();
$break_res = $stmt->get_result();
if ($b = $break_res->fetch_assoc()) {
    $current_break_start = $b['break_start_time'];
    $current_break_end = $b['break_end_time'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $specialization = $_POST['specialization'];
        $bio = trim($_POST['bio']);
        $emergency_contact_name = trim($_POST['emergency_contact_name']);
        $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
        $status = $_POST['status'];
        $day_offs = isset($_POST['day_offs']) ? $_POST['day_offs'] : [];

        // Accept a single fixed shift applied to all non-day-off days
        $default_shift = isset($_POST['default_shift']) ? $_POST['default_shift'] : $current_default_shift;
        $valid_shifts = ['morning', 'afternoon', 'night', 'none'];
        if (!in_array($default_shift, $valid_shifts, true)) {
            $default_shift = 'none';
        }

        // Validate required fields
        if (empty($name) || empty($email) || empty($phone) || empty($specialization)) {
            $error = 'Please fill in all required fields.';
        } elseif (count($day_offs) !== 2) {
            $error = 'You must select exactly 2 days off per week.';
        } else {
            // Check if email already exists (excluding current trainer)
            $check_query = "SELECT id FROM trainers WHERE email = ? AND id != ? AND deleted_at IS NULL";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("si", $email, $trainer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = 'A trainer with this email already exists.';
            } else {
                // Handle photo upload securely
                $photo = $trainer['photo'];
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../../../uploads/trainers/';
                    $uploadHandler = SecureFileUpload::imageUpload($upload_dir, 5);

                    $result = $uploadHandler->uploadFile($_FILES['photo']);

                    if ($result['success']) {
                        // Delete old photo if exists
                        if (!empty($trainer['photo']) && file_exists($upload_dir . $trainer['photo'])) {
                            unlink($upload_dir . $trainer['photo']);
                        }
                        $photo = $result['filename'];
                    } else {
                        $error = $result['message'];
                    }
                }

                if (empty($error)) {
                    // Start transaction to update trainer, day-offs and shifts atomically
                    $conn->begin_transaction();

                    try {
                        // Track changes for activity log
                        $changes = [];
                        if ($trainer['name'] !== $name)
                            $changes[] = "Name: {$trainer['name']} → $name";
                        if ($trainer['email'] !== $email)
                            $changes[] = "Email: {$trainer['email']} → $email";
                        if ($trainer['phone'] !== $phone)
                            $changes[] = "Phone: {$trainer['phone']} → $phone";
                        if ($trainer['specialization'] !== $specialization)
                            $changes[] = "Specialization: {$trainer['specialization']} → $specialization";
                        if ($trainer['status'] !== $status)
                            $changes[] = "Status: {$trainer['status']} → $status";

                        // Check day-off changes
                        $old_day_offs = implode(', ', $current_day_offs);
                        $new_day_offs = implode(', ', $day_offs);
                        if ($old_day_offs !== $new_day_offs) {
                            $changes[] = "Day-offs: $old_day_offs → $new_day_offs";
                        }

                        // Check default shift change
                        if ($current_default_shift !== $default_shift) {
                            $changes[] = "Default shift: {$current_default_shift} → {$default_shift}";
                        }

                        // Update trainer
                        $update_query = "UPDATE trainers SET name = ?, email = ?, phone = ?, specialization = ?, bio = ?, photo = ?,
                                        emergency_contact_name = ?, emergency_contact_phone = ?, status = ? WHERE id = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param(
                            "sssssssssi",
                            $name,
                            $email,
                            $phone,
                            $specialization,
                            $bio,
                            $photo,
                            $emergency_contact_name,
                            $emergency_contact_phone,
                            $status,
                            $trainer_id
                        );

                        if (!$stmt->execute()) {
                            throw new Exception('Failed to update trainer. Please try again.');
                        }

                        // Update day-off schedule: ensure all days exist then mark selected as day-offs
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

                        foreach ($days as $day) {
                            $upsert_day = "INSERT INTO trainer_day_offs (trainer_id, day_of_week, is_day_off)
                                          VALUES (?, ?, FALSE)
                                          ON DUPLICATE KEY UPDATE is_day_off = FALSE";
                            $stmt = $conn->prepare($upsert_day);
                            $stmt->bind_param("is", $trainer_id, $day);
                            $stmt->execute();
                        }

                        // Then mark selected days as day-offs
                        if (!empty($day_offs)) {
                            foreach ($day_offs as $day_off) {
                                $update_day = "UPDATE trainer_day_offs SET is_day_off = TRUE
                                              WHERE trainer_id = ? AND day_of_week = ?";
                                $stmt = $conn->prepare($update_day);
                                $stmt->bind_param("is", $trainer_id, $day_off);
                                $stmt->execute();
                            }
                        }

                        // Rebuild trainer_shifts for this trainer according to new day-offs and default_shift.
                        // For simplicity and correctness, delete existing rows then insert 7 rows (one per day).
                        $del_query = "DELETE FROM trainer_shifts WHERE trainer_id = ?";
                        $del_stmt = $conn->prepare($del_query);
                        $del_stmt->bind_param("i", $trainer_id);
                        $del_stmt->execute();

                        // Standard shift times and breaks:
                        // morning => 07:00:00 - 15:00:00, break 12:00:00 - 13:00:00
                        // afternoon => 11:00:00 - 19:00:00, break 15:00:00 - 16:00:00
                        // night => 14:00:00 - 22:00:00, break 18:00:00 - 19:00:00
                        foreach ($days as $day) {
                            // If it's a day-off, set shift to 'none'
                            if (in_array($day, $day_offs, true)) {
                                $is_active = 0;
                                $shift_query = "INSERT INTO trainer_shifts (trainer_id, day_of_week, shift_type, custom_start_time, custom_end_time, break_start_time, break_end_time, is_active)
                                                VALUES (?, ?, 'none', NULL, NULL, NULL, NULL, ?)";
                                $shift_stmt = $conn->prepare($shift_query);
                                $shift_stmt->bind_param("isi", $trainer_id, $day, $is_active);
                                $shift_stmt->execute();
                                continue;
                            }

                            // Otherwise apply default_shift
                            if ($default_shift === 'morning') {
                                $start_time = '07:00:00';
                                $end_time = '15:00:00';
                                $break_start = '12:00:00';
                                $break_end = '13:00:00';
                                $is_active = 1;
                                $shift_query = "INSERT INTO trainer_shifts (trainer_id, day_of_week, shift_type, custom_start_time, custom_end_time, break_start_time, break_end_time, is_active)
                                                VALUES (?, ?, 'morning', ?, ?, ?, ?, ?)";
                                $shift_stmt = $conn->prepare($shift_query);
                                $shift_stmt->bind_param("isssssi", $trainer_id, $day, $start_time, $end_time, $break_start, $break_end, $is_active);
                                $shift_stmt->execute();
                            } elseif ($default_shift === 'afternoon') {
                                $start_time = '11:00:00';
                                $end_time = '19:00:00';
                                $break_start = '15:00:00';
                                $break_end = '16:00:00';
                                $is_active = 1;
                                $shift_query = "INSERT INTO trainer_shifts (trainer_id, day_of_week, shift_type, custom_start_time, custom_end_time, break_start_time, break_end_time, is_active)
                                                VALUES (?, ?, 'afternoon', ?, ?, ?, ?, ?)";
                                $shift_stmt = $conn->prepare($shift_query);
                                $shift_stmt->bind_param("isssssi", $trainer_id, $day, $start_time, $end_time, $break_start, $break_end, $is_active);
                                $shift_stmt->execute();
                            } elseif ($default_shift === 'night') {
                                $start_time = '14:00:00';
                                $end_time = '22:00:00';
                                $break_start = '18:00:00';
                                $break_end = '19:00:00';
                                $is_active = 1;
                                $shift_query = "INSERT INTO trainer_shifts (trainer_id, day_of_week, shift_type, custom_start_time, custom_end_time, break_start_time, break_end_time, is_active)
                                                VALUES (?, ?, 'night', ?, ?, ?, ?, ?)";
                                $shift_stmt = $conn->prepare($shift_query);
                                $shift_stmt->bind_param("isssssi", $trainer_id, $day, $start_time, $end_time, $break_start, $break_end, $is_active);
                                $shift_stmt->execute();
                            } else {
                                // default_shift === 'none' should rarely be used for non-day-off days, but handle it
                                $is_active = 0;
                                $shift_query = "INSERT INTO trainer_shifts (trainer_id, day_of_week, shift_type, custom_start_time, custom_end_time, break_start_time, break_end_time, is_active)
                                                VALUES (?, ?, 'none', NULL, NULL, NULL, NULL, ?)";
                                $shift_stmt = $conn->prepare($shift_query);
                                $shift_stmt->bind_param("isi", $trainer_id, $day, $is_active);
                                $shift_stmt->execute();
                            }
                        }

                        // Log activity if there are changes
                        if (!empty($changes)) {
                            $log_query = "INSERT INTO trainer_activity_log (trainer_id, admin_id, action, details) VALUES (?, ?, 'Edited', ?)";
                            $details = "Updated: " . implode(", ", $changes);
                            $stmt = $conn->prepare($log_query);
                            $stmt->bind_param("iss", $trainer_id, $admin_id, $details);
                            $stmt->execute();

                            // Log to main activity log
                            ActivityLogger::log('trainer_updated', $name, $trainer_id, "Trainer '$name' (#$trainer_id) updated. Changes: " . implode(", ", $changes));
                        }

                        $conn->commit();

                        header('Location: trainers.php?success=updated');
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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trainer - Admin Panel</title>
    <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/trainer-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .shift-select { width: 220px; }
        .shift-info { font-size: 0.95rem; color: #666; margin-left: 8px; }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>Edit Trainer</h1>
                <p class="subtitle">Update trainer information</p>
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
                <?= CSRFProtection::getTokenField(); ?>
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required
                                value="<?= htmlspecialchars($trainer['name']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required
                                value="<?= htmlspecialchars($trainer['email']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" placeholder="+63-917-XXX-XXXX" required
                                value="<?= htmlspecialchars($trainer['phone']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="specialization">Specialization <span class="required">*</span></label>
                            <select id="specialization" name="specialization" required>
                                <option value="">Select Specialization</option>
                                <option value="Gym" <?= $trainer['specialization'] === 'Gym' ? 'selected' : '' ?>>Gym
                                </option>
                                <option value="MMA" <?= $trainer['specialization'] === 'MMA' ? 'selected' : '' ?>>MMA
                                </option>
                                <option value="Boxing" <?= $trainer['specialization'] === 'Boxing' ? 'selected' : '' ?>>
                                    Boxing</option>
                                <option value="Muay Thai" <?= $trainer['specialization'] === 'Muay Thai' ? 'selected' : '' ?>>Muay Thai</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="4"
                            placeholder="Brief description of the trainer's experience and expertise..."><?= htmlspecialchars($trainer['bio']) ?></textarea>
                        <span class="form-hint">Brief background about the trainer</span>
                    </div>

                    <div class="form-group">
                        <label for="photo">Profile Photo</label>
                        <div class="photo-preview">
                            <?php
                            $currentPhoto = !empty($trainer['photo']) && file_exists('../../../uploads/trainers/' . $trainer['photo'])
                                ? '../../../uploads/trainers/' . htmlspecialchars($trainer['photo'])
                                : '../../../images/account-icon.svg';
                            $isDefaultIcon = $currentPhoto === '../../../images/account-icon.svg';
                            ?>
                            <img src="<?= $currentPhoto ?>"
                                alt="<?= $isDefaultIcon ? 'Default Profile Icon' : 'Current Photo' ?>"
                                class="<?= $isDefaultIcon ? 'default-icon' : '' ?>"
                                id="photoPreview">
                            <div class="photo-preview-info">
                                <h4 id="photoPreviewTitle"><?= $isDefaultIcon ? 'No Photo Uploaded' : 'Current Photo' ?></h4>
                                <p id="photoPreviewText"><?= $isDefaultIcon ? 'Upload an image to set profile photo' : 'Upload a new image to replace' ?></p>
                            </div>
                        </div>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg" onchange="previewPhoto(this)">
                        <span class="form-hint">Accepted formats: JPG, JPEG, PNG</span>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Emergency Contact</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                                value="<?= htmlspecialchars($trainer['emergency_contact_name']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Phone</label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                                placeholder="+63-917-XXX-XXXX"
                                value="<?= htmlspecialchars($trainer['emergency_contact_phone']) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Status</h3>

                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required>
                            <option value="Active" <?= $trainer['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $trainer['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive
                            </option>
                            <option value="On Leave" <?= $trainer['status'] === 'On Leave' ? 'selected' : '' ?>>On Leave
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Weekly Schedule <span class="required">*</span></h3>
                    <p class="section-description">Select exactly 2 days off per week for this trainer</p>

                    <div class="days-grid">
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day):
                            $is_checked = in_array($day, $current_day_offs);
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

                <div class="form-section">
                    <h3 class="section-title">Fixed Shift</h3>
                    <p class="section-description">Choose a single fixed shift that applies to all non-day-off days for this trainer.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="default_shift">Default Shift</label>
                            <?php
                                // If form was submitted and errored, prefer posted value; otherwise show current default
                                $posted_default = isset($_POST['default_shift']) ? $_POST['default_shift'] : $current_default_shift;
                                $posted_default = in_array($posted_default, ['morning','afternoon','night','none'], true) ? $posted_default : 'morning';
                            ?>
                            <select id="default_shift" name="default_shift" class="shift-select">
                                <option value="none" <?= $posted_default === 'none' ? 'selected' : '' ?>>None</option>
                                <option value="morning" <?= $posted_default === 'morning' ? 'selected' : '' ?>>Morning (07:00 - 15:00)</option>
                                <option value="afternoon" <?= $posted_default === 'afternoon' ? 'selected' : '' ?>>Afternoon (11:00 - 19:00)</option>
                                <option value="night" <?= $posted_default === 'night' ? 'selected' : '' ?>>Night (14:00 - 22:00)</option>
                            </select>
                            <span class="shift-info" id="defaultShiftInfo">
                                <?php
                                    $map = [
                                        'morning' => '07:00 - 15:00 (Break: 12:00 - 13:00)',
                                        'afternoon' => '11:00 - 19:00 (Break: 15:00 - 16:00)',
                                        'night' => '14:00 - 22:00 (Break: 18:00 - 19:00)',
                                        'none' => '—'
                                    ];
                                    echo isset($map[$posted_default]) ? $map[$posted_default] : '—';
                                ?>
                            </span>
                            <?php if ($current_break_start && $current_break_end): ?>
                                <div class="form-hint">Current stored break: <?= htmlspecialchars($current_break_start) ?> - <?= htmlspecialchars($current_break_end) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="trainers.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
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
        });

        // Photo preview function
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            const title = document.getElementById('photoPreviewTitle');
            const text = document.getElementById('photoPreviewText');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('default-icon');
                    title.textContent = 'New Photo Selected';
                    text.textContent = input.files[0].name;
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

        // Update default shift info text when selection changes
        document.addEventListener('DOMContentLoaded', function() {
            const mapping = {
                'morning': '07:00 - 15:00 (Break: 12:00 - 13:00)',
                'afternoon': '11:00 - 19:00 (Break: 15:00 - 16:00)',
                'night': '14:00 - 22:00 (Break: 18:00 - 19:00)',
                'none': '—'
            };
            const select = document.getElementById('default_shift');
            const info = document.getElementById('defaultShiftInfo');
            if (select && info) {
                select.addEventListener('change', function() {
                    info.textContent = mapping[this.value] || '—';
                });
            }
        });
    </script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
</body>

</html>