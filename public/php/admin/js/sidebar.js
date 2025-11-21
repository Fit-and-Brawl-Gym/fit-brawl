// Admin Sidebar Hamburger Menu & Collapse

document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');

    // Load saved collapse state from localStorage
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed && window.innerWidth > 1024) {
        sidebar.classList.add('collapsed');
    }

    // Toggle sidebar collapse (Desktop only)
    function toggleSidebarCollapse() {
        if (window.innerWidth <= 1024) return; // Don't collapse on mobile
        
        sidebar.classList.toggle('collapsed');
        const collapsed = sidebar.classList.contains('collapsed');
        
        // Save state to localStorage
        localStorage.setItem('sidebarCollapsed', collapsed);
        
        // Update tooltip
        if (sidebarCollapseBtn) {
            sidebarCollapseBtn.setAttribute('title', collapsed ? 'Expand Sidebar' : 'Collapse Sidebar');
        }
    }

    // Toggle sidebar (Mobile only)
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

    // Close sidebar (Mobile only)
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

    if (sidebarCollapseBtn) {
        sidebarCollapseBtn.addEventListener('click', toggleSidebarCollapse);
    }

    // Close sidebar on ESC key (Mobile)
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });

    // Close sidebar when clicking on navigation links (Mobile only)
    const navLinks = sidebar.querySelectorAll('nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) {
                // Small delay to allow navigation
                setTimeout(closeSidebar, 200);
            }
        });
    });

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            // Close mobile sidebar on large screens if open
            if (window.innerWidth > 1024 && sidebar.classList.contains('active')) {
                closeSidebar();
            }
            // Remove collapsed class on mobile
            if (window.innerWidth <= 1024 && sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
            }
            // Restore collapsed state on desktop
            if (window.innerWidth > 1024 && localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
        }, 250);
    });
});