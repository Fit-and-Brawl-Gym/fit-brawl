document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otp');
    const resendBtn = document.getElementById('resend-otp');
    const countdownEl = document.getElementById('countdown');
    
    // Get expiry time from session storage or set new one
    let expiryTime = sessionStorage.getItem('otpExpiryTime');
    if (!expiryTime) {
        expiryTime = Date.now() + (300 * 1000); // 5 minutes in milliseconds
        sessionStorage.setItem('otpExpiryTime', expiryTime);
    }

    // Clear session storage when leaving the page
    window.addEventListener('beforeunload', function() {
        sessionStorage.removeItem('otpExpiryTime');
    });

    function updateCountdown() {
        const now = Date.now();
        const timeLeft = Math.max(0, Math.floor((expiryTime - now) / 1000));
        
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        if (timeLeft > 0) {
            countdownEl.innerHTML = `OTP expires in: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            setTimeout(updateCountdown, 1000);
        } else {
            countdownEl.innerHTML = 'OTP has expired';
            resendBtn.disabled = false;
            sessionStorage.removeItem('otpExpiryTime');
        }
    }

    // Update resend button click handler
    resendBtn.addEventListener('click', async function() {
        resendBtn.disabled = true;
        
        try {
            const response = await fetch('resend-otp.php', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Set new expiry time to 5 minutes
                expiryTime = Date.now() + (300 * 1000);
                sessionStorage.setItem('otpExpiryTime', expiryTime);
                updateCountdown();
                
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.className = 'success-message';
                successMsg.textContent = 'New OTP sent to your email';
                
                const form = document.querySelector('.verification-form');
                form.insertBefore(successMsg, form.firstChild);
                
                setTimeout(() => successMsg.remove(), 3000);
            } else {
                throw new Error(data.error || 'Failed to send OTP');
            }
        } catch (error) {
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.textContent = error.message;
            
            const form = document.querySelector('.verification-form');
            form.insertBefore(errorMsg, form.firstChild);
            
            setTimeout(() => errorMsg.remove(), 3000);
        }
    });

    // Start countdown
    updateCountdown();
});