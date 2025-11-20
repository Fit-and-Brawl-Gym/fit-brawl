document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger-menu');
    const navBar = document.querySelector('.nav-bar');

    if (!hamburger || !navBar) return;

    // Create mobile overlay/panel elements (created on demand)
    function createMobileNav() {
        // Overlay
        const overlay = document.createElement('div');
        overlay.className = 'mobile-nav-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');

        // Panel
        const panel = document.createElement('aside');
        panel.className = 'mobile-nav-panel';
        panel.setAttribute('tabindex', '-1');

        // Clone navBar contents into panel
        // Use a shallow clone of the innerHTML to preserve links and structure
        panel.innerHTML = navBar.innerHTML;

        overlay.appendChild(panel);
        document.body.appendChild(overlay);

        // Add close handler when clicking overlay outside panel
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeMobileNav(overlay);
            }
        });

        // Close on ESC
        function onKey(e) {
            if (e.key === 'Escape') closeMobileNav(overlay);
        }
        document.addEventListener('keydown', onKey);

        // Close when links inside panel are clicked
        panel.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', function() {
                closeMobileNav(overlay);
            });
        });

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Focus the panel for accessibility
        window.setTimeout(() => panel.focus(), 50);

        return { overlay, onKey };
    }

    function openMobileNav() {
        // If overlay already exists, do nothing
        if (document.querySelector('.mobile-nav-overlay')) return;
        const { overlay } = createMobileNav();
        // allow CSS transition
        window.requestAnimationFrame(() => overlay.classList.add('open'));
        hamburger.classList.add('active');
    }

    function closeMobileNav(overlay) {
        const el = overlay || document.querySelector('.mobile-nav-overlay');
        if (!el) return;
        el.classList.remove('open');
        hamburger.classList.remove('active');
        document.body.style.overflow = '';
        // Remove overlay after transition
        el.addEventListener('transitionend', function onEnd() {
            el.removeEventListener('transitionend', onEnd);
            if (el.parentNode) el.parentNode.removeChild(el);
        });
    }

    // Toggle when hamburger clicked
    hamburger.addEventListener('click', function(e) {
        e.stopPropagation();
        const overlay = document.querySelector('.mobile-nav-overlay');
        if (overlay) {
            closeMobileNav(overlay);
        } else {
            openMobileNav();
        }
    });

    // Close if window resized to desktop width
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            const overlay = document.querySelector('.mobile-nav-overlay');
            if (overlay) closeMobileNav(overlay);
        }
    });
});
