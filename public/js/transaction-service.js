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
            alert('Invalid file type. Please upload JPG, PNG, or PDF only.');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert('File must be under 10MB.');
            return;
        }

        selectedFile = file;
        fileName.textContent = file.name;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
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

    fileUploadArea.addEventListener('click', () => receiptFileInput.click());

    receiptFileInput.addEventListener('change', e => {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    removeFileBtn.addEventListener('click', resetFileUpload);

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
        if (e.key === 'Escape' && receiptModal.classList.contains('active')) {
            closeModal();
        }
    });

    submitReceiptBtn.addEventListener('click', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const service = urlParams.get('service');

        if (!service) {
            alert('Service information is missing');
            return;
        }

        const formData = new FormData(subscriptionForm);
        formData.append('service', service);

        if (selectedFile) {
            formData.append('receipt', selectedFile);
        } else {
            alert('Please upload a payment receipt');
            return;
        }

        submitReceiptBtn.disabled = true;
        submitReceiptBtn.textContent = 'SUBMITTING...';

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
                msg.textContent = '✅ Booking confirmed! Redirecting to receipt...';
                document.body.appendChild(msg);

                setTimeout(() => {
                    window.location.href = 'receipt_service.php?id=' + data.receipt_id;
                }, 1500);
            } else {
                alert('Error: ' + data.message);
                submitReceiptBtn.disabled = false;
                submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('An error occurred. Please try again.');
            submitReceiptBtn.disabled = false;
            submitReceiptBtn.textContent = 'SUBMIT RECEIPT';
        });
    });
});
