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

    // Payment method switching
    const paymentMethodInputs = document.querySelectorAll('input[name="payment_method"]');
    const modalQrSection = document.getElementById('modalQrSection');
    const modalCashInstructions = document.getElementById('modalCashInstructions');
    const modalTitle = document.getElementById('modalTitle');
    const submitCashBtn = document.getElementById('submitCashBtn');

    function updatePaymentMethodUI() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'online';

        if (selectedMethod === 'cash') {
            confirmPaymentBtn.style.display = 'none';
            submitCashBtn.style.display = 'inline-block';
        } else {
            confirmPaymentBtn.style.display = 'inline-block';
            submitCashBtn.style.display = 'none';
        }
    }

    paymentMethodInputs.forEach(input => {
        input.addEventListener('change', updatePaymentMethodUI);
    });

    // Initialize on load
    updatePaymentMethodUI();

    function resetFileUpload() {
        selectedFile = null;
        receiptFileInput.value = '';
        fileUploadArea.style.display = 'block';
        filePreview.style.display = 'none';
        submitReceiptBtn.disabled = true;
    }

    function handleFileSelect(file) {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!validTypes.includes(file.type)) return alert('Invalid file type.');
        if (file.size > 10 * 1024 * 1024) return alert('File must be under 10MB.');

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

    // File input change handler - iOS compatible (no programmatic click needed)
    receiptFileInput.addEventListener('change', e => {
        if (e.target.files.length > 0) handleFileSelect(e.target.files[0]);
    });

    // Add drag and drop support for desktop browsers
    fileUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.style.borderColor = 'var(--color-accent)';
        fileUploadArea.style.background = 'rgba(213, 186, 43, 0.15)';
    });

    fileUploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.style.borderColor = '';
        fileUploadArea.style.background = '';
    });

    fileUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.style.borderColor = '';
        fileUploadArea.style.background = '';

        if (e.dataTransfer.files.length > 0) {
            // Manually set the files to the input for consistency
            receiptFileInput.files = e.dataTransfer.files;
            handleFileSelect(e.dataTransfer.files[0]);
        }
    });

    removeFileBtn.addEventListener('click', resetFileUpload);


    function openReceiptModal() {
        if (!subscriptionForm.checkValidity()) {
            subscriptionForm.reportValidity();
            return;
        }

        // Update modal content based on payment method
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'online';

        if (selectedMethod === 'cash') {
            modalTitle.textContent = 'Submit Cash Payment Receipt';
            modalQrSection.style.display = 'none';
            modalCashInstructions.style.display = 'block';
        } else {
            modalTitle.textContent = 'Submit Payment Receipt';
            modalQrSection.style.display = 'block';
            modalCashInstructions.style.display = 'none';
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

    // Cash payment submission handler
    submitCashBtn.addEventListener('click', () => {
        if (!subscriptionForm.checkValidity()) {
            subscriptionForm.reportValidity();
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        const basePlan = urlParams.get('plan') || 'gladiator';
        const formData = new FormData(subscriptionForm);
        formData.append('plan', basePlan);
        formData.append('billing', urlParams.get('billing') || 'monthly');
        formData.append('payment_method', 'cash');

        submitCashBtn.disabled = true;
        submitCashBtn.textContent = 'SUBMITTING...';

        fetch('api/process_subscription.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const msg = document.createElement('div');
                    msg.className = 'success-message';
                    msg.textContent = 'Request submitted! Please visit the gym to complete payment.';
                    msg.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#28a745;color:white;padding:15px 30px;border-radius:8px;z-index:10000;';
                    document.body.appendChild(msg);
                    setTimeout(() => window.location.href = 'membership-status.php', 2500);
                } else {
                    alert('Error: ' + data.message);
                    submitCashBtn.disabled = false;
                    submitCashBtn.textContent = 'SUBMIT REQUEST';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('An error occurred. Please try again.');
                submitCashBtn.disabled = false;
                submitCashBtn.textContent = 'SUBMIT REQUEST';
            });
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

        // Get CSRF token and add to form data
        const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!csrfToken) {
            alert('Your session expired. Please refresh the page.');
            submitReceiptBtn.disabled = false;
            submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
            return;
        }
        formData.append('csrf_token', csrfToken);

        if (selectedFile) {
            formData.append('receipt', selectedFile);
        } else {
            console.error('No file selected');
            alert('Please upload a receipt');
            return;
        }

        // Log form data
        console.log('Form data contents:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        submitReceiptBtn.disabled = true;
        submitReceiptBtn.textContent = 'SUBMITTING...';

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
                        const msg = document.createElement('div');
                        msg.className = 'success-message';
                        msg.textContent = 'Subscription submitted! Redirecting...';
                        document.body.appendChild(msg);
                        setTimeout(() => window.location.href = 'membership-status.php', 2000);
                    } else {
                        alert('Error: ' + data.message);
                        submitReceiptBtn.disabled = false;
                        submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    alert('Server error: Invalid response format');
                    submitReceiptBtn.disabled = false;
                    submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                alert('An error occurred. Please try again.');
                submitReceiptBtn.disabled = false;
                submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
            });
    });
});
