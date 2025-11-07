let allContacts = [];
let currentFilter = 'all';
let refreshInterval;

// Load contacts on page load
document.addEventListener('DOMContentLoaded', function () {
    loadContacts();
    setupEventListeners();
    startAutoRefresh();
});

// Setup event listeners
function setupEventListeners() {
    // Tab clicks
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            filterAndRenderContacts();
        });
    });

    // Search input
    document.getElementById('searchInput').addEventListener('input', function (e) {
        filterAndRenderContacts(e.target.value);
    });

    // Reply form submission
    document.getElementById('replyForm').addEventListener('submit', handleReplySubmit);
}

// Load contacts from API
async function loadContacts() {
    try {
        const response = await fetch('api/get_contacts.php');
        const text = await response.text();
        console.log('Raw response:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
        }

        if (!data.success) {
            throw new Error(data.message || 'Failed to load contacts');
        }

        allContacts = data.contacts;
        updateStats();
        renderContacts(allContacts);

    } catch (error) {
        console.error('Error loading contacts:', error);
        showError('Failed to load contacts: ' + error.message);
    }
}

// Filter and render contacts
function filterAndRenderContacts(searchTerm = '') {
    let filtered = allContacts;

    // Filter by status
    if (currentFilter !== 'all') {
        filtered = filtered.filter(c => c.status === currentFilter);
    }

    // Filter by search term
    if (searchTerm) {
        const search = searchTerm.toLowerCase();
        filtered = filtered.filter(c =>
            (c.first_name + ' ' + c.last_name).toLowerCase().includes(search) ||
            c.email.toLowerCase().includes(search)
        );
    }

    renderContacts(filtered);
}

// Render contacts list
function renderContacts(contacts) {
    const container = document.getElementById('contactsList');

    if (contacts.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-envelope-open-text"></i>
                <h3>No Contacts Found</h3>
                <p>No contact inquiries match your current filter</p>
            </div>
        `;
        return;
    }

    container.innerHTML = contacts.map(contact => createContactRow(contact)).join('');
}

// Create individual contact row
function createContactRow(contact) {
    const fullName = `${contact.first_name} ${contact.last_name}`;
    const messagePreview = contact.message.length > 80
        ? contact.message.substring(0, 80) + '...'
        : contact.message;
    const dateFormatted = formatDate(contact.date_submitted);
    const unreadClass = contact.status === 'unread' ? 'unread' : '';

    return `
        <div class="contact-row ${unreadClass}" data-id="${contact.id}">
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
                <div class="contact-status-badge ${contact.status}">
                    ${contact.status}
                </div>
            </div>
            <div class="contact-details">
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">
                            <a href="mailto:${contact.email}">${escapeHtml(contact.email)}</a>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value">
                            <a href="tel:${contact.phone_number}">${escapeHtml(contact.phone_number || 'N/A')}</a>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Full Message</span>
                    <div class="full-message">${escapeHtml(contact.message)}</div>
                </div>
                <div class="details-actions">
                    <button class="btn-action btn-reply" onclick="openReplyModal(${contact.id}, '${escapeHtml(contact.email)}', '${escapeHtml(fullName)}')">
                        <i class="fa-solid fa-reply"></i>
                        Reply
                    </button>
                    ${contact.status === 'unread' ? `
                        <button class="btn-action btn-mark-read" onclick="markAsRead(${contact.id})">
                            <i class="fa-solid fa-check"></i>
                            Mark as Read
                        </button>
                    ` : `
                        <button class="btn-action btn-mark-unread" onclick="markAsUnread(${contact.id})">
                            <i class="fa-solid fa-envelope"></i>
                            Mark as Unread
                        </button>
                    `}
                    <button class="btn-action btn-archive" onclick="archiveContact(${contact.id})">
                        <i class="fa-solid fa-box-archive"></i>
                        Archive
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteContact(${contact.id})">
                        <i class="fa-solid fa-trash"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Toggle contact details
function toggleContactDetails(id) {
    const row = document.querySelector(`.contact-row[data-id="${id}"]`);
    row.classList.toggle('expanded');
}

// Update statistics
function updateStats() {
    const unreadCount = allContacts.filter(c => c.status === 'unread').length;
    document.getElementById('totalContacts').textContent = allContacts.length;
    document.getElementById('unreadCount').textContent = unreadCount;
}

// Mark as read
async function markAsRead(id) {
    try {
        const response = await fetch('api/contact_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', id })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to mark as read');
        }

        // Update local state
        const contact = allContacts.find(c => c.id === id);
        if (contact) contact.status = 'read';

        updateStats();
        filterAndRenderContacts(document.getElementById('searchInput').value);
        showToast('Contact marked as read', 'success');

    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to mark as read: ' + error.message, 'error');
    }
}

