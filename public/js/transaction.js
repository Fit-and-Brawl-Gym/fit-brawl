document.addEventListener('DOMContentLoaded', function () {
    // Elements
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

    // Billing toggle functionality
    billingBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const billing = this.getAttribute('data-billing');
            const urlParams = new URLSearchParams(window.location.search);
            const plan = urlParams.get('plan') || 'gladiator';

        });
    });

    // Billing toggle functionality with radio buttons
    const billingRadios = document.querySelectorAll('input[name="billing"]');
    billingRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.checked) {
                const billing = this.value;
                // Update price display here
                // Example:
                // document.querySelector('.plan-price .price-amount').textContent = billing === 'monthly' ? monthlyPrice : yearlyPrice;
            }
        });
    });

    // Variant toggle functionality (for Resolution plan)
    document.querySelectorAll('input[name="variant"]').forEach(radio => {
        radio.addEventListener('change', function () {
            updatePlanPrice();
        });
    });

    // Open receipt modal
    function openReceiptModal() {
        // Validate form first
        if (!subscriptionForm.checkValidity()) {
            subscriptionForm.reportValidity();
            return;
        }

        receiptModal.classList.add('active');
        receiptModalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Close receipt modal
    function closeModal() {
        receiptModal.classList.remove('active');
        receiptModalOverlay.classList.remove('active');
        document.body.style.overflow = '';
        resetFileUpload();
    }

    // Reset file upload
    function resetFileUpload() {
        selectedFile = null;
        receiptFileInput.value = '';
        fileUploadArea.style.display = 'block';
        filePreview.style.display = 'none';
        submitReceiptBtn.disabled = true;
    }

    // File upload area click
    fileUploadArea.addEventListener('click', function () {
        receiptFileInput.click();
    });

    // Drag and drop functionality
    fileUploadArea.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.style.borderColor = 'var(--color-accent)';
        this.style.background = 'rgba(213, 186, 43, 0.1)';
    });

    fileUploadArea.addEventListener('dragleave', function (e) {
        e.preventDefault();
        this.style.borderColor = 'rgba(213, 186, 43, 0.5)';
        this.style.background = 'rgba(255, 255, 255, 0.05)';
    });

    fileUploadArea.addEventListener('drop', function (e) {
        e.preventDefault();
        this.style.borderColor = 'rgba(213, 186, 43, 0.5)';
        this.style.background = 'rgba(255, 255, 255, 0.05)';

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    // File input change
    receiptFileInput.addEventListener('change', function (e) {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    // Handle file selection
    function handleFileSelect(file) {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
            alert('Please upload a valid image (JPG, PNG) or PDF file.');
            return;
        }

        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB.');
            return;
        }

        selectedFile = file;
        fileName.textContent = file.name;

        // Show preview for images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewImage.style.display = 'none';
        }

        fileUploadArea.style.display = 'none';
        filePreview.style.display = 'block';
        submitReceiptBtn.disabled = false;
    }

    // Remove file
    removeFileBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        resetFileUpload();
    });

    // Submit receipt
    submitReceiptBtn.addEventListener('click', function () {
        if (!selectedFile) {
            alert('Please select a file first.');
            return;
        }

        // Get form data
        const formData = new FormData(subscriptionForm);
        formData.append('receipt', selectedFile);

        // Get plan details from URL
        const urlParams = new URLSearchParams(window.location.search);
        formData.append('plan', urlParams.get('plan') || 'gladiator');
        formData.append('billing', urlParams.get('billing') || 'monthly');

        // Show loading state
        submitReceiptBtn.textContent = 'SUBMITTING...';
        submitReceiptBtn.disabled = true;

        // TODO: Send to backend
        // For now, simulate submission
        setTimeout(() => {
            alert('Payment receipt submitted successfully! We will verify your payment and activate your membership soon.');
            window.location.href = '../membership.php?success=1';
        }, 1500);

        // Actual AJAX call would look like:
        /*
        fetch('process_subscription.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment receipt submitted successfully!');
                window.location.href = 'membership.php?success=1';
            } else {
                alert('Error: ' + data.message);
                submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
                submitReceiptBtn.disabled = false;
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
            submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
            submitReceiptBtn.disabled = false;
        });
        */
    });

    // Event listeners
    confirmPaymentBtn.addEventListener('click', openReceiptModal);
    closeReceiptModal.addEventListener('click', closeModal);
    cancelReceiptBtn.addEventListener('click', closeModal);
    receiptModalOverlay.addEventListener('click', closeModal);

    // ESC key to close modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && receiptModal.classList.contains('active')) {
            closeModal();
        }
    });
});

document.querySelectorAll('input[name="billing"]').forEach(radio => {
    radio.addEventListener('change', function (e) {
        // Update displayed prices and info here
        // Do NOT reload the page or submit the form
        // Example: update price display
        // document.querySelector('.plan-price .price-amount').textContent = ...;
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[name="billing"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const priceAmount = document.querySelector('.plan-card-transaction .plan-price .price-amount');
            const pricePeriod = document.querySelector('.plan-card-transaction .plan-price .price-period');
            if (this.value === 'monthly') {
                priceAmount.textContent = monthlyPrice;
                pricePeriod.textContent = '/MONTH';
            } else {
                priceAmount.textContent = yearlyPrice;
                pricePeriod.textContent = '/YEAR';
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    // Billing radio change
    document.querySelectorAll('input[name="billing"]').forEach(radio => {
        radio.addEventListener('change', function () {
            updatePlanPrice();
        });
    });

    // Variant radio change (for Resolution plan)
    document.querySelectorAll('input[name="variant"]').forEach(radio => {
        radio.addEventListener('change', function () {
            updatePlanPrice();
        });
    });

    function updatePlanPrice() {
        const billing = document.querySelector('input[name="billing"]:checked').value;
        const variantRadio = document.querySelector('input[name="variant"]:checked');
        const variant = variantRadio ? variantRadio.value : null;
        const priceAmount = document.querySelector('.plan-card-transaction .plan-price .price-amount');
        const pricePeriod = document.querySelector('.plan-card-transaction .plan-price .price-period');

        // If Resolution plan
        if (variant && resolutionPrices[variant]) {
            priceAmount.textContent = resolutionPrices[variant][billing];
            pricePeriod.textContent = billing === 'yearly' ? '/YEAR' : '/MONTH';
        } else {
            // For other plans, use global monthlyPrice/yearlyPrice
            priceAmount.textContent = billing === 'yearly' ? yearlyPrice : monthlyPrice;
            pricePeriod.textContent = billing === 'yearly' ? '/YEAR' : '/MONTH';
        }
    }

    // Initial load
    updatePlanPrice();
});