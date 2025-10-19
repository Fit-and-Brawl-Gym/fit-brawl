document.addEventListener('DOMContentLoaded', function () {

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
    const billingBtns = document.querySelectorAll('.billing-btn');
    const subscriptionForm = document.getElementById('subscriptionForm');

    let selectedFile = null;


    function updatePlanPrice() {
        const billing = document.querySelector('input[name="billing"]:checked')?.value || 'monthly';
        const variantRadio = document.querySelector('input[name="variant"]:checked');
        const variant = variantRadio ? variantRadio.value : null;
        const priceAmount = document.querySelector('.plan-card-transaction .plan-price .price-amount');
        const pricePeriod = document.querySelector('.plan-card-transaction .plan-price .price-period');

        if (!priceAmount || !pricePeriod) return; // Safety

        // Example global vars (define these in your HTML)
        // let monthlyPrice = 299, yearlyPrice = 2999, resolutionPrices = {...}

        if (variant && typeof resolutionPrices !== 'undefined' && resolutionPrices[variant]) {
            priceAmount.textContent = resolutionPrices[variant][billing];
            pricePeriod.textContent = billing === 'yearly' ? '/YEAR' : '/MONTH';
        } else {
            priceAmount.textContent = billing === 'yearly' ? yearlyPrice : monthlyPrice;
            pricePeriod.textContent = billing === 'yearly' ? '/YEAR' : '/MONTH';
        }
    }


    billingBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const billing = this.getAttribute('data-billing');
            const urlParams = new URLSearchParams(window.location.search);
            const plan = urlParams.get('plan') || 'gladiator';

            urlParams.set('billing', billing);
            urlParams.set('plan', plan);

            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
            window.history.pushState({}, '', newUrl);

            updatePlanPrice();
        });
    });


    document.querySelectorAll('input[name="billing"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.checked) {
                const billing = this.value;
                const urlParams = new URLSearchParams(window.location.search);
                const plan = urlParams.get('plan') || 'gladiator';
                urlParams.set('billing', billing);
                urlParams.set('plan', plan);

                const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
                window.history.pushState({}, '', newUrl);

                updatePlanPrice();
            }
        });
    });


    document.querySelectorAll('input[name="variant"]').forEach(radio => {
        radio.addEventListener('change', updatePlanPrice);
    });


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

    fileUploadArea.addEventListener('click', () => receiptFileInput.click());
    receiptFileInput.addEventListener('change', e => {
        if (e.target.files.length > 0) handleFileSelect(e.target.files[0]);
    });
    removeFileBtn.addEventListener('click', resetFileUpload);


  submitReceiptBtn.addEventListener('click', function () {
    if (!selectedFile) return alert('Please select a file first.');

    // Disable buttons immediately
    submitReceiptBtn.disabled = true;
    removeFileBtn.disabled = true;
    cancelReceiptBtn.disabled = true;
    submitReceiptBtn.textContent = 'SUBMITTING...';

    const formData = new FormData(subscriptionForm);
    formData.append('receipt', selectedFile);

    const urlParams = new URLSearchParams(window.location.search);
    formData.append('plan', urlParams.get('plan') || 'gladiator');
    formData.append('billing', urlParams.get('billing') || 'monthly');

    fetch('api/process_subscription.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Do NOT re-enable buttons here
            const msg = document.createElement('div');
            msg.className = 'success-message';
            msg.textContent = 'Subscription submitted! Redirecting...';
            document.body.appendChild(msg);
            setTimeout(() => {
                window.location.href = 'membership-status.php';
            }, 2000);
        } else {
            alert('Error: ' + data.message);
            submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
            // Re-enable buttons only on error
            submitReceiptBtn.disabled = false;
            removeFileBtn.disabled = false;
            cancelReceiptBtn.disabled = false;
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('An error occurred. Please try again.');
        submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
        // Re-enable buttons only on error
        submitReceiptBtn.disabled = false;
        removeFileBtn.disabled = false;
        cancelReceiptBtn.disabled = false;
    });
});

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

    updatePlanPrice();
});
