let activeContacts = [];
let archivedContacts = [];
let currentFilter = 'all';
let refreshInterval;
let searchDebounce;

const csrfMetaTag = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfMetaTag ? csrfMetaTag.getAttribute('content') : '';
const csrfHeaders = csrfToken ? { 'X-CSRF-Token': csrfToken } : {};
const toastContainer = document.getElementById('toastContainer');

document.addEventListener('DOMContentLoaded', () => {
    loadContacts(false);
    setupEventListeners();
    startAutoRefresh();
});

function setupEventListeners() {
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            if (tab.classList.contains('active')) return;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentFilter = tab.dataset.filter;
            filterAndRenderContacts();
        });
    });

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => filterAndRenderContacts(), 250);
        });
    }

    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', handleReplySubmit);
    }
}

async function loadContacts(preserveExpanded = true) {
    const expandedIds = preserveExpanded ? getExpandedContactIds() : [];

    try {
        const response = await fetch('api/get_contacts.php', { headers: { ...csrfHeaders } });
        const text = await response.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Server returned invalid JSON: ' + text.substring(0, 120));
        }

        if (!data.success) {
            throw new Error(data.message || 'Failed to load contacts.');
        }

        const normalize = (contact) => ({
            ...contact,
            id: Number(contact.id),
            status: contact.status && contact.status !== '' ? contact.status : 'unread',
            phone_number: contact.phone_number || ''
        });

        activeContacts = (data.active_contacts ?? data.contacts ?? []).map(normalize);
        archivedContacts = (data.archived_contacts ?? []).map(normalize);

        updateStats(data);
        filterAndRenderContacts();
        restoreExpandedContacts(expandedIds);

    } catch (error) {
        console.error('Error loading contacts:', error);
        showError('Failed to load contacts: ' + error.message);
    }
}

function filterAndRenderContacts(explicitSearchTerm) {
    const expandedIds = getExpandedContactIds();
    const searchTerm = explicitSearchTerm !== undefined ? explicitSearchTerm : getSearchTerm();
    const isArchivedView = currentFilter === 'archived';

    let sourceList = isArchivedView ? archivedContacts : activeContacts;
    let filtered = [...sourceList];

    if (!isArchivedView && currentFilter !== 'all') {
        filtered = filtered.filter(contact => contact.status === currentFilter);
    }

    if (searchTerm) {
        const query = searchTerm.toLowerCase();
        filtered = filtered.filter(contact => (
            `${contact.first_name} ${contact.last_name}`.toLowerCase().includes(query) ||
            contact.email.toLowerCase().includes(query)
        ));
    }

    renderContacts(filtered, { isArchivedView });
    restoreExpandedContacts(expandedIds);
}

