document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger-menu');
    const navBar = document.querySelector('.nav-bar');

    if (hamburger && navBar) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navBar.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navBar.contains(event.target);
            const isClickOnHamburger = hamburger.contains(event.target);

            if (!isClickInsideNav && !isClickOnHamburger && navBar.classList.contains('active')) {
                hamburger.classList.remove('active');
                navBar.classList.remove('active');
            }
        });

        // Close menu when clicking on a nav link
        const navLinks = navBar.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                navBar.classList.remove('active');
            });
        });
    }
});
