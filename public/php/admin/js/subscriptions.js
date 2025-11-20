// Show success notification
function showNotification(message, type = 'success') {
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 20px;
    background: ${type === 'success' ? '#28a745' : '#dc3545'};
    color: white;
    border-radius: 6px;
    z-index: 3000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 300ms ease;
  `;
  document.body.appendChild(notification);
  setTimeout(() => {
    notification.style.animation = 'slideOut 300ms ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add CSS animations if not already present
if (!document.getElementById('notification-styles')) {
  const style = document.createElement('style');
  style.id = 'notification-styles';
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(400px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(400px); opacity: 0; }
    }
  `;
  document.head.appendChild(style);
}

// Modal-based approve/reject flow
let currentAction = null;
let currentId = null;

// Load data when page loads
document.addEventListener('DOMContentLoaded', () => {
  loadSubscriptions('processing', 'processingTableBody');
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

  if (currentAction === 'reject') {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
      alert('Please provide a reason for rejection.');
      return;
    }
    formData.append('remarks', reason);
  }

  // Save action and id before closing modal (closeModal resets these to null)
  const action = currentAction;
  const actionId = currentId;
  
  // Close modal immediately for better UX
  closeModal();

  try {
    const response = await fetch(`api/admin_subscriptions_api.php?action=${action}`, {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      // Show success notification
      const actionText = action === 'approve' ? 'approved' : 'rejected';

      showNotification(result.message || `Subscription ${actionText} successfully`, 'success');
      
      // Reload only the tables that exist on this page
      loadSubscriptions('processing', 'processingTableBody');
      loadSubscriptions('rejected', 'rejectedTableBody');
    } else {
      showNotification('Error: ' + (result.message || 'Operation failed'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification('An error occurred. Please try again.', 'error');
  }
}