function renderContacts(contacts, options = {}) {
    const { isArchivedView = false } = options;
    const container = document.getElementById('contactsList');

    if (!container) return;

    if (contacts.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-envelope-open-text"></i>
                <h3>${isArchivedView ? 'No Archived Contacts' : 'No Contacts Found'}</h3>
                <p>${isArchivedView ? 'Archived messages will appear here.' : 'No contact inquiries match your current filter.'}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = contacts
        .map(contact => createContactRow(contact, { isArchivedView }))
        .join('');
}

function createContactRow(contact, options = {}) {
    const { isArchivedView = false } = options;
    const fullName = `${contact.first_name} ${contact.last_name}`.trim();
    const messagePreview = contact.message.length > 80
        ? `${contact.message.substring(0, 80)}...`
        : contact.message;
    const dateFormatted = formatDate(contact.date_submitted);
    const rowClasses = ['contact-row'];
    if (contact.status === 'unread' && !isArchivedView) rowClasses.push('unread');
    if (isArchivedView) rowClasses.push('archived');

    const badgeClass = isArchivedView ? 'archived' : contact.status;
    const badgeLabel = isArchivedView
        ? `Archived${contact.status ? ' • ' + contact.status : ''}`
        : contact.status;

    const phoneDisplay = contact.phone_number
        ? `<a href="tel:${contact.phone_number}">${escapeHtml(contact.phone_number)}</a>`
        : 'N/A';

    const actionsHtml = isArchivedView
        ? `
            <button class="btn-action btn-restore" onclick="restoreContact(${contact.id})">
                <i class="fa-solid fa-rotate-left"></i>
                Restore
            </button>
            <button class="btn-action btn-delete" onclick="deleteContact(${contact.id})">
                <i class="fa-solid fa-trash"></i>
                Delete
            </button>
        `
        : `
            <button class="btn-action btn-reply" onclick="openReplyModal(${contact.id}, '${escapeHtml(contact.email)}', '${escapeHtml(fullName)}')">
                <i class="fa-solid fa-reply"></i>
                Reply
            </button>
            ${contact.status === 'unread'
                ? `<button class="btn-action btn-mark-read" onclick="markAsRead(${contact.id})">
                        <i class="fa-solid fa-check"></i> Mark as Read
                   </button>`
                : `<button class="btn-action btn-mark-unread" onclick="markAsUnread(${contact.id})">
                        <i class="fa-solid fa-envelope"></i> Mark as Unread
                   </button>`}
            <button class="btn-action btn-archive" onclick="archiveContact(${contact.id})">
                <i class="fa-solid fa-box-archive"></i>
                Archive
            </button>
            <button class="btn-action btn-delete" onclick="deleteContact(${contact.id})">
                <i class="fa-solid fa-trash"></i>
                Delete
            </button>
        `;

    return `
        <div class="${rowClasses.join(' ')}" data-id="${contact.id}">
            <div class="contact-header" onclick="toggleContactDetails(${contact.id})">
                <div class="expand-icon">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
                <div class="contact-id">#${contact.id}</div>
                <div class="contact-info">
                    <div class="contact-name">${escapeHtml(fullName)}</div>
                    <div class="contact-message-preview">${escapeHtml(messagePreview)}</div>
                </div>
                <div class="contact-date">${dateFormatted}</div>
                <div class="contact-status-badge ${badgeClass}">
                    ${escapeHtml(badgeLabel)}
                </div>
            </div>
            <div class="contact-details">
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><a href="mailto:${contact.email}">${escapeHtml(contact.email)}</a></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value">${phoneDisplay}</span>
                    </div>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Full Message</span>
                    <div class="full-message">${escapeHtml(contact.message)}</div>
                </div>
                <div class="details-actions">${actionsHtml}</div>
            </div>
        </div>
    `;
}

function toggleContactDetails(id) {
    const row = document.querySelector(`.contact-row[data-id="${id}"]`);
    if (!row) return;
    row.classList.toggle('expanded');
}

function getExpandedContactIds() {
    return Array.from(document.querySelectorAll('.contact-row.expanded'))
        .map(row => Number(row.dataset.id));
}

function restoreExpandedContacts(ids) {
    ids.forEach(id => {
        const row = document.querySelector(`.contact-row[data-id="${id}"]`);
        if (row) row.classList.add('expanded');
    });
}

function getSearchTerm() {
    const input = document.getElementById('searchInput');
    return input ? input.value.trim() : '';
}

function updateStats(serverData = {}) {
    const totalEl = document.getElementById('totalContacts');
    const unreadEl = document.getElementById('unreadCount');
    const archivedEl = document.getElementById('archivedCount');

    if (totalEl) totalEl.textContent = activeContacts.length;
    if (unreadEl) unreadEl.textContent = serverData.unread_total ?? activeContacts.filter(c => c.status === 'unread').length;
    if (archivedEl) archivedEl.textContent = archivedContacts.length;
}

async function markAsRead(id) {
    await handleStatusChange('mark_read', id, 'read', 'Contact marked as read');
}

async function markAsUnread(id) {
    await handleStatusChange('mark_unread', id, 'unread', 'Contact marked as unread');
}

async function handleStatusChange(action, id, newStatus, successMessage) {
    try {
        await performContactAction(action, id);
        const contactEntry = findContactEntry(id);
        if (contactEntry) {
            contactEntry.list[contactEntry.index].status = newStatus;
        }
        filterAndRenderContacts();
        updateStats();
        showToast(successMessage, 'success');
    } catch (error) {
        console.error(error);
        showToast(error.message, 'error');
    }
}

async function archiveContact(id) {
    try {
        await performContactAction('archive', id);
        moveContactBetweenLists(id, true);
        filterAndRenderContacts();
        updateStats();
        showToast('Contact archived.', 'success');
    } catch (error) {
        console.error(error);
        showToast('Failed to archive contact: ' + error.message, 'error');
    }
}

async function restoreContact(id) {
    try {
        await performContactAction('restore', id);
        moveContactBetweenLists(id, false);
        filterAndRenderContacts();
        updateStats();
        showToast('Contact restored.', 'success');
    } catch (error) {
        console.error(error);
        showToast('Failed to restore contact: ' + error.message, 'error');
    }
}

async function deleteContact(id) {
    if (!confirm('Delete this contact permanently?')) return;

    try {
        await performContactAction('delete', id);
        extractContact(id);
        filterAndRenderContacts();
        updateStats();
        showToast('Contact deleted.', 'success');
    } catch (error) {
        console.error(error);
        showToast('Failed to delete contact: ' + error.message, 'error');
    }
}

function moveContactBetweenLists(id, toArchive) {
    const extracted = extractContact(id);
    if (!extracted) return;
    if (toArchive) {
        archivedContacts.unshift(extracted.contact);
    } else {
        activeContacts.unshift(extracted.contact);
    }
}

function extractContact(id) {
    const entry = findContactEntry(id);
    if (!entry) return null;
    const [contact] = entry.list.splice(entry.index, 1);
    return { contact, fromArchived: entry.isArchived };
}

function findContactEntry(id) {
    const numericId = Number(id);
    let index = activeContacts.findIndex(c => c.id === numericId);
    if (index !== -1) {
        return { list: activeContacts, index, isArchived: false };
    }
    index = archivedContacts.findIndex(c => c.id === numericId);
    if (index !== -1) {
        return { list: archivedContacts, index, isArchived: true };
    }
    return null;
}

async function performContactAction(action, id) {
    const response = await fetch('api/contact_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...csrfHeaders
        },
        body: JSON.stringify({ action, id })
    });

    const data = await response.json();
    if (!data.success) {
        throw new Error(data.message || 'Action failed.');
    }
    return data;
}

