/**
 * Active Memberships Management System
 * Uses HashTable for O(1) membership lookups
 */

// Global State
let allMemberships = [];
let filteredMemberships = [];
let membershipHashTable = new Map();

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadMemberships();
});

/**
 * Initialize all event listeners
 */
function initializeEventListeners() {
    // Add Membership Modal
    document.getElementById('addMembershipBtn').addEventListener('click', openAddMembershipModal);
    document.getElementById('closeAddMembershipModal').addEventListener('click', closeAddMembershipModal);
    document.getElementById('cancelAddMembership').addEventListener('click', closeAddMembershipModal);
    document.getElementById('addMembershipForm').addEventListener('submit', handleAddMembership);

    // Payment History Modal
    document.getElementById('closePaymentHistoryModal').addEventListener('click', closePaymentHistoryModal);

    // Details Panel
    document.getElementById('closeDetailsPanel').addEventListener('click', closeDetailsPanel);

    // Filters
    document.getElementById('billingTypeFilter').addEventListener('change', applyFilters);
    document.getElementById('expirationFilter').addEventListener('change', applyFilters);
    document.getElementById('paymentStatusFilter').addEventListener('change', applyFilters);
    document.getElementById('searchFilter').addEventListener('input', applyFilters);
    document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
    document.getElementById('showExpiredToggle').addEventListener('change', toggleExpired);

    // Close modals on outside click
    window.addEventListener('click', function(event) {
        const addModal = document.getElementById('addMembershipModal');
        const historyModal = document.getElementById('paymentHistoryModal');
        
        if (event.target === addModal) {
            closeAddMembershipModal();
        }
        if (event.target === historyModal) {
            closePaymentHistoryModal();
        }
    });
}

/**
 * Load memberships from API
 */
async function loadMemberships() {
    try {
        const showExpired = document.getElementById('showExpiredToggle').checked;
        const response = await fetch(`${PUBLIC_PATH}/php/admin/api/active_memberships_api.php?action=getMemberships&include_expired=${showExpired}`);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('API Error Response:', errorText);
            throw new Error('Failed to fetch memberships: ' + response.status);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        
        if (data.success) {
            allMemberships = data.data;
            buildHashTable();
            applyFilters();
            updateStatCards(data.stats);
        } else {
            showError('Failed to load memberships: ' + data.message);
        }
    } catch (error) {
        console.error('Error loading memberships:', error);
        showError('Error loading memberships. Please try again.');
    }
}

/**
 * Build HashTable for O(1) lookups
 */
function buildHashTable() {
    membershipHashTable.clear();
    allMemberships.forEach(membership => {
        membershipHashTable.set(membership.id, membership);
    });
}

/**
 * Apply all active filters
 */
function applyFilters() {
    const billingType = document.getElementById('billingTypeFilter').value;
    const expiration = document.getElementById('expirationFilter').value;
    const paymentStatus = document.getElementById('paymentStatusFilter').value;
    const searchTerm = document.getElementById('searchFilter').value.toLowerCase();

    filteredMemberships = allMemberships.filter(membership => {
        // Billing Type Filter
        if (billingType && membership.billing_type !== billingType) return false;

        // Expiration Filter
        if (expiration) {
            const daysRemaining = calculateDaysRemaining(membership.end_date);
            if (expiration === 'expiring_soon' && (daysRemaining < 0 || daysRemaining > 7)) return false;
            if (expiration === 'this_month' && (daysRemaining < 0 || daysRemaining > 30)) return false;
        }

        // Payment Status Filter
        if (paymentStatus) {
            if (membership.payment_method === 'cash') {
                if (paymentStatus === 'paid' && membership.cash_payment_status !== 'paid') return false;
                if (paymentStatus === 'unpaid' && membership.cash_payment_status !== 'unpaid') return false;
            } else {
                // Online payments are automatically paid
                if (paymentStatus === 'unpaid') return false;
            }
        }

        // Search Filter
        if (searchTerm) {
            const searchableText = `${membership.name} ${membership.email} ${membership.contact_number}`.toLowerCase();
            if (!searchableText.includes(searchTerm)) return false;
        }

        return true;
    });

    renderMembershipsTable();
}

