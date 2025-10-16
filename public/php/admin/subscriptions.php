<?php
session_start();

// Require admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin | Manage Subscriptions</title>
  <link rel="stylesheet" href="css/admin.css">
</head>

<body>

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">
    <header>
      <h1>Manage Subscriptions</h1>
      <p>Approve or reject membership payments</p>
    </header>

    <!-- Filter Buttons -->
    <div class="filters">
      <button onclick="loadSubscriptions('Pending')">Pending</button>
      <button onclick="loadSubscriptions('Approved')">Approved</button>
      <button onclick="loadSubscriptions('Rejected')">Rejected</button>
      <button onclick="loadSubscriptions()">All</button>
    </div>

    <!-- Table -->
    <table id="subsTable" border="1" cellpadding="8">
      <thead>
        <tr>
          <th>ID</th>
          <th>Member</th>
          <th>Plan</th>
          <th>Amount</th>
          <th>Status</th>
          <th>QR Proof</th>
          <th>Date Submitted</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="8" align="center">Loading...</td>
        </tr>
      </tbody>
    </table>
  </main>

  <script src="js/subscriptions.js"></script>
</body>

</html>