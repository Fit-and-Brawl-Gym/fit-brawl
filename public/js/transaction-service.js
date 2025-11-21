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

        // Get CSRF token - check multiple sources
        let csrfToken = formData.get('csrf_token'); // From hidden field
        if (!csrfToken) {
            csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        if (!csrfToken) {
            alert('Your session expired. Please refresh the page.');
            btn.textContent = originalText;
            btn.disabled = false;
            return;
        }

        // Ensure token is in formData
        if (!formData.has('csrf_token')) {
            formData.append('csrf_token', csrfToken);
        }

        // Ensure service_date is in YYYY-MM-DD format
        if (formData.has('service_date')) {
            let rawDate = formData.get('service_date');
            // Try to parse and reformat if needed
            if (rawDate) {
                // Acceptable formats: YYYY-MM-DD, MM/DD/YYYY, "November 22, 2025", etc.
                let dateObj = new Date(rawDate);
                if (!isNaN(dateObj.getTime())) {
                    // Format to YYYY-MM-DD
                    let yyyy = dateObj.getFullYear();
                    let mm = String(dateObj.getMonth() + 1).padStart(2, '0');
                    let dd = String(dateObj.getDate()).padStart(2, '0');
                    let formatted = `${yyyy}-${mm}-${dd}`;
                    formData.set('service_date', formatted);
                }
            }
        }

        // Debug: Log all form data
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ':', value);
        }

        fetch('api/process_service_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(async res => {
            // First get the response as text
            const responseText = await res.text();
            console.log('Raw response:', responseText);
            console.log('Status:', res.status);

            let data;
            try {
                // Try to parse as JSON
                if (responseText.trim()) {
                    data = JSON.parse(responseText);
                } else {
                    throw new Error('Server returned empty response (status ' + res.status + ')');
                }
            } catch (e) {
                console.error('Parse error:', e);
                console.error('Response was:', responseText.substring(0, 500));
                throw new Error('Server error (status ' + res.status + '): ' + responseText.substring(0, 200));
            }

            if (res.ok && data.success) {
                const msg = document.createElement('div');
                msg.className = 'success-message';
                msg.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #4caf50; color: white; padding: 15px 30px; border-radius: 8px; z-index: 10000; font-weight: bold;';
                msg.textContent = '✅ Receipt generated! Redirecting...';
                document.body.appendChild(msg);

                setTimeout(() => {
                    window.location.href = 'receipt_service.php?id=' + data.receipt_id;
                }, 1500);
            } else {
                const errorMsg = data && data.message ? data.message : 'Unknown error occurred';
                console.error('Server error:', errorMsg, data);
                alert('Error: ' + errorMsg);
                btn.textContent = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('An error occurred: ' + err.message);
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });
});
