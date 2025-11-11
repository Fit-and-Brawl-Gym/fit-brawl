<?php
// ===========================================
// admin.php — Main Admin Dashboard
// ===========================================

include_once('../../../includes/init.php');
require_once('../../../includes/config.php');
require_once('../../../includes/activity_logger.php');

// Initialize activity logger
ActivityLogger::init($conn);

// Optional: Check admin privileges
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch dashboard stats
$totalMembers = $totalTrainers = $pendingSubs = $pendingRes = 0;

// Total Members
$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'member'");
if ($result)
  $totalMembers = $result->fetch_assoc()['total'];

// Total Trainers
$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'trainer'");
if ($result)
  $totalTrainers = $result->fetch_assoc()['total'];

// Pending Subscriptions: prefer `subscriptions` table if present, otherwise safely use `user_memberships`
$pendingSubs = 0;
if ($conn->query("SHOW TABLES LIKE 'subscriptions'")->num_rows) {
  $result = $conn->query("SELECT COUNT(*) AS total FROM subscriptions WHERE status = 'Pending'");
  if ($result)
    $pendingSubs = $result->fetch_assoc()['total'];
} elseif ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
  // Inspect which status-like columns exist
  $has_request_status = ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'request_status'")->num_rows > 0);
  $has_membership_status = ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'membership_status'")->num_rows > 0);
  $has_status = ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'status'")->num_rows > 0);

  if ($has_request_status) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE request_status = 'pending'");
    if ($result)
      $pendingSubs = $result->fetch_assoc()['total'];
  } elseif ($has_status) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE status IN ('Pending','pending')");
    if ($result)
      $pendingSubs = $result->fetch_assoc()['total'];
  } elseif ($has_membership_status) {
    // Approximate pending requests: submitted but not approved/activated
    $result = $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE membership_status IS NULL AND date_submitted IS NOT NULL AND date_approved IS NULL");
    if ($result)
      $pendingSubs = $result->fetch_assoc()['total'];
  }
}

// Pending Reservations (optional if you have table)
if ($conn->query("SHOW TABLES LIKE 'reservations'")->num_rows) {
  $result = $conn->query("SELECT COUNT(*) AS total FROM reservations WHERE status = 'Pending'");
  if ($result)
    $pendingRes = $result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | Fit & Brawl Gym</title>
  <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
  <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
  <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

  <?php include_once('admin_sidebar.php'); ?>

  <main class="admin-main">
    <header>
      <h1>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h1>
      <p>Here’s an overview of your gym’s activity.</p>
    </header>

    <!-- Dashboard Cards -->
    <section class="cards">
      <div class="card">
        <div class="card-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <h2><?= $totalMembers ?></h2>
        <p>Total Members</p>
      </div>
      <div class="card">
        <div class="card-icon">
          <i class="fa-solid fa-dumbbell"></i>
        </div>
        <h2><?= $totalTrainers ?></h2>
        <p>Active Trainers</p>
      </div>
      <div class="card <?= $pendingSubs > 0 ? 'has-pending' : '' ?>">
        <div class="card-icon">
          <i class="fa-solid fa-clock"></i>
        </div>
        <h2><?= $pendingSubs ?></h2>
        <p>Pending Subscriptions</p>
        <?php if ($pendingSubs > 0): ?>
          <a href="subscriptions.php" class="card-action">Review Now →</a>
        <?php endif; ?>
      </div>
      <div class="card <?= $pendingRes > 0 ? 'has-pending' : '' ?>">
        <div class="card-icon">
          <i class="fa-solid fa-calendar-check"></i>
        </div>
        <h2><?= $pendingRes ?></h2>
        <p>Pending Reservations</p>
        <?php if ($pendingRes > 0): ?>
          <a href="reservations.php" class="card-action">Review Now →</a>
        <?php endif; ?>
      </div>
      <?php
      // Get unread contact count
      $unreadContacts = 0;
      $unread_query = $conn->query("SELECT COUNT(*) as count FROM contact WHERE status = 'unread' AND (archived = 0 OR archived IS NULL) AND deleted_at IS NULL");
      if ($unread_query && $unread_row = $unread_query->fetch_assoc()) {
        $unreadContacts = $unread_row['count'];
      }
      ?>
      <div class="card card-contacts <?= $unreadContacts > 0 ? 'has-unread' : '' ?>">
        <div class="card-icon">
          <i class="fa-solid fa-envelope"></i>
        </div>
        <h2><?= $unreadContacts ?></h2>
        <p>Unread Messages</p>
        <?php if ($unreadContacts > 0): ?>
          <a href="contacts.php" class="card-action">View Messages →</a>
        <?php endif; ?>
      </div>
    </section>

    <!-- Recent Activity Logs -->
    <section class="logs">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2>Recent Activity</h2>
        <a href="activity-log.php" class="btn-primary"
          style="padding: 8px 16px; text-decoration: none; font-size: 14px;">
          <i class="fa-solid fa-eye"></i> View All
        </a>
      </div>
      <table>
        <thead>
          <tr>
            <th width="40"></th>
            <th>Admin</th>
            <th>Action</th>
            <th>Details</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $activities = ActivityLogger::getActivities(10);

          if (!empty($activities)):
            foreach ($activities as $activity):
              $iconData = ActivityLogger::getActivityIcon($activity['action_type']);
              $timeAgo = timeAgo($activity['timestamp']);
              ?>
              <tr>
                <td>
                  <i class="fa-solid <?= $iconData['icon'] ?>"
                    style="color: <?= $iconData['color'] ?>; font-size: 18px;"></i>
                </td>
                <td><strong><?= htmlspecialchars($activity['admin_name']) ?></strong></td>
                <td><?= ucwords(str_replace('_', ' ', $activity['action_type'])) ?></td>
                <td><?= htmlspecialchars($activity['details']) ?></td>
                <td style="color: #999; font-size: 13px;"><?= $timeAgo ?></td>
              </tr>
              <?php
            endforeach;
          else:
            ?>
            <tr>
              <td colspan="5" style="text-align: center; color: #999; padding: 40px;">
                No recent activity
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <?php
  // Helper function for time ago
  function timeAgo($timestamp)
  {
    if (!$timestamp)
      return 'N/A';

    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;

    // Handle future dates
    if ($diff < 0) {
      return date('M d, Y g:i A', $time);
    }

    // Less than 1 minute
    if ($diff < 60) {
      return 'Just now';
    }

    // Less than 1 hour
    if ($diff < 3600) {
      $mins = floor($diff / 60);
      return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    }

    // Less than 24 hours
    if ($diff < 86400) {
      $hours = floor($diff / 3600);
      return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }

    // Less than 7 days
    if ($diff < 604800) {
      $days = floor($diff / 86400);
      return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }

    // Less than 30 days
    if ($diff < 2592000) {
      $weeks = floor($diff / 604800);
      return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }

    // Older than 30 days - show full date
    return date('M d, Y g:i A', $time);
  }
  ?>

  <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
</body>

</html>
