// Homepage modal functionality for non-member services
document.addEventListener('DOMContentLoaded', function() {
    const openModalBtn = document.getElementById('openServicesModal');
    const closeModalBtn = document.getElementById('closeServicesModal');
    const modal = document.getElementById('servicesModal');
    const backdrop = document.querySelector('.services-modal-backdrop');

    // Open modal
    if (openModalBtn) {
        openModalBtn.addEventListener('click', function() {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        });
    }

    // Close modal when clicking close button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            modal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        });
    }

    // Close modal when clicking backdrop
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            modal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        });
    }

    // Close modal when pressing ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.classList.contains('active')) {
            modal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }
    });
});

