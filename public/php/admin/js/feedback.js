document.addEventListener('DOMContentLoaded', function () {
    // Load feedback with default filter
    const currentFilter = document.getElementById('dateFilter').value;
    loadFeedback(currentFilter);
});

// Load feedback from database
async function loadFeedback(filter = 'all') {
    try {
        // Use admin-specific endpoint
        const response = await fetch('api/get_feedback.php');

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const feedbacks = await response.json();

        console.log('=== FEEDBACK DATA LOADED ===');
        console.log('Total feedbacks:', feedbacks.length);
        if (feedbacks.length > 0) {
            console.log('First feedback ID:', feedbacks[0].id);
            console.log('Sample feedback:', feedbacks[0]);
        }

        if (!Array.isArray(feedbacks)) {
            console.error('Invalid response format:', feedbacks);
            showError('Invalid data format received');
            return;
        }

        // Filter by date if needed
        const filteredFeedbacks = filterByDate(feedbacks, filter);

        // Update stats
        updateStats(feedbacks);

        // Check which view is active and render accordingly
        const tableView = document.getElementById('tableView');
        const cardsView = document.getElementById('cardsView');
        
        if (tableView && tableView.classList.contains('active')) {
            // Render table view
            renderFeedbackTable(filteredFeedbacks);
        } else if (cardsView && cardsView.classList.contains('active')) {
            // Render card view
            renderFeedback(filteredFeedbacks);
        } else {
            // Default to card view
            renderFeedback(filteredFeedbacks);
        }

    } catch (error) {
        console.error('Error loading feedback:', error);
        showError('Failed to load feedback: ' + error.message);
    }
}

// Filter feedback by date
function filterByDate(feedbacks, filter) {
    if (filter === 'all') return feedbacks;

    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    return feedbacks.filter(feedback => {
        const feedbackDate = new Date(feedback.date || feedback.created_at);

        switch (filter) {
            case 'today':
                return feedbackDate >= today;
            case 'week':
                const weekAgo = new Date(today);
                weekAgo.setDate(weekAgo.getDate() - 7);
                return feedbackDate >= weekAgo;
            case 'month':
                const monthAgo = new Date(today);
                monthAgo.setMonth(monthAgo.getMonth() - 1);
                return feedbackDate >= monthAgo;
            case 'year':
                const yearAgo = new Date(today);
                yearAgo.setFullYear(yearAgo.getFullYear() - 1);
                return feedbackDate >= yearAgo;
            default:
                return true;
        }
    });
}

// Toggle read more/less
function toggleReadMore(id) {
    const messageEl = document.getElementById(`message-${id}`);
    const btn = event.target;

    if (messageEl.classList.contains('expanded')) {
        messageEl.classList.remove('expanded');
        btn.textContent = 'Read more';
    } else {
        messageEl.classList.add('expanded');
        btn.textContent = 'Read less';
    }
}

