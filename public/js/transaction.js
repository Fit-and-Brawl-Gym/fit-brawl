// Toast notification function
function showNotification(message, type = 'success', persistent = false) {
    // Remove any existing notifications first
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.style.animation = 'slideOut 300ms ease';
        setTimeout(() => existingNotification.remove(), 300);
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;

    // Set background color based on type
    let bgColor = '#28a745'; // success - green
    if (type === 'error') bgColor = '#dc3545'; // error - red
    if (type === 'info') bgColor = '#17a2b8'; // info - blue
    if (type === 'warning') bgColor = '#ffc107'; // warning - yellow

    // Add spinner icon for persistent info notifications
    let icon = '';
    if (persistent && type === 'info') {
        icon = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>';
    }

    notification.innerHTML = icon + message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 20px;
        background: ${bgColor};
        color: white;
        border-radius: 6px;
        z-index: 3000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 300ms ease;
        font-weight: 500;
        display: flex;
        width: 20%;
        align-items: center;
    `;

    // Wait a tiny bit to ensure old notification is removed
    setTimeout(() => {
        document.body.appendChild(notification);

        // Auto-dismiss after 3 seconds if not persistent
        if (!persistent) {
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'slideOut 300ms ease';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 3000);
        }
    }, 350);

    return notification;
}

// Add CSS animations if not already present
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

document.addEventListener('DOMContentLoaded', () => {
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    const receiptModal = document.getElementById('receiptModal');
    const receiptModalOverlay = document.getElementById('receiptModalOverlay');
    const closeReceiptModal = document.getElementById('closeReceiptModal');
    const cancelReceiptBtn = document.getElementById('cancelReceiptBtn');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const receiptFileInput = document.getElementById('receiptFile');
    const filePreview = document.getElementById('filePreview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    const removeFileBtn = document.getElementById('removeFile');
    const submitReceiptBtn = document.getElementById('submitReceiptBtn');
    const subscriptionForm = document.getElementById('subscriptionForm');

    let selectedFile = null;

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updatePlanPrice() {
        const billing = document.querySelector('input[name="billing"]:checked')?.value || 'monthly';
        const priceAmount = document.querySelector('.plan-card-transaction .plan-price .price-amount');
        const pricePeriod = document.querySelector('.plan-card-transaction .plan-price .price-period');
        if (!priceAmount || !pricePeriod) return;

        const price = billing === 'quarterly' ? quarterlyPrice : monthlyPrice;
        priceAmount.textContent = formatNumber(price);
        pricePeriod.textContent = billing === 'quarterly' ? '/QUARTER' : '/MONTH';
    }


    function setURLParam(key, value, replace = false) {
        const url = new URL(window.location.href);
        url.searchParams.set(key, value);
        if (replace) {
            window.history.replaceState({}, '', url);
        } else {
            window.history.pushState({}, '', url);
        }
    }

    document.querySelectorAll('input[name="billing"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const billing = radio.value;
            setURLParam('billing', billing);
            updatePlanPrice();
        });
    });

    updatePlanPrice();

    function resetFileUpload() {
        selectedFile = null;
        receiptFileInput.value = '';
        fileUploadArea.style.display = 'block';
        filePreview.style.display = 'none';
        submitReceiptBtn.disabled = true;
    }

    function handleFileSelect(file) {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
            showNotification('Invalid file type. Please upload a JPEG, PNG, or PDF file.', 'error');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showNotification('File must be under 10MB. Please choose a smaller file.', 'error');
            return;
        }

        selectedFile = file;
        fileName.textContent = file.name;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => previewImage.src = e.target.result;
            reader.readAsDataURL(file);
            previewImage.style.display = 'block';
        } else {
            previewImage.style.display = 'none';
        }

        fileUploadArea.style.display = 'none';
        filePreview.style.display = 'block';
        submitReceiptBtn.disabled = false;
    }

    fileUploadArea.addEventListener('click', () => receiptFileInput.click());
    receiptFileInput.addEventListener('change', e => {
        if (e.target.files.length > 0) handleFileSelect(e.target.files[0]);
    });
    removeFileBtn.addEventListener('click', resetFileUpload);


    function openReceiptModal() {
        if (!subscriptionForm.checkValidity()) {
            subscriptionForm.reportValidity();
            return;
        }
        receiptModal.classList.add('active');
        receiptModalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        receiptModal.classList.remove('active');
        receiptModalOverlay.classList.remove('active');
        document.body.style.overflow = '';
        resetFileUpload();
    }

    confirmPaymentBtn.addEventListener('click', openReceiptModal);
    closeReceiptModal.addEventListener('click', closeModal);
    cancelReceiptBtn.addEventListener('click', closeModal);
    receiptModalOverlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && receiptModal.classList.contains('active')) closeModal();
    });

    submitReceiptBtn.addEventListener('click', () => {
        console.log('Submit button clicked');

        const urlParams = new URLSearchParams(window.location.search);
        let basePlan = urlParams.get('plan') || 'gladiator';

        console.log('Submitting plan:', basePlan);
        console.log('Selected file:', selectedFile);

        const formData = new FormData(subscriptionForm);
        formData.append('plan', basePlan);
        formData.append('billing', urlParams.get('billing') || 'monthly');

        if (selectedFile) {
            formData.append('receipt', selectedFile);
        } else {
            console.error('No file selected');
            showNotification('Please upload a payment receipt', 'warning');
            return;
        }

        // Log form data
        console.log('Form data contents:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        submitReceiptBtn.disabled = true;
        submitReceiptBtn.textContent = 'SUBMITTING...';

        // Show persistent processing notification
        showNotification('Submitting your subscription...', 'info', true);

        console.log('Making fetch request to: api/process_subscription.php');

        fetch('api/process_subscription.php', {
            method: 'POST',
            body: formData
        })
            .then(res => {
                console.log('Response received:', res);
                console.log('Response status:', res.status);
                return res.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed JSON:', data);

                    if (data.success) {
                        showNotification('Subscription submitted successfully! Redirecting...', 'success');
                        setTimeout(() => window.location.href = 'membership-status.php', 2000);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                        submitReceiptBtn.disabled = false;
                        submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    showNotification('Server error: Invalid response format', 'error');
                    submitReceiptBtn.disabled = false;
                    submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                showNotification('An error occurred. Please try again.', 'error');
                submitReceiptBtn.disabled = false;
                submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
            });
    });
});