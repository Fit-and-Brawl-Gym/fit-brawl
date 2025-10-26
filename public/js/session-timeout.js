class SessionTimer {
    constructor() {
        console.log('Initializing session timer');
        this.warningTime = 120; // 2 minutes warning
        this.idleTimeout = 900; // 15 minutes total
        this.remainingTime = this.idleTimeout;
        this.isWarningDisplayed = false;
        this.displayInterval = null;
        this.lastActivityTime = Date.now();
        
        console.log('Setting up components...');
        this.setupModal();
        this.setupActivityTracking();
        this.startTimer();
        console.log('Timer initialized with:', {
            warningTime: this.warningTime,
            remainingTime: this.remainingTime
        });
    }

    setupModal() {
        console.log('Creating warning modal');
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
        console.log('Warning modal created');
    }

    setupActivityTracking() {
        console.log('Setting up activity tracking');
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
        console.log('Activity tracking initialized');
    }

    resetTimer() {
        console.log('Activity detected - resetting timer');
        this.remainingTime = this.idleTimeout;
        this.isWarningDisplayed = false;
        this.warningModal.style.display = 'none';
        this.updateCountdown(this.remainingTime);
        
        // Notify server of activity
        this.extendSession();
    }

    startTimer() {
        console.log('Starting timer');
        if (this.displayInterval) {
            console.log('Clearing existing interval');
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
        console.log('Timer started with interval ID:', this.displayInterval);
    }

    async extendSession() {
        console.log('Extending session');
        try {
            const response = await fetch('extend_session.php');
            const data = await response.json();
            
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
            console.log('Updated countdown display:', countdown.textContent);
        }
    }

    showWarning() {
        console.log('Showing warning modal');
        if (!this.isWarningDisplayed) {
            this.isWarningDisplayed = true;
            this.warningModal.style.display = 'flex';
        }
    }

    handleTimeout() {
        console.log('Session timeout - logging out');
        if (this.displayInterval) {
            clearInterval(this.displayInterval);
        }
        window.location.href = 'logout.php';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded - creating SessionTimer');
    window.sessionTimer = new SessionTimer();
});