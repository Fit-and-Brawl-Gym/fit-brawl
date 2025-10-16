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
    if (heading) heading.setAttribute('aria-expanded', 'true');
    loadSubscriptions('Rejected', 'rejectedTable');
  } else {
    container.style.display = 'none';
    // toggle icon back to "eye"
    if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
    btn.setAttribute('aria-pressed', 'false');
    if (heading) heading.setAttribute('aria-expanded', 'false');
  }
}

// small helper to avoid XSS in cells
function escapeHtml(str) {
  return (str === null || str === undefined) ? '' : String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
