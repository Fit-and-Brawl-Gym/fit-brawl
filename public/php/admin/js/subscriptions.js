document.addEventListener("DOMContentLoaded", () => {
  // Load cash payments, online payments, and rejected memberships
  loadCashPayments();
  loadOnlinePayments();
  loadRejectedSubmissions();
});

// Load online payment submissions (non-cash pending payments)
async function loadOnlinePayments() {
  const tbody = document.getElementById('processingTableBody');
  if (!tbody) return;

  tbody.innerHTML = `<tr><td colspan="6" align="center">Loading...</td></tr>`;

  try {
    const response = await fetch(`api/admin_subscriptions_api.php?action=fetch&type=processing`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const result = await response.json();
    if (!result.success) {
      console.error('API Error:', result.message);
      tbody.innerHTML = `<tr><td colspan="6" align="center">Error loading data.</td></tr>`;
      return;
    }

    // Filter for online payments only (or payments without payment_method field for backward compatibility)
    const onlinePayments = result.data.filter(sub => !sub.payment_method || sub.payment_method === 'online');

    if (onlinePayments.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" align="center">No pending online payments.</td></tr>`;
      return;
    }

    tbody.innerHTML = onlinePayments.map(sub => {
      const qrLink = sub.qr_proof
        ? `<a class="qr-link" href="${window.UPLOADS_PATH}/receipts/${encodeURIComponent(sub.qr_proof)}" target="_blank">View</a>`
        : '—';
      const dateFormatted = formatDate(sub.date_submitted || '');

      return `
        <tr>
          <td>${sub.id}</td>
          <td>${escapeHtml(sub.member || '')}</td>
          <td>${escapeHtml(sub.plan || '')}</td>
          <td>${qrLink}</td>
          <td>${dateFormatted}</td>
          <td>
            <button class="approve-btn" onclick="updateSubscription(${sub.id}, 'Approved')">Approve</button>
            <button class="reject-btn" onclick="updateSubscription(${sub.id}, 'Rejected')">Reject</button>
          </td>
        </tr>
      `;
    }).join('');
  } catch (error) {
    console.error('Error loading online payments:', error);
    tbody.innerHTML = `<tr><td colspan="6" align="center">Error loading data.</td></tr>`;
  }
}

// Load rejected submissions
async function loadRejectedSubmissions() {
  const tbody = document.getElementById('rejectedTableBody');
  if (!tbody) return;

  tbody.innerHTML = `<tr><td colspan="5" align="center">Loading...</td></tr>`;

  try {
    const response = await fetch(`api/admin_subscriptions_api.php?action=fetch&type=rejected`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const result = await response.json();
    if (!result.success) {
      console.error('API Error:', result.message);
      tbody.innerHTML = `<tr><td colspan="5" align="center">Error loading data.</td></tr>`;
      return;
    }

    if (result.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" align="center">No rejected submissions.</td></tr>`;
      return;
    }

    tbody.innerHTML = result.data.map(sub => {
      const dateFormatted = formatDate(sub.date_submitted || '');
      const remarks = escapeHtml(sub.remarks || 'No reason provided');

      return `
        <tr>
          <td>${sub.id}</td>
          <td>${escapeHtml(sub.member || '')}</td>
          <td>${escapeHtml(sub.plan || '')}</td>
          <td>${dateFormatted}</td>
          <td>${remarks}</td>
        </tr>
      `;
    }).join('');
  } catch (error) {
    console.error('Error loading rejected submissions:', error);
    tbody.innerHTML = `<tr><td colspan="5" align="center">Error loading data.</td></tr>`;
  }
}

// Load cash payments (separate function for clarity)
async function loadCashPayments() {
  const tbody = document.getElementById('cashTableBody');
  if (!tbody) return;

  tbody.innerHTML = `<tr><td colspan="6" align="center">Loading...</td></tr>`;

  try {
    const response = await fetch(`api/admin_subscriptions_api.php?action=fetch&type=processing`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const result = await response.json();
    if (!result.success) {
      console.error('API Error:', result.message);
      tbody.innerHTML = `<tr><td colspan="6" align="center">Error loading data.</td></tr>`;
      return;
    }

    // Filter for cash payments only
    const cashPayments = result.data.filter(sub => sub.payment_method === 'cash');

    if (cashPayments.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#999;">No records found.</td></tr>`;
      return;
    }

    tbody.innerHTML = cashPayments.map(sub => {
      const id = sub.id;
      const member = escapeHtml(sub.member || '');
      const plan = escapeHtml(sub.plan || '—');
      const dateSubmitted = formatDate(sub.date_submitted);
      const paymentStatus = sub.cash_payment_status === 'paid'
        ? `<span class="status-badge status-paid">Paid</span>`
        : `<span class="status-badge status-unpaid">Unpaid</span>`;

      const action = sub.cash_payment_status === 'unpaid'
        ? `<button class="btn-approve" onclick="markCashAsPaid(${id})">Mark as Paid</button>
           <button class="btn-reject" onclick="updateSubscription(${id}, 'Rejected')">Reject</button>`
        : `<span class="muted">Payment received ${formatDate(sub.cash_payment_date)}</span>`;

      return `
        <tr>
          <td>${id}</td>
          <td>${member}</td>
          <td>${plan}</td>
          <td>${dateSubmitted}</td>
          <td>${paymentStatus}</td>
          <td>${action}</td>
        </tr>
      `;
    }).join('');
  } catch (error) {
    console.error('Error loading cash payments:', error);
    tbody.innerHTML = `<tr><td colspan="6" align="center">Error loading data.</td></tr>`;
  }
}

// Mark cash payment as paid
async function markCashAsPaid(id) {
  if (!confirm('Mark this cash payment as received?')) return;

  try {
    const formData = new FormData();
    formData.append('id', id);
    if (window.CSRF_TOKEN) {
      formData.append('csrf_token', window.CSRF_TOKEN);
    }

    const response = await fetch('api/admin_subscriptions_api.php?action=mark_cash_paid', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      alert('Cash payment marked as received!');
      loadCashPayments(); // Reload cash payments table
    } else {
      alert('Error: ' + (result.message || 'Failed to update payment status'));
    }
  } catch (error) {
    console.error('Error marking cash payment:', error);
    alert('Failed to update payment status');
  }
}

function loadSubscriptions(status = "", targetTableId = "processTable") {
  const table = document.getElementById(targetTableId);
  const tbody = table.querySelector("tbody");
  tbody.innerHTML = `<tr><td colspan="8" align="center">Loading...</td></tr>`;

  const url = `api/admin_subscriptions_api.php?action=fetch&type=${status.toLowerCase() || 'processing'}`;
  fetch(url)
    .then(r => r.json())
    .then(response => {
      if (!response.success || !Array.isArray(response.data)) {
        throw new Error(response.message || "Invalid data format");
      }

      const data = response.data;

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
  const qrLink = sub.qr_proof ? `<a class="qr-link" href="${window.UPLOADS_PATH}/receipts/${encodeURIComponent(sub.qr_proof)}" target="_blank">View</a>` : '—';
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

  // Remove old listeners and replace with clones
  cancelBtn.replaceWith(cancelBtn.cloneNode(true));
  okBtn.replaceWith(okBtn.cloneNode(true));
  const newCancel = document.getElementById('confirmCancel');
  const newOk = document.getElementById('confirmOk');

  newOk.addEventListener('click', () => {
    // restore modal to default state
    reasonInput.readOnly = false;
    reasonInput.style.background = '';
    reasonInput.value = '';
    reasonContainer.style.display = 'none';
    newCancel.style.display = '';
    newOk.textContent = 'OK';
    title.textContent = 'Confirm action';
    msg.textContent = 'Are you sure?';
    modal.classList.remove('show');
  });

  newCancel.addEventListener('click', () => {
    modal.classList.remove('show');
    reasonInput.readOnly = false;
    reasonInput.style.background = '';
    reasonInput.value = '';
    reasonContainer.style.display = 'none';
    newCancel.style.display = '';
    newOk.textContent = 'OK';
    title.textContent = 'Confirm action';
    msg.textContent = 'Are you sure?';
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
  newOk.classList.add('btn-modal');
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

  newCancel.classList.add('btn-modal');
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
  // Determine the action and prepare form data
  const action = status === 'Approved' ? 'approve' : 'reject';
  const formData = new FormData();
  formData.append('id', id);
  if (action === 'reject' && reason) {
    formData.append('remarks', reason);
  }
  // Add CSRF token
  if (window.CSRF_TOKEN) {
    formData.append('csrf_token', window.CSRF_TOKEN);
  }

  fetch(`api/admin_subscriptions_api.php?action=${action}`, {
    method: "POST",
    body: formData
  })
    .then(resp => resp.json())
    .then(result => {
      if (result.success) {
        // Reload all tables
        loadCashPayments();
        loadOnlinePayments();
        loadRejectedSubmissions();
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

    // Filter data based on table type
    let filteredData = result.data;

    // For processing table, exclude cash payments (they're shown separately)
    if (tableBodyId === 'processingTableBody') {
      filteredData = result.data.filter(sub => sub.payment_method !== 'cash');
    }

    if (filteredData.length === 0) {
      const colspan = type === 'processing' ? 6 : (type === 'approved' ? 6 : 5);
      tbody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center; color:#999;">No records found.</td></tr>`;
      return;
    }

    // Populate rows based on type
    filteredData.forEach(sub => {
      const row = document.createElement('tr');

      if (type === 'processing') {
        row.innerHTML = `
                    <td>${sub.id}</td>
                    <td>${escapeHtml(sub.member)}</td>
                    <td>${escapeHtml(sub.plan)}</td>
                    <td>${sub.qr_proof ? `<a href="${window.UPLOADS_PATH}/receipts/${escapeHtml(sub.qr_proof)}" target="_blank" class="qr-link">View</a>` : '—'}</td>
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
                    <td>${sub.qr_proof ? `<a href="${window.UPLOADS_PATH}/receipts/${escapeHtml(sub.qr_proof)}" target="_blank" class="qr-link">View</a>` : '—'}</td>
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
  // Add CSRF token
  if (window.CSRF_TOKEN) {
    formData.append('csrf_token', window.CSRF_TOKEN);
  }
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
      // Reload all tables including cash payments
      loadCashPayments();
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

// Approve subscription function
async function approveSub(id) {
  const row = document.querySelector(`#processingTableBody tr[data-id="${id}"]`);
  if (!row) return;

  // Call the existing approve function
  await executeAction(id, 'approve');

  // Log the action
  const response = await fetch('api/subscription_actions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'approve',
      id: id,
      // Add member info for logging
      member_name: row.querySelector('td:nth-child(2)').textContent,
      plan_name: row.querySelector('td:nth-child(3)').textContent
    })
  });

  const result = await response.json();
  if (!result.success) {
    console.error('Logging error:', result.message);
  }
}

// Reject subscription function
async function rejectSub(id, reason) {
  console.log('rejectSub called with id:', id, 'reason:', reason);
  const row = document.querySelector(`#processingTableBody tr[data-id="${id}"]`);
  if (!row) {
    console.error('Row not found for id:', id);
    return;
  }

  // Call the existing reject function
  await executeAction(id, 'reject', reason);

  // Log the action
  const response = await fetch('api/subscription_actions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'reject',
      id: id,
      reason: reason,
      // Add member info for logging
      member_name: row.querySelector('td:nth-child(2)').textContent,
      plan_name: row.querySelector('td:nth-child(3)').textContent
    })
  });

  const result = await response.json();
  if (!result.success) {
    console.error('Logging error:', result.message);
  }
}
