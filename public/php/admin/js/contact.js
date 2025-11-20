// Helper to get CSRF token from hidden input
function getCSRFToken() {
    return document.getElementById('csrf_token')?.value || '';
}
async function loadContacts() {
    const res = await fetch('api/admin_contacts_api.php?action=fetch');
    const data = await res.json();
    const tbody = document.querySelector('#contactsTable tbody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" align="center">No inquiries found.</td></tr>';
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        if (row.status === 'Unread') tr.classList.add('highlight');
        tr.innerHTML = `
      <td>${row.id}</td>
      <td>${row.name}</td>
      <td>${row.email}</td>
      <td>${row.phone ?? '-'}</td>
      <td>${row.message}</td>
      <td>${row.status}</td>
      <td>${row.date_sent}</td>
      <td>
        <button onclick="markRead(${row.id})">Mark Read</button>
        <button onclick="openReply('${row.email}')">Reply</button>
        <button onclick="deleteInquiry(${row.id})">Delete</button>
      </td>`;
        tbody.appendChild(tr);
    });
}

async function markRead(id) {
    await fetch('api/admin_contacts_api.php?action=mark_read', {
        method: 'POST',
        body: new URLSearchParams({ id, csrf_token: getCSRFToken() })
    });
    loadContacts();
}

async function deleteInquiry(id) {
    if (!confirm('Delete this inquiry?')) return;
    await fetch('api/admin_contacts_api.php?action=delete', {
        method: 'POST',
        body: new URLSearchParams({ id, csrf_token: getCSRFToken() })
    });
    loadContacts();
}

function openReply(email) {
    const subject = prompt("Enter subject:");
    if (!subject) return;
    const message = prompt("Enter your message:");
    if (!message) return;

    fetch('api/admin_contacts_api.php?action=reply', {
        method: 'POST',
        body: new URLSearchParams({ email, subject, message, csrf_token: getCSRFToken() })
    }).then(res => res.json())
        .then(data => alert(data.msg))
        .then(() => loadContacts());
}

loadContacts();
