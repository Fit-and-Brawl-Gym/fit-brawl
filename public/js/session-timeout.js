class SessionTimer {
    constructor() {
        this.warningTime = 120; // 2 minutes warning
        this.idleTimeout = 900; // 15 minutes total
        this.remainingTime = this.idleTimeout;
        this.isWarningDisplayed = false;
        this.displayInterval = null;
        this.lastActivityTime = Date.now();

        this.setupModal();
        this.setupActivityTracking();
        this.startTimer();
    }

    setupModal() {
        const modal = document.createElement('div');
        modal.className = 'session-warning-modal';
        modal.innerHTML = `
            <div class="session-warning-content">
                <h3>Session Timeout Warning</h3>
                <p>Your session will expire in <span id="timeout-countdown">3:00</span></p>
                <p class="warning-hint">Move mouse or press any key to stay logged in</p>
            </div>
        `;
        document.body.appendChild(modal);
        this.warningModal = modal;
    }

    setupActivityTracking() {
        const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];

        const handleActivity = () => {
            const now = Date.now();
            // Only handle activity if enough time has passed (prevent spam)
            if (now - this.lastActivityTime > 1000) {
                this.lastActivityTime = now;
                this.resetTimer();
            }
        };

        events.forEach(event => {
            document.addEventListener(event, handleActivity, { passive: true });
        });
    }

    resetTimer() {
        this.remainingTime = this.idleTimeout;
        this.isWarningDisplayed = false;
        this.warningModal.style.display = 'none';
        this.updateCountdown(this.remainingTime);

        // Notify server of activity
        this.extendSession();
    }

    startTimer() {
        if (this.displayInterval) {
            clearInterval(this.displayInterval);
        }

        this.displayInterval = setInterval(() => {
            if (this.remainingTime > 0) {
                this.remainingTime--;
                this.updateCountdown(this.remainingTime);

                if (!this.isWarningDisplayed && this.remainingTime <= this.warningTime) {
                    this.showWarning();
                } else if (this.remainingTime <= 0) {
                    this.handleTimeout();
                }
            }
        }, 1000);
    }

    async extendSession() {
        try {
            const publicPath = window.PUBLIC_PATH || '/fit-brawl/public';
            const url = `${publicPath}/php/extend_session.php`;
            console.log('Extending session with URL:', url);
            
            const response = await fetch(url);
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Response data:', data);

            if (!data.success) {
                console.error('Failed to extend session on server');
                this.handleTimeout();
            }
        } catch (error) {
            console.error('Failed to extend session:', error);
        }
    }

    updateCountdown(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        const countdown = document.getElementById('timeout-countdown');
        if (countdown) {
            countdown.textContent = `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
    }

    showWarning() {
        if (!this.isWarningDisplayed) {
            this.isWarningDisplayed = true;
            this.warningModal.style.display = 'flex';
        }
    }

    handleTimeout() {
        if (this.displayInterval) {
            clearInterval(this.displayInterval);
        }
        window.location.href = 'logout.php';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.sessionTimer = new SessionTimer();
});
