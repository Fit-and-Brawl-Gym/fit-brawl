(function () {
  const API_URL = 'api/admin_subscriptions_api.php';
  const CASH_TABLE_ID = 'cashTableBody';
  const ONLINE_TABLE_ID = 'processingTableBody';
  const REJECTED_TABLE_ID = 'rejectedTableBody';

  const modal = document.getElementById('confirmModal');
  const modalTitle = document.getElementById('confirmTitle');
  const modalMessage = document.getElementById('confirmMessage');
  const modalReasonWrap = document.getElementById('rejectReasonContainer');
  const modalReasonInput = document.getElementById('rejectReason');
  const modalConfirmBtn = document.getElementById('confirmOk');
  const modalCancelBtn = document.getElementById('confirmCancel');

  let pendingAction = null; // { action: 'approve'|'reject', id: number }
  const cache = new Map();
  const inflight = new Map();

  document.addEventListener('DOMContentLoaded', () => {
    refreshAll();
  });

  async function refreshAll(force = false) {
    await Promise.all([
      loadProcessingTables(force),
      loadRejectedTable(force)
    ]).catch(error => console.error('Failed to refresh subscriptions:', error));
  }

  async function loadProcessingTables(force = false) {
    try {
      const data = await fetchSubscriptions('processing', force);
      renderCashTable(data);
      renderOnlineTable(data);
    } catch (error) {
      console.error('Unable to load processing subscriptions:', error);
      renderErrorRow(CASH_TABLE_ID, 6, 'Error loading data.');
      renderErrorRow(ONLINE_TABLE_ID, 6, 'Error loading data.');
    }
  }

  async function loadRejectedTable(force = false) {
    try {
      const data = await fetchSubscriptions('rejected', force);
      renderRejectedTable(data);
    } catch (error) {
      console.error('Unable to load rejected subscriptions:', error);
      renderErrorRow(REJECTED_TABLE_ID, 5, 'Error loading data.');
    }
  }

  async function fetchSubscriptions(type, force = false) {
    if (!force && cache.has(type)) {
      return cache.get(type);
    }

    if (inflight.has(type)) {
      return inflight.get(type);
    }

    const request = fetch(`${API_URL}?action=fetch&type=${encodeURIComponent(type)}`)
      .then(async response => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        const json = await response.json();
        if (!json.success || !Array.isArray(json.data)) {
          throw new Error(json.message || 'Unexpected response');
        }
        cache.set(type, json.data);
        return json.data;
      })
      .finally(() => inflight.delete(type));

    inflight.set(type, request);
    return request;
  }

  function renderCashTable(data) {
    const tbody = document.getElementById(CASH_TABLE_ID);
    if (!tbody) return;

    const rows = (data || []).filter(sub => sub.payment_method === 'cash');
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#999;">No cash payments pending.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(sub => {
      const paymentStatus = sub.cash_payment_status === 'paid'
        ? '<span class="status-badge status-paid">Paid</span>'
        : '<span class="status-badge status-unpaid">Unpaid</span>';
      const actionCell = sub.cash_payment_status === 'unpaid'
        ? `<button class="btn-approve" onclick="markCashAsPaid(${sub.id})">Mark as Paid</button>
           <button class="btn-reject" onclick="updateSubscription(${sub.id}, 'Rejected')">Reject</button>`
        : `<span class="muted">Payment received ${formatDate(sub.cash_payment_date)}</span>`;

      return `
        <tr>
          <td>${sub.id}</td>
          <td>${escapeHtml(sub.member || '')}</td>
          <td>${escapeHtml(sub.plan || '—')}</td>
          <td>${formatDate(sub.date_submitted)}</td>
          <td>${paymentStatus}</td>
          <td>${actionCell}</td>
        </tr>`;
    }).join('');
  }

  function renderOnlineTable(data) {
    const tbody = document.getElementById(ONLINE_TABLE_ID);
    if (!tbody) return;

    const rows = (data || []).filter(sub => !sub.payment_method || sub.payment_method === 'online');
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#999;">No pending online payments.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(sub => {
      const qrLink = sub.qr_proof
        ? `<a class="qr-link" href="${window.UPLOADS_PATH}/receipts/${encodeURIComponent(sub.qr_proof)}" target="_blank">View</a>`
        : '—';
      return `
        <tr>
          <td>${sub.id}</td>
          <td>${escapeHtml(sub.member || '')}</td>
          <td>${escapeHtml(sub.plan || '')}</td>
          <td>${qrLink}</td>
          <td>${formatDateTime(sub.date_submitted)}</td>
          <td>
            <button class="approve-btn" onclick="updateSubscription(${sub.id}, 'Approved')">Approve</button>
            <button class="reject-btn" onclick="updateSubscription(${sub.id}, 'Rejected')">Reject</button>
          </td>
        </tr>`;
    }).join('');
  }

  function renderRejectedTable(data) {
    const tbody = document.getElementById(REJECTED_TABLE_ID);
    if (!tbody) return;

    if (!data || !data.length) {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#999;">No rejected submissions.</td></tr>`;
      return;
    }

    tbody.innerHTML = data.map(sub => `
      <tr>
        <td>${sub.id}</td>
        <td>${escapeHtml(sub.member || '')}</td>
        <td>${escapeHtml(sub.plan || '')}</td>
        <td>${escapeHtml(sub.remarks || 'No reason provided')}</td>
        <td>${formatDateTime(sub.date_submitted)}</td>
      </tr>`).join('');
  }

  function renderErrorRow(tableId, colspan, message) {
    const tbody = document.getElementById(tableId);
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center; color:red;">${message}</td></tr>`;
  }

  function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    return isNaN(date.getTime())
      ? value
      : date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    return isNaN(date.getTime())
      ? value
      : date.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
  }

  function openActionModal(action, id) {
    pendingAction = { action, id };
    const isReject = action === 'reject';
    modalTitle.textContent = isReject ? 'Reject Subscription' : 'Approve Subscription';
    modalMessage.textContent = isReject
      ? 'Please provide a reason for rejection:'
      : 'Are you sure you want to approve this subscription?';
    modalReasonWrap.style.display = isReject ? 'block' : 'none';
    modalReasonInput.value = '';
    setModalProcessing(false);
    modal.classList.add('show');
  }

  function setModalProcessing(isProcessing) {
    if (isProcessing) {
      if (!modalConfirmBtn.dataset.originalText) {
        modalConfirmBtn.dataset.originalText = modalConfirmBtn.textContent;
      }
      modalConfirmBtn.disabled = true;
      modalConfirmBtn.textContent = 'Processing...';
    } else {
      modalConfirmBtn.disabled = false;
      modalConfirmBtn.textContent = modalConfirmBtn.dataset.originalText || 'Confirm';
    }
  }

  function closeModal() {
    modal.classList.remove('show');
    pendingAction = null;
    modalReasonInput.value = '';
    setModalProcessing(false);
  }

  modalCancelBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  modalConfirmBtn.addEventListener('click', async () => {
    if (!pendingAction) return;
    const { action, id } = pendingAction;
    const reason = action === 'reject' ? modalReasonInput.value.trim() : '';
    if (action === 'reject' && !reason) {
      alert('Please provide a rejection reason.');
      modalReasonInput.focus();
      return;
    }
    try {
      setModalProcessing(true);
      await performAction(action, id, reason);
      closeModal();
    } catch (error) {
      console.error('Subscription update failed:', error);
      alert(error.message || 'Unable to complete the action.');
      setModalProcessing(false);
    }
  });

  async function performAction(action, id, reason = '') {
    const formData = new FormData();
    formData.append('id', id);
    if (window.CSRF_TOKEN) {
      formData.append('csrf_token', window.CSRF_TOKEN);
    }
    if (action === 'reject' && reason) {
      formData.append('remarks', reason);
    }

    const response = await fetch(`${API_URL}?action=${action}`, {
      method: 'POST',
      body: formData
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(result.message || `HTTP ${response.status}`);
    }

    await refreshAll(true);
  }

  window.updateSubscription = function (id, status) {
    const action = status === 'Approved' ? 'approve' : 'reject';
    openActionModal(action, id);
  };

  window.markCashAsPaid = async function (id) {
    if (!confirm('Mark this cash payment as received?')) {
      return;
    }

    try {
      const formData = new FormData();
      formData.append('id', id);
      if (window.CSRF_TOKEN) {
        formData.append('csrf_token', window.CSRF_TOKEN);
      }

      const response = await fetch(`${API_URL}?action=mark_cash_paid`, {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || `HTTP ${response.status}`);
      }

      await refreshAll(true);
    } catch (error) {
      console.error('Failed to mark cash payment:', error);
      alert(error.message || 'Unable to update payment status.');
    }
  };
})();
