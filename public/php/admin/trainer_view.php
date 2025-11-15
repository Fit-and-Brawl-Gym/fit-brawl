<?php
require_once '../../../includes/init.php';

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get trainer ID
$trainer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$trainer_id) {
    header('Location: trainers.php');
    exit;
}

// Fetch trainer details with stats
$query = "SELECT t.*,
          (SELECT COUNT(DISTINCT ur.user_id)
           FROM user_reservations ur
           WHERE ur.trainer_id = t.id
           AND ur.booking_status = 'confirmed'
           AND ur.booking_date = CURDATE()) as clients_today,
          (SELECT COUNT(*)
           FROM user_reservations ur
           WHERE ur.trainer_id = t.id
           AND ur.booking_status = 'confirmed'
           AND ur.booking_date > CURDATE()) as upcoming_bookings,
          (SELECT COUNT(*)
           FROM user_reservations ur
           WHERE ur.trainer_id = t.id
           AND ur.booking_status = 'completed') as total_sessions
          FROM trainers t
          WHERE t.id = ? AND t.deleted_at IS NULL";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();
$trainer = $result->fetch_assoc();

if (!$trainer) {
    header('Location: trainers.php?error=notfound');
    exit;
}

// Fetch upcoming sessions
$sessions_query = "SELECT ur.*, u.username, u.email
                   FROM user_reservations ur
                   JOIN users u ON u.id = ur.user_id
                   WHERE ur.trainer_id = ?
                   AND ur.booking_status = 'confirmed'
                   AND ur.booking_date >= CURDATE()
                   ORDER BY ur.booking_date ASC, ur.session_time ASC
                   LIMIT 10";

$stmt = $conn->prepare($sessions_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$sessions_result = $stmt->get_result();

// Fetch activity log
$log_query = "SELECT tal.*, u.username as admin_username
              FROM trainer_activity_log tal
              LEFT JOIN users u ON u.id = tal.admin_id
              WHERE tal.trainer_id = ?
              ORDER BY tal.timestamp DESC
              LIMIT 20";
$stmt = $conn->prepare($log_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$log_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Trainer - Admin Panel</title>
    <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/trainer-view.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>Trainer Details</h1>
                <p class="subtitle">View trainer information and activity</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="trainer_edit.php?id=<?= $trainer_id ?>" class="btn-primary">
                    <i class="fas fa-edit"></i> Edit Trainer
                </a>
                <a href="trainers.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="trainer-view-container">
            <!-- Left Column: Profile -->
            <div class="trainer-profile-section">
                <div class="profile-card">
                    <div class="profile-header">
                        <?php
                        $trainerPhoto = !empty($trainer['photo']) && file_exists('../../../uploads/trainers/' . $trainer['photo'])
                            ? '../../../uploads/trainers/' . htmlspecialchars($trainer['photo'])
                            : '../../../images/account-icon.svg';
                        ?>
                        <img src="<?= $trainerPhoto ?>"
                            alt="<?= htmlspecialchars($trainer['name']) ?>" class="profile-avatar">
                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $trainer['status'])) ?>">
                            <?= htmlspecialchars($trainer['status']) ?>
                        </span>
                    </div>

                    <h2 class="profile-name"><?= htmlspecialchars($trainer['name']) ?></h2>
                    <p class="profile-specialization">
                        <span
                            class="specialization-badge <?= strtolower(str_replace(' ', '-', $trainer['specialization'])) ?>">
                            <?= htmlspecialchars($trainer['specialization']) ?> Specialist
                        </span>
                    </p>

                    <div class="profile-info-grid">
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <label>Email</label>
                                <span><?= htmlspecialchars($trainer['email']) ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <label>Phone</label>
                                <span><?= htmlspecialchars($trainer['phone']) ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user-shield"></i>
                            <div>
                                <label>Emergency Contact</label>
                                <span><?= !empty($trainer['emergency_contact_name']) ? htmlspecialchars($trainer['emergency_contact_name']) : '-' ?></span>
                                <?php if (!empty($trainer['emergency_contact_phone'])): ?>
                                    <small><?= htmlspecialchars($trainer['emergency_contact_phone']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <label>Joined</label>
                                <span><?= date('M d, Y', strtotime($trainer['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($trainer['bio'])): ?>
                        <div class="profile-bio">
                            <h4>About</h4>
                            <p><?= nl2br(htmlspecialchars($trainer['bio'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card-small">
                        <i class="fas fa-users"></i>
                        <div>
                            <h3><?= $trainer['clients_today'] ?></h3>
                            <p>Clients Today</p>
                        </div>
                    </div>
                    <div class="stat-card-small">
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <h3><?= $trainer['upcoming_bookings'] ?></h3>
                            <p>Upcoming</p>
                        </div>
                    </div>
                    <div class="stat-card-small">
                        <i class="fas fa-dumbbell"></i>
                        <div>
                            <h3><?= $trainer['total_sessions'] ?></h3>
                            <p>Total Sessions</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sessions & Activity -->
            <div class="trainer-activity-section">
                <!-- Upcoming Sessions -->
                <div class="section-card">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-week"></i>
                        Upcoming Sessions
                    </h3>
                    <div class="sessions-list">
                        <?php if ($sessions_result->num_rows > 0): ?>
                            <?php while ($session = $sessions_result->fetch_assoc()): ?>
                                <div class="session-item">
                                    <div class="session-date">
                                        <div class="date-badge">
                                            <span class="day"><?= date('d', strtotime($session['date'])) ?></span>
                                            <span class="month"><?= date('M', strtotime($session['date'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="session-details">
                                        <h4><?= htmlspecialchars($session['class_type']) ?></h4>
                                        <p class="session-time">
                                            <i class="fas fa-clock"></i>
                                            <?= date('g:i A', strtotime($session['start_time'])) ?> -
                                            <?= date('g:i A', strtotime($session['end_time'])) ?>
                                        </p>
                                        <p class="session-user">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($session['username']) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="empty-state">No upcoming sessions</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="section-card">
                    <h3 class="section-title">
                        <i class="fas fa-history"></i>
                        Activity Log
                    </h3>
                    <div class="activity-timeline">
                        <?php if ($log_result->num_rows > 0): ?>
                            <?php while ($log = $log_result->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <strong><?= htmlspecialchars($log['action']) ?></strong>
                                            <span
                                                class="timeline-time"><?= date('M d, Y g:i A', strtotime($log['timestamp'])) ?></span>
                                        </div>
                                        <?php if (!empty($log['details'])): ?>
                                            <p class="timeline-details"><?= htmlspecialchars($log['details']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($log['admin_username'])): ?>
                                            <p class="timeline-admin">by <?= htmlspecialchars($log['admin_username']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="empty-state">No activity recorded</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
</body>

</html>
