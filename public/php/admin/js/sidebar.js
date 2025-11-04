// Admin Sidebar Hamburger Menu
document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarClose = document.getElementById('sidebarClose');

    console.log('Sidebar initialized successfully');

    // Toggle sidebar
    function toggleSidebar() {
        hamburgerBtn.classList.toggle('active');
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');

        // Prevent body scroll when sidebar is open
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    // Close sidebar
    function closeSidebar() {
        hamburgerBtn.classList.remove('active');
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Event listeners
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', toggleSidebar);
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });

    // Close sidebar when clicking on navigation links (optional, for better UX)
    const navLinks = sidebar.querySelectorAll('nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            // Small delay to allow navigation
            setTimeout(closeSidebar, 200);
        });
    });

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            // Close sidebar on large screens if open
            if (window.innerWidth > 1024 && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        }, 250);
    });
});
