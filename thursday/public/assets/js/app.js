document.addEventListener('DOMContentLoaded', function () {
    // Mobile sidebar toggle: hamburger <-> close icon, backdrop, tap-outside
    // to close, close on nav-link click, and lock body scroll while open.
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarClose = document.getElementById('sidebarClose');
    const mobileMenuQuery = window.matchMedia('(max-width: 991px)');

    function openMobileSidebar() {
        sidebar.classList.add('show');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
        document.body.classList.add('sidebar-open');
        if (sidebarToggleIcon) {
            sidebarToggleIcon.classList.remove('bi-list');
            sidebarToggleIcon.classList.add('bi-x-lg');
        }
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'true');
    }

    function closeMobileSidebar() {
        sidebar.classList.remove('show');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        if (sidebarToggleIcon) {
            sidebarToggleIcon.classList.remove('bi-x-lg');
            sidebarToggleIcon.classList.add('bi-list');
        }
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            if (sidebar.classList.contains('show')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        });

        // Tap outside the menu (on the backdrop) closes it.
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', closeMobileSidebar);
        }

        // The "X" button inside the sidebar itself closes the menu.
        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeMobileSidebar);
        }

        // Clicking a nav link closes the menu on mobile so the page
        // underneath is immediately visible after navigating.
        sidebar.querySelectorAll('a.nav-link:not([data-bs-toggle="collapse"])').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileMenuQuery.matches) {
                    closeMobileSidebar();
                }
            });
        });

        // If the viewport is resized/rotated past the mobile breakpoint
        // while the menu is open, reset everything to the desktop state.
        mobileMenuQuery.addEventListener('change', function (e) {
            if (!e.matches) {
                closeMobileSidebar();
            }
        });

        // Escape key closes the menu too.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                closeMobileSidebar();
            }
        });
    }

    // Sidebar scroll position: every navigation here is a full page load, so
    // without this the sidebar always snaps back to the top. Restore first
    // (before paint-triggering work), then keep saving as the user scrolls
    // and right before they navigate away, so a click, a back button, or a
    // refresh all land back where they were.
    if (sidebar) {
        const scrollKey = 'school_erp_sidebar_scroll';
        const savedScroll = sessionStorage.getItem(scrollKey);
        if (savedScroll !== null) {
            sidebar.scrollTop = parseInt(savedScroll, 10) || 0;
        }
        let scrollSaveTimer;
        sidebar.addEventListener('scroll', function () {
            clearTimeout(scrollSaveTimer);
            scrollSaveTimer = setTimeout(function () {
                sessionStorage.setItem(scrollKey, String(sidebar.scrollTop));
            }, 100);
        });
        sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                sessionStorage.setItem(scrollKey, String(sidebar.scrollTop));
            });
        });
        window.addEventListener('beforeunload', function () {
            sessionStorage.setItem(scrollKey, String(sidebar.scrollTop));
        });
    }

    // Dark / light mode toggle, persisted across visits via localStorage.
    // The *initial* theme is applied synchronously in <head> (see layouts/app.php)
    // to avoid a light-mode flash on load; this just wires up the toggle button.
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('school_erp_theme', next);
        });
    }

    // NOTE: "form.confirm-delete" and "form[data-confirm]" are now handled
    // globally by sweetalert-helpers.js, which shows a SweetAlert2
    // confirmation modal instead of the native browser confirm().

    // Auto-init any table with class "data-table" as a DataTable, if present
    if (window.jQuery && jQuery.fn.DataTable) {
        jQuery('.data-table').each(function () {
            jQuery(this).DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
            });
        });
    }
});
