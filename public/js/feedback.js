// Feedback page with filtering and voting system
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('feedback-section');
    const searchInput = document.getElementById('searchInput');
    const planFilter = document.getElementById('planFilter');
    const sortFilter = document.getElementById('sortFilter');

    let feedbackData = [];
    let searchTimeout = null;

    // Load feedback on page load
    loadFeedback();

    // Event listeners for filters
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadFeedback();
        }, 500); // Debounce search
    });

    planFilter.addEventListener('change', loadFeedback);
    sortFilter.addEventListener('change', loadFeedback);

    // Load feedback function
    function loadFeedback() {
        const plan = planFilter.value;
        const sort = sortFilter.value;
        const search = searchInput.value.trim();

        // Build query string
        const params = new URLSearchParams({
            api: 'true',
            plan: plan,
            sort: sort,
            search: search
        });

        fetch(`feedback.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                feedbackData = data;
                renderFeedback(data);
            })
            .catch(error => {
                console.error('Error loading feedback:', error);
                showError('Failed to load feedback. Please try again later.');
            });
    }

    // Render feedback
    function renderFeedback(data) {
        if (data.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>No Feedback Found</h3>
                    <p>No feedback matches your search criteria.<br>Try adjusting your filters.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = data.map((item, index) => {
            const cardClass = index % 2 === 0 ? 'left' : 'right';
            let avatar, isDefaultIcon = false;

            if (!item.avatar || item.avatar === '' || item.avatar.includes('account-icon.svg') || item.avatar.includes('profile-icon.svg')) {
                avatar = item.avatar && item.avatar.includes('account-icon.svg')
                    ? item.avatar
                    : '../../images/account-icon.svg';
                isDefaultIcon = true;
            } else if (item.avatar.startsWith('../') || item.avatar.startsWith('../../')) {
                avatar = item.avatar;
                isDefaultIcon = item.avatar.includes('account-icon.svg') || item.avatar.includes('profile-icon.svg');
            } else {
                avatar = `../../uploads/avatars/${item.avatar}`;
                isDefaultIcon = false;
            }

            const defaultIconClass = isDefaultIcon ? 'default-icon' : '';

            // Format date
            const date = new Date(item.date);
            const formattedDate = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            // Voting buttons
            const helpfulCount = item.helpful_count || 0;
            const notHelpfulCount = item.not_helpful_count || 0;
            const userVote = item.user_vote || null;

            const votingHTML = `
                <div class="feedback-meta">
                    <div class="feedback-plan">
                        <i class="fas fa-crown"></i>
                        <span>${item.plan_name}</span>
                    </div>
                    <div class="vote-actions">
                        <button class="vote-btn ${userVote === 'helpful' ? 'active' : ''}"
                                data-feedback-id="${item.id}"
                                data-vote-type="helpful"
                                title="Mark as helpful">
                            <i class="fas fa-thumbs-up"></i>
                            Helpful
                            <span class="vote-count">${helpfulCount}</span>
                        </button>
                        <button class="vote-btn ${userVote === 'not_helpful' ? 'active' : ''}"
                                data-feedback-id="${item.id}"
                                data-vote-type="not_helpful"
                                title="Mark as not helpful">
                            <i class="fas fa-thumbs-down"></i>
                            <span class="vote-count">${notHelpfulCount}</span>
                        </button>
                    </div>
                </div>
            `;

            return `
                <div class="feedback-card">
                    <div class="bubble">
                        <div class="bubble-header">
                            <img src="${avatar}" alt="${item.username}" class="${defaultIconClass}" onerror="this.onerror=null; this.src='../../images/account-icon.svg'; this.classList.add('default-icon');">
                            <div>
                                <h3>${item.username}</h3>
                                <span class="feedback-date">${formattedDate}</span>
                            </div>
                        </div>
                        <p>${item.message}</p>
                        ${votingHTML}
                    </div>
                </div>
            `;
        }).join('');

        // Add event listeners to vote buttons
        attachVoteListeners();
    }

    // Attach vote listeners
    function attachVoteListeners() {
        const voteButtons = container.querySelectorAll('.vote-btn');

        voteButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const feedbackId = this.getAttribute('data-feedback-id');
                const voteType = this.getAttribute('data-vote-type');
                const isActive = this.classList.contains('active');

                // If already voted this way, remove vote
                const finalVoteType = isActive ? 'remove' : voteType;

                handleVote(feedbackId, finalVoteType, this);
            });
        });
    }

    // Handle voting
    function handleVote(feedbackId, voteType, button) {
        // Disable button during request
        const allButtons = container.querySelectorAll(`[data-feedback-id="${feedbackId}"]`);
        allButtons.forEach(btn => btn.disabled = true);

        fetch('api/feedback_vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                feedback_id: feedbackId,
                vote_type: voteType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                updateVoteUI(feedbackId, data);
            } else {
                alert(data.message || 'Failed to record vote');
            }

            // Re-enable buttons
            allButtons.forEach(btn => btn.disabled = false);
        })
        .catch(error => {
            console.error('Error voting:', error);
            alert('An error occurred. Please try again.');
            allButtons.forEach(btn => btn.disabled = false);
        });
    }

    // Update vote UI
    function updateVoteUI(feedbackId, data) {
        const buttons = container.querySelectorAll(`[data-feedback-id="${feedbackId}"]`);

        buttons.forEach(btn => {
            const btnType = btn.getAttribute('data-vote-type');
            const countSpan = btn.querySelector('.vote-count');

            // Remove active class from all
            btn.classList.remove('active');

            // Update counts
            if (btnType === 'helpful') {
                countSpan.textContent = data.helpful_count || 0;
                if (data.user_vote === 'helpful') {
                    btn.classList.add('active');
                }
            } else if (btnType === 'not_helpful') {
                countSpan.textContent = data.not_helpful_count || 0;
                if (data.user_vote === 'not_helpful') {
                    btn.classList.add('active');
                }
            }
        });
    }

    // Show error
    function showError(message) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>Error</h3>
                <p>${message}</p>
            </div>
        `;
    }

    // Back to top button functionality
    const backToTopBtn = document.querySelector('.back-to-top');

    if (backToTopBtn) {
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        // Scroll to top when clicked
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ==========================
    // Feedback Modal Functionality
    // ==========================

    const feedbackModal = document.getElementById('feedbackModal');
    const openModalBtn = document.getElementById('openFeedbackModal');
    const closeModalBtn = document.getElementById('closeFeedbackModal');
    const cancelBtn = document.getElementById('cancelFeedback');
    const feedbackForm = document.getElementById('feedbackSubmitForm');
    const messageTextarea = document.getElementById('feedbackMessage');
    const charCountSpan = document.getElementById('charCount');
    const submitBtn = document.getElementById('submitFeedback');
    const successModal = document.getElementById('successModal');
    const closeSuccessBtn = document.getElementById('closeSuccessModal');

    // Open modal
    if (openModalBtn) {
        openModalBtn.addEventListener('click', function() {
            feedbackModal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scroll
            messageTextarea.focus();
        });
    }

    // Close modal function
    function closeFeedbackModal() {
        feedbackModal.classList.remove('active');
        document.body.style.overflow = ''; // Restore scroll
        feedbackForm.reset();
        if (charCountSpan) charCountSpan.textContent = '0';
    }

    // Close modal events
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeFeedbackModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeFeedbackModal);
    }

    // Close on outside click
    feedbackModal.addEventListener('click', function(e) {
        if (e.target === feedbackModal) {
            closeFeedbackModal();
        }
    });

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && feedbackModal.classList.contains('active')) {
            closeFeedbackModal();
        }
    });

    // Character counter
    if (messageTextarea && charCountSpan) {
        messageTextarea.addEventListener('input', function() {
            const count = this.value.length;
            charCountSpan.textContent = count;

            // Visual feedback for max length
            if (count > 900) {
                charCountSpan.style.color = '#ff6b6b';
            } else {
                charCountSpan.style.color = '';
            }
        });
    }

    // Form submission
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(feedbackForm);
            const data = {
                message: formData.get('message'),
                name: formData.get('name') || '',
                email: formData.get('email') || ''
            };

            // Validate message
            if (!data.message || data.message.trim().length === 0) {
                alert('Please enter your feedback message.');
                return;
            }

            if (data.message.length > 1000) {
                alert('Message exceeds maximum length of 1000 characters.');
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            // Submit feedback
            fetch('api/submit_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    // Close feedback modal
                    closeFeedbackModal();

                    // Show success modal
                    showSuccessModal();

                    // Reload feedback to show new submission
                    setTimeout(() => {
                        loadFeedback();
                    }, 500);
                } else {
                    throw new Error(result.message || 'Failed to submit feedback');
                }
            })
            .catch(error => {
                console.error('Error submitting feedback:', error);
                alert(error.message || 'An error occurred while submitting your feedback. Please try again.');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Feedback';
            });
        });
    }

    // Show success modal
    function showSuccessModal() {
        successModal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Auto close after 3 seconds
        setTimeout(() => {
            closeSuccessModal();
        }, 3000);
    }

    // Close success modal
    function closeSuccessModal() {
        successModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (closeSuccessBtn) {
        closeSuccessBtn.addEventListener('click', closeSuccessModal);
    }

    // Close success modal on outside click
    successModal.addEventListener('click', function(e) {
        if (e.target === successModal) {
            closeSuccessModal();
        }
    });
});
