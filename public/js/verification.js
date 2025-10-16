document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otp');
    const resendBtn = document.getElementById('resend-otp');
    const countdownEl = document.getElementById('countdown');
    
    // Get original expiry time from session storage
    let originalExpiryTime = sessionStorage.getItem('originalOtpExpiryTime');
    let expiryTime = sessionStorage.getItem('otpExpiryTime');

    // Set initial expiry time only if both timers don't exist
    if (!originalExpiryTime && !expiryTime) {
        originalExpiryTime = Date.now() + (300 * 1000); // 5 minutes
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
            resendBtn.disabled = true;
        } else {
            countdownEl.innerHTML = 'OTP has expired';
            resendBtn.disabled = false;
            // Only remove current expiry time, keep original
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
                // Use the original timer duration
                expiryTime = Date.now() + (300 * 1000);
                sessionStorage.setItem('otpExpiryTime', expiryTime);
                updateCountdown();
                showMessage('New OTP sent to your email', 'success');
            } else {
                throw new Error(data.error || 'Failed to send OTP');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(error.message, 'error');
            resendBtn.disabled = false;
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

    // Clear ALL session storage only when verification is successful
    // This should be triggered by the PHP verification success
    window.addEventListener('unload', function(event) {
        if (window.location.href.includes('change-password.php')) {
            sessionStorage.removeItem('originalOtpExpiryTime');
            sessionStorage.removeItem('otpExpiryTime');
        }
    });
});