// Toggle visibility
async function toggleVisibility(id, show) {
    console.log('=== TOGGLE VISIBILITY ===');
    console.log('ID:', id);
    console.log('Show:', show);

    const requestBody = {
        action: 'toggle_visibility',
        id: id,
        is_visible: show ? 1 : 0
    };

    try {
        const response = await fetch('api/feedback_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });

        const result = await response.json();
        console.log('Toggle result:', result);

        if (result.success) {
            console.log('✓ Success! Reloading feedback...');
            // Reload feedback with current filter
            const currentFilter = document.getElementById('dateFilter').value;
            await loadFeedback(currentFilter);
        } else {
            console.error('✗ Failed:', result.message);
            alert('Failed to update visibility: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('✗ Error occurred:', error);
        alert('An error occurred: ' + error.message);
    }
}

// Delete feedback
async function deleteFeedback(id) {
    if (!confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) {
        return;
    }

    console.log('=== DELETING FEEDBACK ===');
    console.log('ID:', id);

    try {
        const response = await fetch('api/feedback_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                id: id
            })
        });

        const result = await response.json();
        console.log('Delete result:', result);

        if (result.success) {
            console.log('✓ Success! Reloading feedback...');
            // Reload feedback with current filter
            const currentFilter = document.getElementById('dateFilter').value;
            await loadFeedback(currentFilter);
        } else {
            console.error('✗ Failed:', result.message);
            alert('Failed to delete feedback: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('✗ Error occurred:', error);
        alert('An error occurred: ' + error.message);
    }
}

// Update statistics
function updateStats(feedbacks) {
    const total = feedbacks.length;
    const visible = feedbacks.filter(f => f.is_visible == 1 || f.is_visible === '1').length;
    const hidden = total - visible;

    console.log('=== UPDATING STATS ===');
    console.log('Total:', total, 'Visible:', visible, 'Hidden:', hidden);

    document.getElementById('totalCount').textContent = total;
    document.getElementById('visibleCount').textContent = visible;
    document.getElementById('hiddenCount').textContent = hidden;
}

// Render feedback cards
function renderFeedback(feedbacks) {
    const grid = document.getElementById('feedbackGrid');

    if (!feedbacks || feedbacks.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-comments"></i>
                <h3>No Feedback Yet</h3>
                <p>Member feedback will appear here</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = feedbacks.map(feedback => createFeedbackCard(feedback)).join('');
}

// Create individual feedback card
function createFeedbackCard(feedback) {
    const isVisible = feedback.is_visible == 1 || feedback.is_visible === '1';
    const username = feedback.username || 'Anonymous';
    const message = feedback.message || '';
    const date = formatDate(feedback.date || feedback.created_at);
    const id = parseInt(feedback.id); // Ensure it's a number

    console.log('Creating card - ID:', id, 'Type:', typeof id, 'Is Visible:', isVisible);

    return `
        <div class="feedback-card ${isVisible ? 'visible' : 'hidden'}" data-id="${id}">
            <div class="feedback-card-header">
                <div class="feedback-user">
                    <div class="user-avatar">
                        <img src="../../../images/account-icon.svg" alt="${escapeHtml(username)}" class="account-icon default-icon">
                    </div>
                    <div class="user-info">
                        <h3>${escapeHtml(username)}</h3>
                        <div class="feedback-date">
                            <i class="fa-solid fa-clock"></i>
                            ${date}
                        </div>
                    </div>
                </div>
                <span class="visibility-badge ${isVisible ? 'visible' : 'hidden'}">
                    ${isVisible ? 'Visible' : 'Hidden'}
                </span>
            </div>
            <div class="feedback-message" id="message-${id}">
                ${escapeHtml(message)}
            </div>
            ${message.length > 200 ? `
                <button class="read-more-btn" onclick="toggleReadMore(${id})">
                    Read more
                </button>
            ` : ''}
            <div class="feedback-actions">
                ${isVisible ? `
                    <button class="action-btn btn-hide" onclick="toggleVisibility(${id}, false)">
                        <i class="fa-solid fa-eye-slash"></i> Hide
                    </button>
                ` : `
                    <button class="action-btn btn-show" onclick="toggleVisibility(${id}, true)">
                        <i class="fa-solid fa-eye"></i> Show
                    </button>
                `}
                <button class="action-btn btn-delete" onclick="deleteFeedback(${id})">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </div>
        </div>
    `;
}

// Date filter change event
document.addEventListener('DOMContentLoaded', function () {
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
        dateFilter.addEventListener('change', function () {
            loadFeedback(this.value);
        });
    }
});

// Helper functions
function getInitials(name) {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;

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
    const grid = document.getElementById('feedbackGrid');
    grid.innerHTML = `
        <div class="empty-state">
            <i class="fa-solid fa-exclamation-triangle" style="color: #e74c3c;"></i>
            <h3>Error</h3>
            <p>${message}</p>
        </div>
    `;
}

// Render feedback table
function renderFeedbackTable(feedbacks) {
    const tbody = document.getElementById('feedbackTableBody');

    if (!feedbacks || feedbacks.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                    <i class="fa-solid fa-comments" style="font-size: 48px; margin-bottom: 12px; display: block;"></i>
                    No Feedback Yet
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = feedbacks.map(feedback => `
        <tr>
            <td><strong>${escapeHtml(feedback.name || 'Anonymous')}</strong></td>
            <td class="table-message" title="${escapeHtml(feedback.message || 'No message')}">${escapeHtml(feedback.message || 'No message')}</td>
            <td>
                <span class="table-rating">${'⭐'.repeat(feedback.rating || 0)}</span>
                <span style="color: #999; font-size: 12px;">(${feedback.rating || 0}/5)</span>
            </td>
            <td style="color: #999; font-size: 13px;">${formatDate(feedback.date || feedback.created_at)}</td>
            <td>
                <span class="visibility-badge ${feedback.is_visible == 1 ? 'visible' : 'hidden'}">
                    <i class="fa-solid fa-${feedback.is_visible == 1 ? 'eye' : 'eye-slash'}"></i>
                    ${feedback.is_visible == 1 ? 'Visible' : 'Hidden'}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    ${feedback.is_visible == 1
                        ? `<button class="btn-secondary btn-small" onclick="toggleVisibility(${feedback.id}, false)">
                            <i class="fa-solid fa-eye-slash"></i> Hide
                           </button>`
                        : `<button class="btn-primary btn-small" onclick="toggleVisibility(${feedback.id}, true)">
                            <i class="fa-solid fa-eye"></i> Show
                           </button>`
                    }
                    <button class="btn-danger btn-small" onclick="deleteFeedback(${feedback.id})">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// View Toggle (Table vs Cards)
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const view = btn.dataset.view;

        // Update active button
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Toggle views
        const tableView = document.getElementById('tableView');
        const cardsView = document.getElementById('cardsView');

        if (view === 'table') {
            tableView.classList.add('active');
            cardsView.classList.remove('active');

            // Re-render table with current filter
            const currentFilter = document.getElementById('dateFilter').value;
            fetch('api/get_feedback.php')
                .then(res => res.json())
                .then(feedbacks => {
                    const filtered = filterByDate(feedbacks, currentFilter);
                    renderFeedbackTable(filtered);
                });
        } else {
            tableView.classList.remove('active');
            cardsView.classList.add('active');

            // Re-render cards with current filter
            const currentFilter = document.getElementById('dateFilter').value;
            fetch('api/get_feedback.php')
                .then(res => res.json())
                .then(feedbacks => {
                    const filtered = filterByDate(feedbacks, currentFilter);
                    updateStats(feedbacks);
                    renderFeedback(filtered);
                });
        }
    });
});

// Update date filter to also update table view
document.getElementById('dateFilter').addEventListener('change', function() {
    loadFeedback(this.value);

    // Also update table if in table view
    const tableView = document.getElementById('tableView');
    if (tableView.classList.contains('active')) {
        fetch('api/get_feedback.php')
            .then(res => res.json())
            .then(feedbacks => {
                const filtered = filterByDate(feedbacks, this.value);
                renderFeedbackTable(filtered);
            });
    }
});
