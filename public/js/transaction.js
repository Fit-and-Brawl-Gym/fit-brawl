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

    function updatePlanPrice() {
        const billing = document.querySelector('input[name="billing"]:checked')?.value || 'monthly';
        const variant = document.querySelector('input[name="variant"]:checked')?.value || null;
        const priceAmount = document.querySelector('.plan-card-transaction .plan-price .price-amount');
        const pricePeriod = document.querySelector('.plan-card-transaction .plan-price .price-period');
        if (!priceAmount || !pricePeriod) return;

        if (variant && typeof resolutionPrices !== 'undefined' && resolutionPrices[variant]) {
            priceAmount.textContent = resolutionPrices[variant][billing];
            pricePeriod.textContent = billing === 'yearly' ? '/YEAR' : '/MONTH';
        } else {
            priceAmount.textContent = billing === 'yearly' ? yearlyPrice : monthlyPrice;
            pricePeriod.textContent = billing === 'yearly' ? '/YEAR' : '/MONTH';
        }
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

    document.querySelectorAll('input[name="variant"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const variant = radio.value;
            setURLParam('variant', variant, true);
            setURLParam('plan', 'resolution', true);
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
        const selectedVariant = document.querySelector('input[name="variant"]:checked')?.value;
        const urlParams = new URLSearchParams(window.location.search);
        let basePlan = urlParams.get('plan') || 'gladiator';

        if (basePlan.startsWith('resolution') && selectedVariant) {
            basePlan = 'resolution-' + selectedVariant;
        }

        console.log('Submitting plan:', basePlan);

        const formData = new FormData(subscriptionForm);
        formData.append('plan', basePlan);
        formData.append('billing', urlParams.get('billing') || 'monthly');

        if (selectedFile) formData.append('receipt', selectedFile);

        submitReceiptBtn.disabled = true;

        fetch('api/process_subscription.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const msg = document.createElement('div');
                msg.className = 'success-message';
                msg.textContent = 'Subscription submitted! Redirecting...';
                document.body.appendChild(msg);
                setTimeout(() => window.location.href = 'membership-status.php', 2000);
            } else {
                alert('Error: ' + data.message);
                submitReceiptBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred. Please try again.');
            submitReceiptBtn.disabled = false;
        });
    });

    const variantRadios = document.querySelectorAll('input[name="variant"]');
    const url = new URL(window.location.href);
    let currentVariant = url.searchParams.get('variant');

    if (!currentVariant && variantRadios.length > 0) {
        const regularRadio = Array.from(variantRadios).find(r => r.value === 'regular');
        if (regularRadio) {
            regularRadio.checked = true;
            setURLParam('variant', 'regular', true);
        }
    } else if (currentVariant) {
        const matchRadio = Array.from(variantRadios).find(r => r.value === currentVariant);
        if (matchRadio) matchRadio.checked = true;
    }

    updatePlanPrice();
});
