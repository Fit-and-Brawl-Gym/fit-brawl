/**
 * Resend Verification Email Handler
 * Limits resends to 3 attempts with countdown timer
 */

document.addEventListener('DOMContentLoaded', function() {
    const resendBtn = document.getElementById('resendVerificationBtn');
    const MAX_RESENDS = 3;

    // Get resend count from sessionStorage
    let resendCount = parseInt(sessionStorage.getItem('resendCount') || '0');

    if (resendBtn) {
        // Update button state based on resend count
        updateButtonState();

        resendBtn.addEventListener('click', async function() {
            // Check if limit reached
            if (resendCount >= MAX_RESENDS) {
                showLimitModal();
                return;
            }

            const email = this.getAttribute('data-email');
            const originalText = this.innerHTML;

            if (!email) {
                showToast('Error: No email found', 'error');
                return;
            }

            // Disable button and show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            try {
                const response = await fetch('resend-verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email })
                });

                const data = await response.json();

                if (data.success) {
                    // Increment resend count
                    resendCount++;
                    sessionStorage.setItem('resendCount', resendCount.toString());

                    showToast(data.message, 'success');

                    // Check if limit reached after this send
                    if (resendCount >= MAX_RESENDS) {
                        this.innerHTML = '<i class="fas fa-ban"></i> Limit Reached';
                        this.disabled = true;
                        this.classList.add('limit-reached');
                        setTimeout(() => showLimitModal(), 1500);
                    } else {
                        // Keep button disabled for 60 seconds to prevent spam
                        let countdown = 60;
                        const intervalId = setInterval(() => {
                            countdown--;
                            const remaining = MAX_RESENDS - resendCount;
                            this.innerHTML = `<i class="fas fa-clock"></i> Resend in ${countdown}s (${remaining} left)`;
                            if (countdown <= 0) {
                                clearInterval(intervalId);
                                updateButtonState();
                            }
                        }, 1000);
                    }
                } else {
                    showToast(data.message || 'Failed to send verification email', 'error');
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
                this.innerHTML = originalText;
                this.disabled = false;
            }
        });
    }

    function updateButtonState() {
        if (!resendBtn) return;

        const remaining = MAX_RESENDS - resendCount;

        if (resendCount >= MAX_RESENDS) {
            resendBtn.innerHTML = '<i class="fas fa-ban"></i> Limit Reached';
            resendBtn.disabled = true;
            resendBtn.classList.add('limit-reached');
        } else if (resendCount > 0) {
            resendBtn.innerHTML = `<i class="fas fa-envelope"></i> Resend Verification Email (${remaining} left)`;
            resendBtn.disabled = false;
        } else {
            resendBtn.innerHTML = '<i class="fas fa-envelope"></i> Resend Verification Email';
            resendBtn.disabled = false;
        }
    }
});

/**
 * Show toast notification in upper right corner
 */
function showToast(message, type) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-notification-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-notification-container';
        toastContainer.className = 'toast-notification-container';
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    toastContainer.appendChild(toast);

    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * Show limit reached modal
 */
function showLimitModal() {
    // Create modal if it doesn't exist
    let modal = document.getElementById('resend-limit-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'resend-limit-modal';
        modal.className = 'resend-limit-modal-overlay';
        modal.innerHTML = `
            <div class="resend-limit-modal-container">
                <div class="resend-limit-modal-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Resend Limit Reached</h3>
                </div>
                <div class="resend-limit-modal-body">
                    <p>You have reached the maximum number of verification email resends (3 attempts).</p>
                    <p>If you still haven't received the email, please:</p>
                    <ul>
                        <li>Check your spam/junk folder</li>
                        <li>Verify you entered the correct email address</li>
                        <li>Wait a few minutes for the email to arrive</li>
                        <li>Contact support if the issue persists</li>
                    </ul>
                </div>
                <div class="resend-limit-modal-footer">
                    <button class="modal-close-btn" onclick="document.getElementById('resend-limit-modal').remove()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Show modal with animation
    setTimeout(() => modal.classList.add('active'), 10);

    // Close on overlay click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 300);
        }
    });
}
