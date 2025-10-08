document.addEventListener('DOMContentLoaded', function() {
    const termsLink = document.querySelector('.terms-link');
    const modalOverlay = document.querySelector('.terms-modal-overlay');
    const closeBtn = document.querySelector('.terms-close-btn');
    const declineBtn = document.querySelector('.terms-decline-btn');
    const acceptBtn = document.querySelector('.terms-accept-btn');
    const termsContent = document.querySelector('.terms-content');
    const sidebarLinks = document.querySelectorAll('.terms-sidebar nav a');

    // Open modal
    if (termsLink) {
        termsLink.addEventListener('click', function(e) {
            e.preventDefault();
            modalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    // Close modal function
    function closeModal() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Close modal on close button
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    // Close modal on decline button
    if (declineBtn) {
        declineBtn.addEventListener('click', closeModal);
    }

    // Close modal on accept button
    if (acceptBtn) {
        acceptBtn.addEventListener('click', closeModal);
    }

    // Close modal on overlay click
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    // Smooth scroll to section on sidebar click
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);

            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Highlight active section on scroll
    if (termsContent) {
        termsContent.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.terms-content h3[id]');
            let currentSection = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop - termsContent.offsetTop;
                const scrollPosition = termsContent.scrollTop;

                if (scrollPosition >= sectionTop - 100) {
                    currentSection = section.getAttribute('id');
                }
            });

            sidebarLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${currentSection}`) {
                    link.classList.add('active');
                }
            });
        });
    }
});
