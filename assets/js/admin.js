// SOET Admin Portal — Core Interactions JS
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
});
