document.addEventListener('DOMContentLoaded', function () {
    loadFeedback();
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

        // Render feedback cards
        renderFeedback(filteredFeedbacks);

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
    const initials = getInitials(username);
    const id = parseInt(feedback.id); // Ensure it's a number

    console.log('Creating card - ID:', id, 'Type:', typeof id, 'Is Visible:', isVisible);

    return `
        <div class="feedback-card ${isVisible ? 'visible' : 'hidden'}" data-id="${id}">
            <div class="feedback-card-header">
                <div class="feedback-user">
                    <div class="user-avatar">${initials}</div>
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
