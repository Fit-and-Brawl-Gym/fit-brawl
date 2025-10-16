// admin/js/subscriptions.js

async function loadSubscriptions(status = '') {
  const tbody = document.querySelector('#subsTable tbody');
  tbody.innerHTML = '<tr><td colspan="8" align="center">Loading...</td></tr>';

  try {
    const res = await fetch('api/admin_subcriptions_api.php?action=fetch&status=' + status);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const data = await res.json();
    console.log("Fetched data:", data);

    tbody.innerHTML = '';

    if (data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" align="center">No subscriptions found.</td></tr>';
      return;
    }

    data.forEach(sub => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${sub.id}</td>
        <td>${sub.username}</td>
        <td>${sub.plan_name}</td>
        <td>${sub.amount ?? '₱' + (sub.price || '0')}</td>
        <td>${sub.status}</td>
        <td>${sub.qr_proof || 'No proof'}</td>
        <td>${sub.date_submitted}</td>
        <td>
          ${sub.status === 'Pending'
          ? `<button onclick="approveSubscription(${sub.id})">Approve</button>
               <button onclick="rejectSubscription(${sub.id})">Reject</button>`
          : '-'}
        </td>`;
      tbody.appendChild(tr);
    });
  } catch (err) {
    console.error('Error loading data:', err);
    tbody.innerHTML = '<tr><td colspan="8" align="center">Error loading data.</td></tr>';
  }
}


async function updateStatus(id, status) {
  const remarks = prompt(`Enter remarks for ${status}:`) || "";

  const res = await fetch("api\admin_subscriptions_api.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, status, remarks })
  });

  const data = await res.json();
  alert(data.message || data.error);
  loadSubscriptions("Pending");
}

// Load pending by default
document.addEventListener("DOMContentLoaded", () => loadSubscriptions("Pending"));