function openReplyModal(contactId, email, name) {
    document.getElementById('replyContactId').value = contactId;
    document.getElementById('replyTo').value = email;
    document.getElementById('replySubject').value = `Re: Contact Inquiry from ${name}`;
    document.getElementById('replyMessage').value = '';
    document.getElementById('replyModal').classList.add('active');
}

function closeReplyModal() {
    const modal = document.getElementById('replyModal');
    const form = document.getElementById('replyForm');
    if (!modal || !form) return;

    modal.classList.remove('active');
    form.reset();

    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Reply';
        submitButton.style.cursor = 'pointer';
        submitButton.style.opacity = '1';
    }
}

async function handleReplySubmit(e) {
    e.preventDefault();

    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalButtonHtml = submitButton.innerHTML;
    if (submitButton.disabled) return;

    const contactId = document.getElementById('replyContactId').value;
    const to = document.getElementById('replyTo').value;
    const subject = document.getElementById('replySubject').value;
    const message = document.getElementById('replyMessage').value;
    const sendCopy = document.getElementById('includeCopy').checked;
    const contactEntry = findContactEntry(Number(contactId));
    const originalMessage = contactEntry ? contactEntry.list[contactEntry.index].message : '';

    try {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        submitButton.style.cursor = 'not-allowed';
        submitButton.style.opacity = '0.6';

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        let response;
        try {
            response = await fetch('api/send_reply.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...csrfHeaders
                },
                body: JSON.stringify({
                    contact_id: contactId,
                    to,
                    subject,
                    message,
                    original_message: originalMessage,
                    send_copy: sendCopy
                }),
                signal: controller.signal
            });
        } finally {
            clearTimeout(timeoutId);
        }

        if (!response.ok) {
            throw new Error(`Server error (${response.status})`);
        }

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (error) {
            throw new Error('Invalid server response.');
        }

        if (!data.success) {
            throw new Error(data.message || 'Failed to send reply.');
        }

        closeReplyModal();
        showToast('Reply sent successfully.', 'success');
        await markAsRead(Number(contactId));

    } catch (error) {
        showToast('Failed to send reply: ' + error.message, 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonHtml;
        submitButton.style.cursor = 'pointer';
        submitButton.style.opacity = '1';
    }
}

function startAutoRefresh() {
    refreshInterval = setInterval(() => loadContacts(), 30000);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date)) return dateString;
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    const container = document.getElementById('contactsList');
    if (!container) return;
    container.innerHTML = `
        <div class="empty-state">
            <i class="fa-solid fa-exclamation-triangle" style="color: #e74c3c;"></i>
            <h3>Error</h3>
            <p>${message}</p>
        </div>
    `;
}

function showToast(message, type = 'info') {
    if (!toastContainer) {
        alert(message);
        return;
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toastContainer.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('visible'));

    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 250);
    }, 3500);
}
