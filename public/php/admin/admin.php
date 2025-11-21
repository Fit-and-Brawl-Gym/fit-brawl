<?php
// ===========================================
// admin.php — Main Admin Dashboard
// ===========================================

include_once('../../../includes/init.php');
require_once('../../../includes/config.php');
require_once('../../../includes/csp_nonce.php');
require_once('../../../includes/activity_logger.php');

// Generate CSP nonces for this request
CSPNonce::generate();

// Initialize activity logger
ActivityLogger::init($conn);

// Optional: Check admin privileges
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch dashboard stats
$activeSubscribers = $totalTrainers = $pendingSubs = $pendingRes = 0;

// Active Subscribers (approved memberships that are paid and not expired)
// Matches the logic from active_memberships_api.php
$result = $conn->query("
  SELECT COUNT(DISTINCT um.user_id) AS total
  FROM user_memberships um
  WHERE um.request_status = 'approved'
  AND um.end_date >= CURDATE()
  AND (
    um.payment_method = 'online'
    OR (um.payment_method = 'cash' AND um.cash_payment_status = 'paid')
  )
");
if ($result)
  $activeSubscribers = $result->fetch_assoc()['total'];

// Total Trainers (from trainers table, excluding deleted ones - matches trainers.php)
$result = $conn->query("SELECT COUNT(*) AS total FROM trainers WHERE deleted_at IS NULL");
if ($result)
  $totalTrainers = $result->fetch_assoc()['total'];

// Pending Subscriptions - memberships awaiting admin approval
// This matches the subscriptions.php page logic for pending requests
$result = $conn->query("
  SELECT COUNT(*) AS total
  FROM user_memberships
  WHERE request_status = 'pending'
");
if ($result)
  $pendingSubs = $result->fetch_assoc()['total'];

// Upcoming Scheduled Sessions - count confirmed bookings (matches reservations.php)
$result = $conn->query("
  SELECT COUNT(*) AS total
  FROM user_reservations
  WHERE booking_status = 'confirmed'
");
if ($result)
  $pendingRes = $result->fetch_assoc()['total'];

// Get unread contact count
$unreadContacts = 0;
$unread_query = $conn->query("SELECT COUNT(*) as count FROM contact WHERE status = 'unread' AND (archived = 0 OR archived IS NULL) AND deleted_at IS NULL");
if ($unread_query && $unread_row = $unread_query->fetch_assoc()) {
  $unreadContacts = $unread_row['count'];
}

// Get total registered members
$totalMembers = 0;
$members_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'member'");
if ($members_query && $members_row = $members_query->fetch_assoc()) {
  $totalMembers = $members_row['count'];
}

// Get total equipment count
$totalEquipment = 0;
$equipment_query = $conn->query("SELECT COUNT(*) as count FROM equipment");
if ($equipment_query && $equipment_row = $equipment_query->fetch_assoc()) {
  $totalEquipment = $equipment_row['count'];
}

// Get equipment in maintenance
$maintenanceEquipment = 0;
$maintenance_query = $conn->query("SELECT COUNT(*) as count FROM equipment WHERE status = 'Maintenance'");
if ($maintenance_query && $maintenance_row = $maintenance_query->fetch_assoc()) {
  $maintenanceEquipment = $maintenance_row['count'];
}

// Get total products count
$totalProducts = 0;
$products_query = $conn->query("SELECT COUNT(*) as count FROM products");
if ($products_query && $products_row = $products_query->fetch_assoc()) {
  $totalProducts = $products_row['count'];
}

// Get out of stock products count (stock = 0)
$outOfStockProducts = 0;
$out_stock_products_query = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock = 0");
if ($out_stock_products_query && $out_stock_products_row = $out_stock_products_query->fetch_assoc()) {
  $outOfStockProducts = $out_stock_products_row['count'];
}

// Get low stock products count (stock > 0 AND stock <= 10)
$lowStockProducts = 0;
$low_stock_products_query = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock > 0 AND stock <= 10");
if ($low_stock_products_query && $low_stock_products_row = $low_stock_products_query->fetch_assoc()) {
  $lowStockProducts = $low_stock_products_row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    <!-- Dashboard Stats -->
    <section class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-info">
          <h3><?= $activeSubscribers ?></h3>
          <p>Active Subscribers</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <i class="fa-solid fa-dumbbell"></i>
        </div>
        <div class="stat-info">
          <h3><?= $totalTrainers ?></h3>
          <p>Active Trainers</p>
        </div>
      </div>
      <div class="stat-card <?= $pendingSubs > 0 ? 'has-alert' : '' ?>">
        <div class="stat-icon orange">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-info">
          <h3><?= $pendingSubs ?></h3>
          <p>Pending Subscriptions</p>
        </div>
        <?php if ($pendingSubs > 0): ?>
          <a href="subscriptions.php" class="stat-action">
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div class="stat-info">
          <h3><?= $pendingRes ?></h3>
          <p>Scheduled Sessions</p>
        </div>
        <?php if ($pendingRes > 0): ?>
          <a href="reservations.php" class="stat-action">
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <div class="stat-card <?= $unreadContacts > 0 ? 'has-alert' : '' ?>">
        <div class="stat-icon red">
          <i class="fa-solid fa-envelope"></i>
        </div>
        <div class="stat-info">
          <h3><?= $unreadContacts ?></h3>
          <p>Unread Messages</p>
        </div>
        <?php if ($unreadContacts > 0): ?>
          <a href="contacts.php" class="stat-action">
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <i class="fa-solid fa-user-group"></i>
        </div>
        <div class="stat-info">
          <h3><?= $totalMembers ?></h3>
          <p>Total Members</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <i class="fa-solid fa-dumbbell"></i>
        </div>
        <div class="stat-info">
          <h3><?= $totalEquipment ?></h3>
          <p>Total Equipment</p>
        </div>
      </div>
      <div class="stat-card <?= $maintenanceEquipment > 0 ? 'has-alert' : '' ?>">
        <div class="stat-icon orange">
          <i class="fa-solid fa-wrench"></i>
        </div>
        <div class="stat-info">
          <h3><?= $maintenanceEquipment ?></h3>
          <p>Under Maintenance</p>
        </div>
        <?php if ($maintenanceEquipment > 0): ?>
          <a href="equipment.php" class="stat-action">
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <i class="fa-solid fa-store"></i>
        </div>
        <div class="stat-info">
          <h3><?= $totalProducts ?></h3>
          <p>Total Products</p>
        </div>
      </div>
      <div class="stat-card <?= $outOfStockProducts > 0 ? 'has-alert' : '' ?>">
        <div class="stat-icon red">
          <i class="fa-solid fa-box-open"></i>
        </div>
        <div class="stat-info">
          <h3><?= $outOfStockProducts ?></h3>
          <p>Out of Stock</p>
        </div>
        <?php if ($outOfStockProducts > 0): ?>
          <a href="products.php" class="stat-action">
            <i class="fa-solid fa-arrow-right"></i>
          </a>
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

    // Convert UTC timestamp to Philippine Time by adding 8 hours
    $time = strtotime($timestamp) + (8 * 3600);
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
