document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger-menu');
    const navBar = document.querySelector('.nav-bar');

    if (hamburger && navBar) {
        // Function to close the menu
        function closeMenu() {
            hamburger.classList.remove('active');
            navBar.classList.remove('active');
            // Re-enable body scrolling
            document.body.style.overflow = '';
        }

        // Function to open the menu
        function openMenu() {
            hamburger.classList.add('active');
            navBar.classList.add('active');
            // Prevent body scrolling when menu is open
            document.body.style.overflow = 'hidden';
        }

        // Toggle menu when hamburger is clicked
        hamburger.addEventListener('click', function(event) {
            event.stopPropagation();
            if (navBar.classList.contains('active')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navBar.contains(event.target);
            const isClickOnHamburger = hamburger.contains(event.target);

            if (!isClickInsideNav && !isClickOnHamburger && navBar.classList.contains('active')) {
                closeMenu();
            }
        });

        // Close menu when scrolling
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            if (navBar.classList.contains('active')) {
                // Clear any existing timeout
                clearTimeout(scrollTimeout);

                // Close menu immediately on scroll
                closeMenu();
            }
        }, { passive: true });

        // Close menu on touch scroll (for mobile)
        let touchStartY = 0;
        let touchStartX = 0;
        document.addEventListener('touchstart', function(event) {
            if (navBar.classList.contains('active')) {
                touchStartY = event.touches[0].clientY;
                touchStartX = event.touches[0].clientX;
            }
        }, { passive: true });

        document.addEventListener('touchmove', function(event) {
            if (navBar.classList.contains('active')) {
                const touchEndY = event.touches[0].clientY;
                const touchEndX = event.touches[0].clientX;
                const deltaY = Math.abs(touchEndY - touchStartY);
                const deltaX = Math.abs(touchEndX - touchStartX);

                // If there's significant vertical or horizontal movement, close menu
                if (deltaY > 10 || deltaX > 10) {
                    const isTouchInsideNav = navBar.contains(event.target);
                    if (!isTouchInsideNav) {
                        closeMenu();
                    }
                }
            }
        }, { passive: true });

        // Close menu when clicking on a nav link
        const navLinks = navBar.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMenu();
            });
        });

        // Close menu on ESC key press
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && navBar.classList.contains('active')) {
                closeMenu();
            }
        });

        // Close menu when window is resized to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && navBar.classList.contains('active')) {
                closeMenu();
            }
        });
    }
});
