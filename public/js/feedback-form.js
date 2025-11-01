document.addEventListener('DOMContentLoaded', function() {
    const successModal = document.getElementById('successModal');
    const countdownElement = document.getElementById('countdown');
    const redirectNowBtn = document.getElementById('redirectNow');

    // Check if feedback was successfully submitted via data attribute
    const shouldShowModal = successModal && successModal.getAttribute('data-show') === 'true';

    if (shouldShowModal) {
        // Show the modal immediately
        successModal.classList.add('active');

        // Countdown and redirect
        let countdown = 5;
        countdownElement.textContent = countdown;

        const countdownInterval = setInterval(() => {
            countdown--;
            if (countdown > 0) {
                countdownElement.textContent = countdown;
            } else {
                clearInterval(countdownInterval);
                window.location.href = 'feedback.php';
            }
        }, 1000);

        // Redirect immediately if button is clicked
        redirectNowBtn.addEventListener('click', function() {
            clearInterval(countdownInterval);
            window.location.href = 'feedback.php';
        });

        // Close modal on outside click (optional)
        successModal.addEventListener('click', function(e) {
            if (e.target === successModal) {
                clearInterval(countdownInterval);
                window.location.href = 'feedback.php';
            }
        });
    }
});
