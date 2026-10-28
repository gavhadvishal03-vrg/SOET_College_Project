/**
 * SOET Admin Portal — Core Interactions Script
 *
 * Manages administrative dashboard UI behaviors:
 * - Persistent sidebar collapse/expand toggle via localStorage
 * - Automatic auto-dismiss timer for flash alert banners (5s)
 * - Safe confirmation prompts on destructive delete operations (.btn-delete-confirm)
 * - Real-time broken image detection and context-aware fallback placeholder assignment
 */

document.addEventListener('DOMContentLoaded', function () {
    // Sidebar collapse toggle handler
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const isCollapsedStored = localStorage.getItem('soet_admin_sidebar_collapsed') === 'true';

    if (isCollapsedStored) {
        document.body.classList.add('sidebar-collapsed');
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-collapsed');
            const isNowCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('soet_admin_sidebar_collapsed', isNowCollapsed);
        });
    }

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });

    // Delete confirmation helper
    document.querySelectorAll('.btn-delete-confirm').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to permanently delete this record? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Admin Image Auto-Fallback & Error Handling
    function handleAdminImgError(img) {
        if (img.dataset.fallbackApplied) return;
        img.dataset.fallbackApplied = "true";
        const src = (img.getAttribute('src') || '').toLowerCase();
        const alt = (img.getAttribute('alt') || '').toLowerCase();
        const className = (img.className || '').toLowerCase();

        const appUrl = (typeof window.APP_URL !== 'undefined' && window.APP_URL)
            ? window.APP_URL.replace(/\/$/, '')
            : (window.location.origin + (window.location.pathname.startsWith('/project') ? '/project' : ''));

        if (src.includes('faculty') || alt.includes('faculty') || alt.includes('director') || alt.includes('student') || className.includes('rounded-circle')) {
            img.src = appUrl + '/assets/images/default_avatar.svg';
        } else if (src.includes('dept') || alt.includes('department') || alt.includes('building')) {
            img.src = appUrl + '/assets/images/default_building.svg';
        } else {
            img.src = appUrl + '/assets/images/default_banner.svg';
        }
    }

    document.querySelectorAll('img').forEach(function(img) {
        img.addEventListener('error', function() {
            handleAdminImgError(this);
        });
        if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) {
            handleAdminImgError(img);
        }
    });
});
