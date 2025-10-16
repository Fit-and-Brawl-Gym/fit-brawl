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
  <link rel="stylesheet" href="css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">
    <header>
      <h1>Manage Subscriptions</h1>
      <p>Approve or reject membership payments — processing and members separated for clarity.</p>
    </header>

    <section class="subscriptions-section">
      <h2>Processing Subscriptions</h2>
      <p class="muted">Pending payments that need admin action.</p>
      <div class="controls">
        <button onclick="loadSubscriptions('Pending','processTable')">Refresh</button>
      </div>

      <div class="table-wrap">
        <table id="processTable" class="subs-table" cellpadding="8" role="grid" aria-label="Processing subscriptions">
          <thead>
            <tr>
              <th>ID</th>
              <th>Member</th>
              <th>Plan</th>
              <th>Amount</th>
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
      </div>
    </section>

    <section class="subscriptions-section" style="margin-top:28px;">
      <h2>Current Members</h2>
      <p class="muted">Approved subscriptions / active members.</p>
      <div class="controls">
        <button onclick="loadSubscriptions('Approved','membersTable')">Refresh</button>
      </div>

      <div class="table-wrap">
        <table id="membersTable" class="subs-table" cellpadding="8" role="grid" aria-label="Current members">
          <thead>
            <tr>
              <th>ID</th>
              <th>Member</th>
              <th>Plan</th>
              <th>Amount</th>
              <th>QR Proof</th>
              <th>Date Submitted</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="8" align="center">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="subscriptions-section" style="margin-top:24px;">
      <h2 id="rejectedHeading" role="button" aria-expanded="false" style="cursor:pointer;" onclick="toggleRejected()">
        Rejected Submissions
        <button id="toggleRejectedBtn" class="small-link" onclick="toggleRejected(); event.stopPropagation()"
          aria-pressed="false" aria-label="Toggle rejected submissions">
          <i class="fa-solid fa-eye" aria-hidden="true"></i>
        </button>
      </h2>

      <div id="rejectedContainer" style="display:none; margin-top:12px;">
        <div class="controls">
          <button onclick="loadSubscriptions('Rejected','rejectedTable')">Refresh Rejected</button>
        </div>

        <div class="table-wrap">
          <table id="rejectedTable" class="subs-table" cellpadding="8" role="grid" aria-label="Rejected submissions">
            <thead>
              <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>QR Proof</th>
                <th>Date Submitted</th>
                <th>Reason</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="8" align="center">No data loaded.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </main>

  <!-- Confirmation Modal -->
  <div id="confirmModal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="confirmTitle"
      aria-describedby="confirmMessage">
      <h3 id="confirmTitle">Confirm action</h3>
      <p id="confirmMessage">Are you sure?</p>

      <!-- Rejection reason (hidden unless rejecting) -->
      <div id="rejectReasonContainer" style="display:none; margin-top:12px;">
        <label for="rejectReason" style="display:block; font-weight:600; margin-bottom:6px;">Rejection reason (will be
          sent to the user)</label>
        <textarea id="rejectReason" rows="4"
          style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; resize:none; overflow:auto;"
          placeholder="Enter reason for rejection..."></textarea>
      </div>

      <div class="modal-actions" style="margin-top:14px;">
        <button id="confirmCancel" class="btn-modal">Cancel</button>
        <button id="confirmOk" class="btn-modal">OK</button>
      </div>
    </div>
  </div>

  <script src="js/subscriptions.js"></script>
</body>

</html>