/**
 * Clear all filters
 */
function clearFilters() {
    document.getElementById('billingTypeFilter').value = '';
    document.getElementById('expirationFilter').value = '';
    document.getElementById('paymentStatusFilter').value = '';
    document.getElementById('searchFilter').value = '';
    applyFilters();
}

/**
 * Toggle expired memberships
 */
async function toggleExpired() {
    await loadMemberships();
}

/**
 * Render memberships table
 */
function renderMembershipsTable() {
    const tbody = document.getElementById('membershipsTableBody');
    
    // Update counts
    document.getElementById('showingCount').textContent = filteredMemberships.length;
    document.getElementById('totalCount').textContent = allMemberships.length;

    if (filteredMemberships.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">
                    <i class="fas fa-inbox"></i> No memberships found
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filteredMemberships.map(membership => createMembershipRow(membership)).join('');
}

/**
 * Create membership table row
 */
function createMembershipRow(membership) {
    const daysRemaining = calculateDaysRemaining(membership.end_date);
    const statusBadge = getStatusBadge(membership, daysRemaining);
    const daysRemainingClass = getDaysRemainingClass(daysRemaining);
    const paymentBadge = getPaymentBadge(membership);
    const avatar = membership.profile_image 
        ? `<img src="${IMAGES_PATH}/${membership.profile_image}" alt="${membership.name}" class="user-avatar">`
        : `<img src="${IMAGES_PATH}/account-icon.svg" alt="${membership.name}" class="user-avatar">`;

    return `
        <tr>
            <td>
                <div class="user-cell">
                    ${avatar}
                    <div class="user-info">
                        <div class="user-name">${escapeHtml(membership.name)}</div>
                        <div class="user-id">ID: ${membership.user_id}</div>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(membership.email)}</td>
            <td>${escapeHtml(membership.contact_number || 'N/A')}</td>
            <td>
                <strong>${escapeHtml(membership.plan_name)}</strong><br>
                <small>${membership.billing_type === 'monthly' ? 'Monthly' : 'Quarterly'}</small>
            </td>
            <td>${formatDate(membership.start_date)}</td>
            <td>${formatDate(membership.end_date)}</td>
            <td class="days-remaining ${daysRemainingClass}">
                ${daysRemaining >= 0 ? daysRemaining + ' days' : 'Expired'}
            </td>
            <td>
                ${statusBadge}
                ${paymentBadge}
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action btn-view" onclick="viewMembershipDetails(${membership.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action btn-history" onclick="viewPaymentHistory(${membership.id})" title="Payment History">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

/**
 * Calculate days remaining until expiration
 */
function calculateDaysRemaining(endDate) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const end = new Date(endDate);
    end.setHours(0, 0, 0, 0);
    const diffTime = end - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

/**
 * Check if membership is in grace period (3 days after expiration)
 */
function isInGracePeriod(endDate) {
    const daysRemaining = calculateDaysRemaining(endDate);
    return daysRemaining >= -3 && daysRemaining < 0;
}

/**
 * Get status badge HTML
 */
function getStatusBadge(membership, daysRemaining) {
    // Check actual days remaining first (more accurate than DB status)
    if (daysRemaining < 0 && !isInGracePeriod(membership.end_date)) {
        return '<span class="badge badge-expired"><i class="fas fa-times-circle"></i> Expired</span>';
    }
    
    if (isInGracePeriod(membership.end_date)) {
        return '<span class="badge badge-grace-period"><i class="fas fa-exclamation-triangle"></i> Grace Period</span>';
    }
    
    if (daysRemaining <= 7 && daysRemaining >= 0) {
        return '<span class="badge badge-expiring-soon"><i class="fas fa-clock"></i> Expiring Soon</span>';
    }
    
    return '<span class="badge badge-active"><i class="fas fa-check-circle"></i> Active</span>';
}

/**
 * Get days remaining color class
 */
function getDaysRemainingClass(daysRemaining) {
    if (daysRemaining < 0) return 'critical';
    if (daysRemaining <= 7) return 'warning';
    return 'normal';
}

/**
 * Get payment status badge
 */
function getPaymentBadge(membership) {
    if (membership.payment_method === 'cash') {
        if (membership.cash_payment_status === 'paid') {
            return '<span class="badge badge-paid"><i class="fas fa-money-bill"></i> Paid (Cash)</span>';
        } else {
            return '<span class="badge badge-unpaid"><i class="fas fa-money-bill"></i> Unpaid (Cash)</span>';
        }
    }
    return '<span class="badge badge-paid"><i class="fas fa-credit-card"></i> Paid (Online)</span>';
}

/**
 * Update stat cards
 */
function updateStatCards(stats) {
    document.getElementById('totalActive').textContent = stats.total_active || 0;
    document.getElementById('expiringSoon').textContent = stats.expiring_soon || 0;
    document.getElementById('newThisMonth').textContent = stats.new_this_month || 0;
    document.getElementById('revenueThisMonth').textContent = '₱' + (stats.revenue_this_month || 0).toLocaleString();
    document.getElementById('renewalRate').textContent = (stats.renewal_rate || 0) + '%';
}

/**
 * View membership details
 */
async function viewMembershipDetails(membershipId) {
    const panel = document.getElementById('detailsPanel');
    const content = document.getElementById('detailsContent');
    
    panel.classList.add('active');
    content.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i> Loading details...</div>';

    try {
        const response = await fetch(`${PUBLIC_PATH}/php/admin/api/active_memberships_api.php?action=getMembershipDetails&id=${membershipId}`);
        
        if (!response.ok) throw new Error('Failed to fetch details');
        
        const data = await response.json();
        
        if (data.success) {
            renderMembershipDetails(data.data);
        } else {
            content.innerHTML = '<div class="empty-state">Failed to load details</div>';
        }
    } catch (error) {
        console.error('Error loading details:', error);
        content.innerHTML = '<div class="empty-state">Error loading details</div>';
    }
}

/**
 * Render membership details in panel
 */
function renderMembershipDetails(data) {
    const content = document.getElementById('detailsContent');
    const membership = data.membership;
    const user = data.user;
    const daysRemaining = calculateDaysRemaining(membership.end_date);
    
    content.innerHTML = `
        <div class="detail-section">
            <h4>Member Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${escapeHtml(user.name)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${escapeHtml(user.email)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Contact:</span>
                    <span class="detail-value">${escapeHtml(user.contact_number || 'N/A')}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value">${escapeHtml(user.role)}</span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h4>Membership Details</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Plan:</span>
                    <span class="detail-value">${escapeHtml(membership.plan_name)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Billing:</span>
                    <span class="detail-value">${membership.billing_type === 'monthly' ? 'Monthly' : 'Quarterly'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Start Date:</span>
                    <span class="detail-value">${formatDate(membership.start_date)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">End Date:</span>
                    <span class="detail-value">${formatDate(membership.end_date)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Days Remaining:</span>
                    <span class="detail-value ${getDaysRemainingClass(daysRemaining)}">${daysRemaining >= 0 ? daysRemaining + ' days' : 'Expired'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">${getStatusBadge(membership, daysRemaining)}</span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h4>Payment Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Method:</span>
                    <span class="detail-value">${membership.payment_method === 'cash' ? 'Cash' : 'Online'}</span>
                </div>
                ${membership.payment_method === 'cash' ? `
                    <div class="detail-item">
                        <span class="detail-label">Payment Status:</span>
                        <span class="detail-value">${getPaymentBadge(membership)}</span>
                    </div>
                    ${membership.cash_payment_date ? `
                        <div class="detail-item">
                            <span class="detail-label">Payment Date:</span>
                            <span class="detail-value">${formatDate(membership.cash_payment_date)}</span>
                        </div>
                    ` : ''}
                ` : ''}
            </div>
        </div>

        ${membership.remarks ? `
            <div class="detail-section">
                <h4>Notes</h4>
                <p class="detail-notes">${escapeHtml(membership.remarks)}</p>
            </div>
        ` : ''}

        <style>
            .detail-section {
                margin-bottom: 24px;
            }
            .detail-section h4 {
                font-size: 16px;
                font-weight: 600;
                color: #222;
                margin-bottom: 12px;
                border-bottom: 2px solid #f0f0f0;
                padding-bottom: 8px;
            }
            .detail-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .detail-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .detail-label {
                font-size: 14px;
                color: #666;
                font-weight: 500;
            }
            .detail-value {
                font-size: 14px;
                color: #222;
                font-weight: 600;
            }
            .detail-notes {
                font-size: 14px;
                color: #444;
                line-height: 1.6;
                padding: 12px;
                background: #f7f8fa;
                border-radius: 8px;
            }
        </style>
    `;
}

/**
 * Close details panel
 */
function closeDetailsPanel() {
    document.getElementById('detailsPanel').classList.remove('active');
}

/**
 * View payment history
 */
async function viewPaymentHistory(membershipId) {
    const modal = document.getElementById('paymentHistoryModal');
    const tbody = document.getElementById('paymentHistoryTableBody');
    
    modal.classList.add('active');
    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><i class="fas fa-spinner fa-spin"></i> Loading payment history...</td></tr>';

    try {
        const response = await fetch(`${PUBLIC_PATH}/php/admin/api/active_memberships_api.php?action=getPaymentHistory&id=${membershipId}`);
        
        if (!response.ok) throw new Error('Failed to fetch payment history');
        
        const data = await response.json();
        
        if (data.success) {
            renderPaymentHistory(data.data);
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No payment history found</td></tr>';
        }
    } catch (error) {
        console.error('Error loading payment history:', error);
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">Error loading payment history</td></tr>';
    }
}

/**
 * Render payment history table
 */
function renderPaymentHistory(payments) {
    const tbody = document.getElementById('paymentHistoryTableBody');
    
    if (payments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No payment records found</td></tr>';
        return;
    }

    tbody.innerHTML = payments.map(payment => `
        <tr>
            <td>${formatDate(payment.payment_date)}</td>
            <td>₱${parseFloat(payment.amount).toLocaleString()}</td>
            <td>${payment.payment_method === 'cash' ? 'Cash' : 'Online'}</td>
            <td>${payment.payment_method === 'cash' ? (payment.cash_payment_status === 'paid' ? '<span class="badge badge-paid">Paid</span>' : '<span class="badge badge-unpaid">Unpaid</span>') : '<span class="badge badge-paid">Paid</span>'}</td>
            <td>${escapeHtml(payment.reference_number || 'N/A')}</td>
        </tr>
    `).join('');
}

/**
 * Close payment history modal
 */
function closePaymentHistoryModal() {
    document.getElementById('paymentHistoryModal').classList.remove('active');
}

/**
 * Open add membership modal
 */
function openAddMembershipModal() {
    document.getElementById('addMembershipModal').classList.add('active');
    // Set default start date to today
    document.getElementById('startDate').value = new Date().toISOString().split('T')[0];
}

/**
 * Close add membership modal
 */
function closeAddMembershipModal() {
    document.getElementById('addMembershipModal').classList.remove('active');
    document.getElementById('addMembershipForm').reset();
}

/**
 * Handle add membership form submission
 */
async function handleAddMembership(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append('action', 'createMembershipWithAccount');

    try {
        const response = await fetch(`${PUBLIC_PATH}/php/admin/api/active_memberships_api.php`, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error('Failed to create membership');

        const data = await response.json();

        if (data.success) {
            showSuccess('Membership created successfully! Password has been sent to the member\'s email.');
            closeAddMembershipModal();
            loadMemberships();
        } else {
            showError('Failed to create membership: ' + data.message);
        }
    } catch (error) {
        console.error('Error creating membership:', error);
        showError('Error creating membership. Please try again.');
    }
}

/**
 * Utility Functions
 */

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getInitials(name) {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
}

function showSuccess(message) {
    alert(message); // Replace with a better notification system if available
}

function showError(message) {
    alert(message); // Replace with a better notification system if available
}
