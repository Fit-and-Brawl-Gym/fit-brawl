document.addEventListener('DOMContentLoaded', function() {
    const termsLink = document.querySelector('.terms-link');
    const modalOverlay = document.querySelector('.terms-modal-overlay');
    const closeBtn = document.querySelector('.terms-close-btn');
    const declineBtn = document.querySelector('.terms-decline-btn');
    const acceptBtn = document.querySelector('.terms-accept-btn');
    const termsContent = document.querySelector('.terms-content');
    const sidebarLinks = document.querySelectorAll('.terms-desktop-nav a');
    const mobileNav = document.querySelector('.terms-mobile-nav');
    const mobileTrigger = mobileNav ? mobileNav.querySelector('.terms-dropdown-trigger') : null;
    const mobileList = mobileNav ? mobileNav.querySelector('.terms-dropdown-list') : null;
    const mobileOptions = mobileNav ? mobileNav.querySelectorAll('.terms-dropdown-list button') : [];

    // Debug: Check if elements are found
    console.log('Terms Modal Debug:', {
        termsLink: !!termsLink,
        modalOverlay: !!modalOverlay,
        closeBtn: !!closeBtn,
        declineBtn: !!declineBtn,
        acceptBtn: !!acceptBtn
    });

    // Open modal
    if (termsLink) {
        termsLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Terms link clicked');
            if (modalOverlay) {
                modalOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                console.log('Modal opened');
            }
        });
    }

    // Close modal function
    function closeModal() {
        if (modalOverlay) {
            modalOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
            console.log('Modal closed');
        }
    }

    // Close modal on close button
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    // Close modal on decline button
    if (declineBtn) {
        declineBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    // Close modal on accept button
    if (acceptBtn) {
        acceptBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Check the terms and conditions checkbox
            const termsCheckbox = document.getElementById('terms-checkbox');
            if (termsCheckbox) {
                termsCheckbox.checked = true;
                console.log('Terms accepted, checkbox checked');
            }
            closeModal();
        });
    }

    // Close modal on overlay click
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }

    // Smooth scroll to section on desktop sidebar click
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

    // Handle mobile dropdown interactions
    function closeMobileDropdown() {
        if (mobileTrigger && mobileList && !mobileList.hasAttribute('hidden')) {
            mobileList.setAttribute('hidden', '');
            mobileTrigger.setAttribute('aria-expanded', 'false');
        }
    }

    if (mobileTrigger && mobileList) {
        mobileTrigger.addEventListener('click', function() {
            const isOpen = mobileTrigger.getAttribute('aria-expanded') === 'true';
            if (isOpen) {
                closeMobileDropdown();
            } else {
                mobileList.removeAttribute('hidden');
                mobileTrigger.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function(e) {
            if (!mobileNav.contains(e.target)) {
                closeMobileDropdown();
            }
        });

        mobileOptions.forEach(option => {
            option.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetSection = document.querySelector(targetId);

                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                if (mobileTrigger) {
                    mobileTrigger.querySelector('.terms-dropdown-label').textContent = this.textContent;
                }

                closeMobileDropdown();
            });
        });
    }

    // Update active section indicator on scroll
    function updateActiveSection(currentSectionId) {
        // Update desktop sidebar links
        sidebarLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${currentSectionId}`) {
                link.classList.add('active');
            }
        });

        // Update mobile dropdown
        if (mobileNav && currentSectionId) {
            mobileNav.setAttribute('data-active', `#${currentSectionId}`);

            const activeOption = Array.from(mobileOptions).find(option => option.getAttribute('data-target') === `#${currentSectionId}`);

            mobileOptions.forEach(option => option.classList.remove('is-active'));

            if (activeOption) {
                activeOption.classList.add('is-active');
                if (mobileTrigger) {
                    mobileTrigger.querySelector('.terms-dropdown-label').textContent = activeOption.textContent;
                }
            }
        }
    }

    // Highlight active section on scroll
    if (termsContent) {
        termsContent.addEventListener('scroll', function() {
            closeMobileDropdown();

            const sections = document.querySelectorAll('.terms-content h3[id]');
            let currentSection = '';

            const scrollPosition = termsContent.scrollTop;
            const containerHeight = termsContent.clientHeight;
            const contentHeight = termsContent.scrollHeight;

            // Check if we're at or near the bottom of the content
            const isAtBottom = scrollPosition + containerHeight >= contentHeight - 10;

            sections.forEach((section, index) => {
                const sectionTop = section.offsetTop - termsContent.offsetTop;

                // If at the bottom, highlight the last section
                if (isAtBottom) {
                    currentSection = sections[sections.length - 1].getAttribute('id');
                } else if (scrollPosition >= sectionTop - 100) {
                    currentSection = section.getAttribute('id');
                }
            });

            // Update both desktop and mobile navigation
            updateActiveSection(currentSection);
        });
    }

    // Set initial active state
    const initialSection = document.querySelector('.terms-content h3[id]');
    if (initialSection) {
        updateActiveSection(initialSection.getAttribute('id'));
    }
});
