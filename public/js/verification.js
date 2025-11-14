document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otp');
    const resendBtn = document.getElementById('resend-otp');
    const countdownEl = document.getElementById('countdown');

    // Track attempts
    let isFirstAttempt = !sessionStorage.getItem('hasAttemptedResend');

    // Get original expiry time from session storage
    let originalExpiryTime = sessionStorage.getItem('originalOtpExpiryTime');
    let expiryTime = sessionStorage.getItem('otpExpiryTime');

    // Set initial expiry time only if both timers don't exist
    if (!originalExpiryTime && !expiryTime) {
        originalExpiryTime = Date.now() + (300 * 1000); // First attempt: 5 minutes
        expiryTime = originalExpiryTime;
        sessionStorage.setItem('originalOtpExpiryTime', originalExpiryTime);
        sessionStorage.setItem('otpExpiryTime', expiryTime);
    }

    function updateCountdown() {
        const now = Date.now();
        const timeLeft = Math.max(0, Math.floor((expiryTime - now) / 1000));

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;

        if (timeLeft > 0) {
            countdownEl.innerHTML = `OTP expires in: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            requestAnimationFrame(updateCountdown);
            // Only disable resend button if it's not first attempt
            if (!isFirstAttempt) {
                resendBtn.disabled = true;
            }
        } else {
            countdownEl.innerHTML = 'OTP has expired';
            resendBtn.disabled = false;
            sessionStorage.removeItem('otpExpiryTime');
        }
    }

    resendBtn.addEventListener('click', async function() {
        try {
            resendBtn.disabled = true;
            const response = await fetch('resend-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // Mark first attempt as used
                isFirstAttempt = false;
                sessionStorage.setItem('hasAttemptedResend', 'true');

                // Reset timer to 3 minutes for subsequent attempts
                expiryTime = Date.now() + (180 * 1000); // Changed to 3 minutes
                sessionStorage.setItem('otpExpiryTime', expiryTime);
                updateCountdown();

                // Show message with remaining resends
                const remainingMsg = data.remaining_resends > 0
                    ? ` (${data.remaining_resends} resends remaining)`
                    : ' (This was your last resend)';
                showMessage('New OTP sent to your email' + remainingMsg, 'success');

                // Disable resend button if limit reached
                if (data.remaining_resends === 0) {
                    resendBtn.disabled = true;
                    resendBtn.style.opacity = '0.5';
                    resendBtn.style.cursor = 'not-allowed';
                }
            } else {
                throw new Error(data.error || 'Failed to send OTP');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(error.message, 'error');
            resendBtn.disabled = !isFirstAttempt;
        }
    });

    function showMessage(message, type) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `${type}-message`;
        msgDiv.textContent = message;

        const form = document.querySelector('.verification-form');
        form.insertBefore(msgDiv, form.firstChild);

        setTimeout(() => msgDiv.remove(), 3000);
    }

    // Start countdown
    updateCountdown();

    // Clear ALL session storage on successful verification
    window.addEventListener('unload', function(event) {
        if (window.location.href.includes('change-password.php')) {
            sessionStorage.clear();
        }
    });
});
