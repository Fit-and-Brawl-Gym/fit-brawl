document.addEventListener("DOMContentLoaded", () => {
  // load processing and members on page load
  loadSubscriptions('Pending', 'processTable');
  loadSubscriptions('Approved', 'membersTable');
});

function loadSubscriptions(status = "", targetTableId = "processTable") {
  const table = document.getElementById(targetTableId);
  const tbody = table.querySelector("tbody");
  tbody.innerHTML = `<tr><td colspan="8" align="center">Loading...</td></tr>`;

  const url = `api/admin_subscriptions_api.php` + (status ? `?status=${encodeURIComponent(status)}` : '');
  fetch(url)
    .then(r => r.json())
    .then(data => {
      if (!Array.isArray(data)) throw new Error("Invalid data format");

      // sort: earliest submissions first
      data.sort((a, b) => (Date.parse(a.date_submitted) || 0) - (Date.parse(b.date_submitted) || 0));

      if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" align="center">No records found.</td></tr>`;
        return;
      }

      tbody.innerHTML = data.map(sub => renderRowForTable(sub, targetTableId)).join("");
    })
    .catch(err => {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="8" align="center">Error loading data.</td></tr>`;
    });
}

// helper: format date_submitted for readability
function formatDate(dateStr) {
  if (!dateStr) return '';
  // Accept MySQL datetime or ISO; try best-effort parsing
  let d = new Date(dateStr);
  if (isNaN(d.getTime())) {
    // fallback: replace space with 'T' for "YYYY-MM-DD HH:MM:SS"
    d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateStr;
  }
  return d.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit'
  });
}

function renderRowForTable(sub, tableId) {
  const id = sub.id;
  const member = escapeHtml(sub.username || '');
  const plan = escapeHtml(sub.plan_name || '—');
  const price = (sub.price !== undefined && sub.price !== null) ? escapeHtml(sub.price) : '—';
  const qrLink = sub.qr_proof ? `<a class="qr-link" href="../../../../uploads/${encodeURIComponent(sub.qr_proof)}" target="_blank">View</a>` : '—';
  const niceDate = formatDate(sub.date_submitted || sub.date_submitted || '');
  const remarks = sub.remarks || '';

  // action cell
  let actionCell = '—';
  if (tableId === 'processTable') {
    actionCell = sub.status === 'Pending'
      ? `<button class="btn-approve" onclick="updateSubscription(${id}, 'Approved')">Approve</button>
         <button class="btn-reject" onclick="updateSubscription(${id}, 'Rejected')">Reject</button>`
      : '—';
  } else if (tableId === 'membersTable') {
    actionCell = '—';
  } else { // rejectedTable
    actionCell = remarks
      ? `<button class="btn-reject" onclick='viewRejectReason(${id}, ${JSON.stringify(remarks)})'>View reason</button>`
      : '—';
  }

  return `
    <tr>
      <td>${id}</td>
      <td>${member}</td>
      <td>${plan}</td>
      <td>${price}</td>
      <td>${qrLink}</td>
      <td>${niceDate}</td>
      <td>${actionCell}</td>
    </tr>
  `;
}

// small helper to view rejection reason (opens modal or alert)
function viewRejectReason(id, reason) {
  const modal = document.getElementById('confirmModal');
  const title = document.getElementById('confirmTitle');
  const msg = document.getElementById('confirmMessage');
  const reasonContainer = document.getElementById('rejectReasonContainer');
  const reasonInput = document.getElementById('rejectReason');
  const okBtn = document.getElementById('confirmOk');
  const cancelBtn = document.getElementById('confirmCancel');

  // set modal content for read-only view
  title.textContent = `Rejection reason (#${id})`;
  msg.textContent = '';
  reasonContainer.style.display = 'block';
  reasonInput.value = reason || '';
  reasonInput.readOnly = true;
  reasonInput.style.background = '#fafafa';
  reasonInput.style.resize = 'none';

  // show only Close button
  cancelBtn.style.display = 'none';
  okBtn.textContent = 'Close';

  // reset listeners safely
  okBtn.replaceWith(okBtn.cloneNode(true));
  const newOk = document.getElementById('confirmOk');

  newOk.addEventListener('click', () => {
    // restore modal to default state
    reasonInput.readOnly = false;
    reasonInput.style.background = '';
    reasonInput.value = '';
    reasonContainer.style.display = 'none';
    cancelBtn.style.display = '';
    okBtn.textContent = 'OK';
    title.textContent = 'Confirm action';
    msg.textContent = 'Are you sure?';
    modal.classList.remove('show');
  });

  modal.classList.add('show');

  // close by clicking backdrop (restore state)
  function onBackdropClick(e) {
    if (e.target === modal) {
      modal.classList.remove('show');
      reasonInput.readOnly = false;
      reasonInput.style.background = '';
      reasonContainer.style.display = 'none';
      cancelBtn.style.display = '';
      okBtn.textContent = 'OK';
      title.textContent = 'Confirm action';
      msg.textContent = 'Are you sure?';
      modal.removeEventListener('click', onBackdropClick);
    }
  }
  modal.addEventListener('click', onBackdropClick);
}

// =============================
// Approve / Reject (modal flow)
// =============================
let pendingSubAction = null; // { id, status, verb }

function updateSubscription(id, status) {
  const verb = status === 'Approved' ? 'approve' : (status === 'Rejected' ? 'reject' : status.toLowerCase());
  pendingSubAction = { id, status, verb };
  const capitalVerb = verb.charAt(0).toUpperCase() + verb.slice(1);
  // show reason input when rejecting
  const showReason = (status === 'Rejected');
  openConfirmModal(`Are you sure you want to ${verb} this subscription?`, capitalVerb, showReason);
}

function openConfirmModal(message, actionText = 'OK', showReason = false) {
  const modal = document.getElementById('confirmModal');
  const msgEl = document.getElementById('confirmMessage');
  const okBtn = document.getElementById('confirmOk');
  const cancelBtn = document.getElementById('confirmCancel');
  const reasonContainer = document.getElementById('rejectReasonContainer');
  const reasonInput = document.getElementById('rejectReason');

  msgEl.textContent = message;
  // show/hide reason input
  if (showReason) {
    reasonContainer.style.display = 'block';
    reasonInput.value = '';
    reasonInput.focus();
  } else {
    reasonContainer.style.display = 'none';
    reasonInput.value = '';
  }

  // reset buttons to avoid duplicate listeners
  okBtn.replaceWith(okBtn.cloneNode(true));
  cancelBtn.replaceWith(cancelBtn.cloneNode(true));

  const newOk = document.getElementById('confirmOk');
  const newCancel = document.getElementById('confirmCancel');

  newOk.textContent = actionText;
  modal.classList.add('show');

  newOk.addEventListener('click', () => {
    // if rejecting, require reason
    const reason = showReason ? document.getElementById('rejectReason').value.trim() : '';
    if (showReason && reason.length === 0) {
      alert('Please provide a reason for rejection.');
      document.getElementById('rejectReason').focus();
      return;
    }

    modal.classList.remove('show');
    if (pendingSubAction) {
      performSubscriptionUpdate(pendingSubAction.id, pendingSubAction.status, reason);
      pendingSubAction = null;
    }
  });

  newCancel.addEventListener('click', () => {
    modal.classList.remove('show');
    pendingSubAction = null;
  });

  function onBackdropClick(e) {
    if (e.target === modal) {
      modal.classList.remove('show');
      pendingSubAction = null;
      modal.removeEventListener('click', onBackdropClick);
    }
  }
  modal.addEventListener('click', onBackdropClick);
}

function performSubscriptionUpdate(id, status, reason = '') {
  fetch(`api/admin_subscriptions_api.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, status, reason })
  })
    .then(resp => resp.json())
    .then(result => {
      if (result.success) {
        // refresh both lists
        loadSubscriptions('Pending', 'processTable');
        loadSubscriptions('Approved', 'membersTable');
        // refresh rejected if visible
        const rejectedVisible = document.getElementById('rejectedContainer').style.display !== 'none';
        if (rejectedVisible) loadSubscriptions('Rejected', 'rejectedTable');
      } else {
        alert("Error: " + (result.message || "Failed to update."));
      }
    })
    .catch(err => {
      console.error(err);
      alert("Something went wrong.");
    });
}

// toggle rejected container
function toggleRejected() {
  const container = document.getElementById('rejectedContainer');
  const btn = document.getElementById('toggleRejectedBtn');
  const heading = document.getElementById('rejectedHeading');
  if (!container || !btn) return;

  const isHidden = container.style.display === 'none' || container.style.display === '';
  const icon = btn.querySelector('i');

  if (isHidden) {
    container.style.display = 'block';
    if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
    btn.setAttribute('aria-pressed', 'true');
    if (heading) heading.textContent = 'Rejected Subscriptions';
    // load rejected subscriptions if container is being shown
    loadSubscriptions('Rejected', 'rejectedTable');
  } else {
    container.style.display = 'none';
    if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
    btn.setAttribute('aria-pressed', 'false');
    if (heading) heading.textContent = 'Rejected Subscriptions (hidden)';
  }
}

// new modal-based approve/reject flow
let currentAction = null;
let currentId = null;

// Load data when page loads
document.addEventListener('DOMContentLoaded', () => {
  loadSubscriptions('processing', 'processingTableBody');
  loadSubscriptions('approved', 'approvedTableBody');
  loadSubscriptions('rejected', 'rejectedTableBody');
});

// Load subscriptions by type
async function loadSubscriptions(type, tableBodyId) {
  try {
    const response = await fetch(`api/admin_subscriptions_api.php?action=fetch&type=${type}`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (!result.success) {
      console.error('API Error:', result.message);
      return;
    }

    const tbody = document.getElementById(tableBodyId);
    if (!tbody) {
      console.error('Table body not found:', tableBodyId);
      return;
    }

    // Clear existing rows
    tbody.innerHTML = '';

    if (result.data.length === 0) {
      const colspan = type === 'processing' ? 6 : (type === 'approved' ? 6 : 5);
      tbody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center; color:#999;">No records found.</td></tr>`;
      return;
    }

    // Populate rows based on type
    result.data.forEach(sub => {
      const row = document.createElement('tr');

      if (type === 'processing') {
        row.innerHTML = `
                    <td>${sub.id}</td>
                    <td>${escapeHtml(sub.member)}</td>
                    <td>${escapeHtml(sub.plan)}</td>
                    <td>${sub.qr_proof ? `<a href="../../../../uploads/qr_proofs/${escapeHtml(sub.qr_proof)}" target="_blank" class="qr-link">View</a>` : '—'}</td>
                    <td>${formatDateTime(sub.date_submitted)}</td>
                    <td>
                        <button class="btn-approve" onclick="approveSubscription(${sub.id})">Approve</button>
                        <button class="btn-reject" onclick="rejectSubscription(${sub.id})">Reject</button>
                    </td>
                `;
      } else if (type === 'approved') {
        row.innerHTML = `
                    <td>${sub.id}</td>
                    <td>${escapeHtml(sub.member)}</td>
                    <td>${escapeHtml(sub.plan)}</td>
                    <td>${sub.qr_proof ? `<a href="../../../../uploads/qr_proofs/${escapeHtml(sub.qr_proof)}" target="_blank" class="qr-link">View</a>` : '—'}</td>
                    <td>${sub.start_date ? formatDate(sub.start_date) : '—'}</td>
                    <td>${sub.end_date ? formatDate(sub.end_date) : '—'}</td>
                `;
      } else if (type === 'rejected') {
        row.innerHTML = `
                    <td>${sub.id}</td>
                    <td>${escapeHtml(sub.member)}</td>
                    <td>${escapeHtml(sub.plan)}</td>
                    <td>${escapeHtml(sub.remarks || 'No reason provided')}</td>
                    <td>${formatDateTime(sub.date_submitted)}</td>
                `;
      }

      tbody.appendChild(row);
    });

  } catch (error) {
    console.error('Error loading subscriptions:', error);
    const tbody = document.getElementById(tableBodyId);
    if (tbody) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red;">Error loading data: ${error.message}</td></tr>`;
    }
  }
}