// Mark as unread
async function markAsUnread(id) {
    try {
        const response = await fetch('api/contact_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_unread', id })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to mark as unread');
        }

        // Update local state
        const contact = allContacts.find(c => c.id === id);
        if (contact) contact.status = 'unread';

        updateStats();
        filterAndRenderContacts(document.getElementById('searchInput').value);
        showToast('Contact marked as unread', 'success');

    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to mark as unread: ' + error.message, 'error');
    }
}

// Archive contact
async function archiveContact(id) {
    if (!confirm('Are you sure you want to archive this contact?')) return;

    try {
        const response = await fetch('api/contact_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'archive', id })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to archive contact');
        }

        // Remove from local state
        allContacts = allContacts.filter(c => c.id !== id);

        updateStats();
        filterAndRenderContacts(document.getElementById('searchInput').value);
        showToast('Contact archived successfully', 'success');

    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to archive contact: ' + error.message, 'error');
    }
}

// Delete contact
async function deleteContact(id) {
    if (!confirm('Are you sure you want to delete this contact? This action cannot be undone.')) return;

    try {
        const response = await fetch('api/contact_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to delete contact');
        }

        // Remove from local state
        allContacts = allContacts.filter(c => c.id !== id);

        updateStats();
        filterAndRenderContacts(document.getElementById('searchInput').value);
        showToast('Contact deleted successfully', 'success');

    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to delete contact: ' + error.message, 'error');
    }
}

// Open reply modal
function openReplyModal(contactId, email, name) {
    document.getElementById('replyContactId').value = contactId;
    document.getElementById('replyTo').value = email;
    document.getElementById('replySubject').value = `Re: Contact Inquiry from ${name}`;
    document.getElementById('replyMessage').value = '';
    document.getElementById('replyModal').classList.add('active');
}

// Close reply modal
function closeReplyModal() {
    const modal = document.getElementById('replyModal');
    const form = document.getElementById('replyForm');
    const submitButton = form.querySelector('button[type="submit"]');

    modal.classList.remove('active');
    form.reset();

    // Reset submit button state
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Reply';
        submitButton.style.cursor = 'pointer';
        submitButton.style.opacity = '1';
    }
}

// Handle reply form submission
async function handleReplySubmit(e) {
    e.preventDefault();

    // Get the submit button
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalButtonHtml = submitButton.innerHTML;

    // Prevent multiple submissions
    if (submitButton.disabled) {
        return;
    }

    const contactId = document.getElementById('replyContactId').value;
    const to = document.getElementById('replyTo').value;
    const subject = document.getElementById('replySubject').value;
    const message = document.getElementById('replyMessage').value;
    const sendCopy = document.getElementById('includeCopy').checked;

    // Get original message for context
    const contact = allContacts.find(c => c.id == contactId);
    const originalMessage = contact ? contact.message : '';

    try {
        // Disable button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        submitButton.style.cursor = 'not-allowed';
        submitButton.style.opacity = '0.6';

        // Add timeout to fetch request (30 seconds for email sending)
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        let response;
        try {
            response = await fetch('api/send_reply.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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
        } catch (fetchError) {
            clearTimeout(timeoutId);
            if (fetchError.name === 'AbortError') {
                throw new Error('Request timed out. The email might still be sending. Please check your sent emails.');
            }
            throw new Error('Network error: Unable to connect to server. Please check your connection and try again.');
        }

        clearTimeout(timeoutId);

        // Check if response is OK
        if (!response.ok) {
            throw new Error(`Server error (${response.status}): ${response.statusText}`);
        }

        // Get the response text first to debug JSON parsing issues
        const text = await response.text();
        console.log('Reply response:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Raw response:', text);
            throw new Error('Invalid server response. Please check the browser console for details.');
        }

        if (!data.success) {
            throw new Error(data.message || 'Failed to send reply');
        }

        closeReplyModal();
        showToast('Reply sent successfully!', 'success');

        // Mark as read automatically after reply
        await markAsRead(parseInt(contactId));

    } catch (error) {
        console.error('Error sending reply:', error);
        showToast('Failed to send reply: ' + error.message, 'error');

        // Re-enable button on error
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonHtml;
        submitButton.style.cursor = 'pointer';
        submitButton.style.opacity = '1';
    }
}

// Auto-refresh contacts every 30 seconds
function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        loadContacts();
    }, 30000); // 30 seconds
}

// Helper functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
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
    document.getElementById('contactsList').innerHTML = `
        <div class="empty-state">
            <i class="fa-solid fa-exclamation-triangle" style="color: #e74c3c;"></i>
            <h3>Error</h3>
            <p>${message}</p>
        </div>
    `;
}

function showToast(message, type = 'info') {
    // Simple alert for now - can be replaced with a toast library
    alert(message);
}
