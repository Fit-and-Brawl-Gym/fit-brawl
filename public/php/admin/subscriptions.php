<?php
session_start();

// Require admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: ../login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin | Manage Subscriptions</title>
  <link rel="icon" type="image/png" href="../../../images/favicon-admin.png">
  <link rel="stylesheet" href="css/admin.css">
  <link rel="stylesheet" href="css/subscriptions.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">
    <header>
      <h1>Manage Subscriptions</h1>
      <p>Approve or reject membership payments.</p>
    </header>

    <!-- Processing Subscriptions -->
    <section class="subscriptions-section">
      <h2>Processing Subscriptions</h2>
      <p class="muted">Pending payments that need admin action.</p>
      <div class="controls">
        <button onclick="location.reload()">Refresh</button>
      </div>
      <div class="table-wrap">
        <table class="subs-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Member</th>
              <th>Plan</th>
              <th>QR Proof</th>
              <th>Date Submitted</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="processingTableBody">
            <!-- Data loaded via JavaScript -->
            <tr>
              <td colspan="6" style="text-align:center; color:#999;">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Rejected Submissions -->
    <section class="subscriptions-section">
      <h2>Rejected Submissions</h2>
      <p class="muted">Subscriptions that were rejected by admin.</p>
      <div class="table-wrap">
        <table class="subs-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Member</th>
              <th>Plan</th>
              <th>Reason</th>
              <th>Date Submitted</th>
            </tr>
          </thead>
          <tbody id="rejectedTableBody">
            <!-- Data loaded via JavaScript -->
            <tr>
              <td colspan="5" style="text-align:center; color:#999;">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <!-- Confirmation Modal -->
  <div id="confirmModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
      <h3 id="confirmTitle">Confirm Action</h3>
      <p id="confirmMessage">Are you sure?</p>
      <div id="rejectReasonContainer" style="display:none; margin-top:12px;">
        <label style="display:block; font-weight:600; margin-bottom:6px;">Reason for Rejection:</label>
        <textarea id="rejectReason" rows="3" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;"
          placeholder="Enter rejection reason..."></textarea>
      </div>
      <div style="display:flex; gap:10px; margin-top:16px; justify-content:flex-end;">
        <button id="confirmCancel" onclick="closeModal()" class="btn-secondary">Cancel</button>
        <button id="confirmOk" onclick="executeAction()" class="btn-primary">Confirm</button>
      </div>
    </div>
  </div>

  <script src="js/sidebar.js"></script>
  <script src="js/subscriptions.js"></script>
</body>

</html>
