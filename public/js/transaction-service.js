document.addEventListener('DOMContentLoaded', () => {
    const subscriptionForm = document.getElementById('subscriptionForm');

    // Handle form submission - direct QR receipt generation
    subscriptionForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Show loading state
        const btn = document.getElementById('confirmPaymentBtn');
        const originalText = btn.textContent;
        btn.textContent = 'GENERATING...';
        btn.disabled = true;

        // Get CSRF token and add to form data
        const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!csrfToken) {
            alert('Your session expired. Please refresh the page.');
            btn.textContent = originalText;
            btn.disabled = false;
            return;
        }
        formData.append('csrf_token', csrfToken);

        fetch('api/process_service_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const msg = document.createElement('div');
                msg.className = 'success-message';
                msg.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #4caf50; color: white; padding: 15px 30px; border-radius: 8px; z-index: 10000; font-weight: bold;';
                msg.textContent = '✅ Receipt generated! Redirecting...';
                document.body.appendChild(msg);

                setTimeout(() => {
                    window.location.href = 'receipt_service.php?id=' + data.receipt_id;
                }, 1500);
            } else {
                alert('Error: ' + data.message);
                btn.textContent = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('An error occurred. Please try again.');
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });
});
