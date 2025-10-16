<?php
// ===========================================
// admin.php — Main Admin Dashboard
// ===========================================

include_once('../../../includes/init.php');


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
  if ($result) $pendingSubs = $result->fetch_assoc()['total'];
} elseif ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
  // Inspect which status-like columns exist
  $has_request_status = ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'request_status'")->num_rows > 0);
  $has_membership_status = ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'membership_status'")->num_rows > 0);
  $has_status = ($conn->query("SHOW COLUMNS FROM user_memberships LIKE 'status'")->num_rows > 0);

  if ($has_request_status) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE request_status = 'pending'");
    if ($result) $pendingSubs = $result->fetch_assoc()['total'];
  } elseif ($has_status) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE status IN ('Pending','pending')");
    if ($result) $pendingSubs = $result->fetch_assoc()['total'];
  } elseif ($has_membership_status) {
    // Approximate pending requests: submitted but not approved/activated
    $result = $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE membership_status IS NULL AND date_submitted IS NOT NULL AND date_approved IS NULL");
    if ($result) $pendingSubs = $result->fetch_assoc()['total'];
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
  <link rel="stylesheet" href="css/admin.css">
</head>

<body>

  <?php include_once('admin_sidebar.php'); ?>
  <?php include_once('admin_header.php'); ?>

  <main class="admin-main">
    <header>
      <h1>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?> 👋</h1>
      <p>Here’s an overview of your gym’s activity.</p>
    </header>

    <!-- Dashboard Cards -->
    <section class="cards">
      <div class="card">
        <h2><?= $totalMembers ?></h2>
        <p>Total Members</p>
      </div>
      <div class="card">
        <h2><?= $totalTrainers ?></h2>
        <p>Active Trainers</p>
      </div>
      <div class="card">
        <h2><?= $pendingSubs ?></h2>
        <p>Pending Subscriptions</p>
      </div>
      <div class="card">
        <h2><?= $pendingRes ?></h2>
        <p>Pending Reservations</p>
      </div>
    </section>

    <!-- Optional Section: Recent Logs -->
    <section class="logs">
      <h2>Recent Activity</h2>
      <table border="1" cellpadding="6">
        <thead>
          <tr>
            <th>Admin</th>
            <th>Action</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php
            // Only query logs if the table exists (prevent fatal errors during schema migration)
            if ($conn->query("SHOW TABLES LIKE 'logs'")->num_rows) {
              $logs = $conn->query("
                SELECT l.action, l.timestamp, u.username
                FROM logs l
                LEFT JOIN users u ON l.admin_id = u.id
                ORDER BY l.timestamp DESC LIMIT 5
              ");
            } else {
              $logs = false;
            }

          if ($logs && $logs->num_rows > 0):
            while ($row = $logs->fetch_assoc()):
              ?>
              <tr>
                <td><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($row['action']) ?></td>
                <td><?= htmlspecialchars($row['timestamp']) ?></td>
              </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="3" align="center">No recent activity.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <?php include_once('admin_footer.php'); ?>
</body>

</html>