// Helper functions
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDateTime(dateTimeString) {
  const date = new Date(dateTimeString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ', ' +
    date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

// Approve subscription
function approveSubscription(id) {
  currentAction = 'approve';
  currentId = id;
  document.getElementById('confirmTitle').textContent = 'Approve Subscription';
  document.getElementById('confirmMessage').textContent = 'Are you sure you want to approve this subscription?';
  document.getElementById('rejectReasonContainer').style.display = 'none';
  document.getElementById('confirmModal').style.display = 'flex';
}

// Reject subscription
function rejectSubscription(id) {
  currentAction = 'reject';
  currentId = id;
  document.getElementById('confirmTitle').textContent = 'Reject Subscription';
  document.getElementById('confirmMessage').textContent = 'Please provide a reason for rejection:';
  document.getElementById('rejectReasonContainer').style.display = 'block';
  document.getElementById('rejectReason').value = '';
  document.getElementById('confirmModal').style.display = 'flex';
}

// Close modal
function closeModal() {
  document.getElementById('confirmModal').style.display = 'none';
  currentAction = null;
  currentId = null;
}

// Execute approve/reject action
async function executeAction() {
  if (!currentAction || !currentId) return;

  const formData = new FormData();
  formData.append('id', currentId);

  if (currentAction === 'reject') {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
      alert('Please provide a reason for rejection.');
      return;
    }
    formData.append('remarks', reason);
  }

  try {
    const response = await fetch(`api/admin_subscriptions_api.php?action=${currentAction}`, {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      closeModal();
      // Reload all tables
      loadSubscriptions('processing', 'processingTableBody');
      loadSubscriptions('approved', 'approvedTableBody');
      loadSubscriptions('rejected', 'rejectedTableBody');
    } else {
      alert('Error: ' + (result.message || 'Operation failed'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  }
}
