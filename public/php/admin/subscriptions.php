<?php
require_once '../../../includes/init.php';

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin | Manage Subscriptions</title>
  <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
  <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
  <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/subscriptions.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">
    <header>
      <h1>Manage Subscriptions</h1>
      <p>Approve or reject membership payments.</p>
    </header>

    <!-- Cash Payments Section -->
    <section class="subscriptions-section cash-section">
      <h2>Cash Payments (Pay at Gym)</h2>
      <p class="muted">Members who selected cash payment option and need to pay at the gym.</p>
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
              <th>Date Submitted</th>
              <th>Payment Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="cashTableBody">
            <!-- Data loaded via JavaScript -->
            <tr>
              <td colspan="6" style="text-align:center; color:#999;">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Processing Subscriptions (Online Payments) -->
    <section class="subscriptions-section">
      <h2>Online Payment Submissions</h2>
      <p class="muted">Pending online payments with QR proof that need admin verification.</p>
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
  <div id="confirmModal">
    <div class="modal-card">
      <h3 id="confirmTitle">Confirm Action</h3>
      <p id="confirmMessage">Are you sure?</p>
      <div id="rejectReasonContainer" style="display:none;">
        <label>Reason for Rejection:</label>
        <textarea id="rejectReason" rows="3" placeholder="Enter rejection reason..."></textarea>
      </div>
      <div style="display:flex; gap:10px; margin-top:16px; justify-content:flex-end;">
        <button id="confirmCancel" class="btn-modal">Cancel</button>
        <button id="confirmOk" class="btn-modal">Confirm</button>
      </div>
    </div>
  </div>

  <script>
    // Pass PHP environment paths to JavaScript
    window.UPLOADS_PATH = '<?= UPLOADS_PATH ?>';
  </script>
  <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
  <script src="<?= PUBLIC_PATH ?>/php/admin/js/subscriptions.js"></script>
</body>

</html>
