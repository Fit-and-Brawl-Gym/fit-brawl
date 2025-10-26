let allMembers = [];
let currentFilter = 'all';

// Load members on page load
document.addEventListener('DOMContentLoaded', function () {
    loadMembers();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Tab clicks
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.plan;
            filterAndRenderMembers();
        });
    });

    // Search input
    document.getElementById('searchInput').addEventListener('input', function (e) {
        filterAndRenderMembers(e.target.value);
    });
}

// Load members from API
async function loadMembers() {
    try {
        const response = await fetch('api/get_members.php');

        // Get raw text first to debug
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
            throw new Error(data.message || 'Failed to load members');
        }

        allMembers = data.members;
        document.getElementById('totalMembers').textContent = allMembers.length;
        renderMembers(allMembers);

    } catch (error) {
        console.error('Error loading members:', error);
        showError('Failed to load members: ' + error.message);
    }
}

// Filter and render members
function filterAndRenderMembers(searchTerm = '') {
    let filtered = allMembers;

    // Filter by plan
    if (currentFilter !== 'all') {
        filtered = filtered.filter(m => m.plan_name === currentFilter);
    }

    // Filter by search term
    if (searchTerm) {
        const search = searchTerm.toLowerCase();
        filtered = filtered.filter(m =>
            m.name.toLowerCase().includes(search)
        );
    }

    renderMembers(filtered);
}

// Render members list
function renderMembers(members) {
    const container = document.getElementById('membersList');

    if (members.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-users-slash"></i>
                <h3>No Members Found</h3>
                <p>No members match your current filter</p>
            </div>
        `;
        return;
    }

    container.innerHTML = members.map(member => createMemberRow(member)).join('');
}

// Create individual member row
function createMemberRow(member) {
    const duration = member.duration ? `${member.duration} days` : 'N/A';
    const email = member.email || 'N/A';
    const totalPayment = member.total_payment ? `₱${parseFloat(member.total_payment).toLocaleString()}` : 'N/A';
    const startDate = formatDate(member.start_date);
    const endDate = formatDate(member.end_date);
    const dateSubmitted = formatDate(member.date_submitted);

    return `
        <div class="member-row" data-id="${member.id}">
            <div class="member-header" onclick="toggleMemberDetails(${member.id})">
                <div class="expand-icon">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
                <div class="member-info">
                    <div class="member-name">${escapeHtml(member.name)}</div>
                    <div class="member-plan">${escapeHtml(member.plan_name)}</div>
                </div>
                <div class="member-duration">${escapeHtml(duration)}</div>
            </div>
            <div class="member-details">
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">
                            <a href="mailto:${email}">${escapeHtml(email)}</a>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Payment</span>
                        <span class="detail-value highlight">${totalPayment}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Start Date</span>
                        <span class="detail-value">${startDate}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">End Date</span>
                        <span class="detail-value">${endDate}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date Submitted</span>
                        <span class="detail-value">${dateSubmitted}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Country</span>
                        <span class="detail-value">${escapeHtml(member.country || 'N/A')}</span>
                    </div>
                </div>
                <div class="details-actions">
                    <button class="btn-history" onclick="viewHistory(${member.user_id}, '${escapeHtml(member.name)}')">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        View History
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Toggle member details
function toggleMemberDetails(id) {
    const row = document.querySelector(`.member-row[data-id="${id}"]`);
    row.classList.toggle('expanded');
}

// View member history
async function viewHistory(userId, userName) {
    try {
        console.log('Loading history for user:', userId, userName);
        
        const response = await fetch(`api/get_member_history.php?user_id=${userId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('History API response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
        }

        if (!data.success) {
            throw new Error(data.message || 'Failed to load history');
        }

        console.log('History loaded:', data.history);
        showHistoryPanel(userName, data.history);

    } catch (error) {
        console.error('Error loading history:', error);
        alert('Failed to load member history: ' + error.message);
    }
}

// Show history panel
function showHistoryPanel(userName, history) {
    const panel = document.getElementById('historyPanel');
    const content = document.getElementById('historyContent');

    document.querySelector('#historyPanel .side-panel-header h2').textContent =
        `${userName}'s Membership History`;

    if (history.length === 0) {
        content.innerHTML = `
            <div class="history-empty">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <p>No membership history found</p>
            </div>
        `;
    } else {
        content.innerHTML = `
            <div class="history-timeline">
                ${history.map(item => createHistoryItem(item)).join('')}
            </div>
        `;
    }

    panel.classList.add('active');
}

// Create history item
function createHistoryItem(item) {
    const payment = item.total_payment ? `₱${parseFloat(item.total_payment).toLocaleString()}` : 'N/A';
    const duration = item.duration ? `${item.duration} days` : 'N/A';
    
    return `
        <div class="history-item">
            <div class="history-header">
                <div class="history-plan">${escapeHtml(item.plan_name)}</div>
                <div class="history-date">${formatDate(item.date_submitted)}</div>
            </div>
            <div class="history-details">
                <div>
                    <label>Duration</label>
                    <span>${escapeHtml(duration)}</span>
                </div>
                <div>
                    <label>Status</label>
                    <span>${escapeHtml(item.membership_status || 'N/A')}</span>
                </div>
                <div>
                    <label>Start Date</label>
                    <span>${formatDate(item.start_date)}</span>
                </div>
                <div>
                    <label>End Date</label>
                    <span>${formatDate(item.end_date)}</span>
                </div>
            </div>
            <div class="history-payment">
                Total Payment: ${payment}
            </div>
        </div>
    `;
}

// Close history panel
function closeHistoryPanel() {
    document.getElementById('historyPanel').classList.remove('active');
}

// Helper functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    document.getElementById('membersList').innerHTML = `
        <div class="empty-state">
            <i class="fa-solid fa-exclamation-triangle" style="color: #e74c3c;"></i>
            <h3>Error</h3>
            <p>${message}</p>
        </div>
    `;
}
