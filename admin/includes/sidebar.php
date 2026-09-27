<?php
// Sidebar Navigation - dynamically shows links based on RBAC permissions
$current_sidebar_page = basename($_SERVER['PHP_SELF']);
?>
<div class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse px-0" id="sidebarMenu">
    <div class="position-sticky pt-2 pb-4">
        <ul class="nav flex-column">
            <!-- Core Menu -->
            <li class="sidebar-heading"><span>Main Overview</span></li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_sidebar_page === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
                </a>
            </li>

            <!-- Users Allocation -->
            <?php if (Auth::hasRole('Super Admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'users.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/users.php">
                        <i class="fa-solid fa-users-gear"></i> <span>User Allocation</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Academics & Admissions Section -->
            <li class="sidebar-heading mt-2"><span>Academics & Admissions</span></li>

            <!-- Admission Review -->
            <?php if (Auth::hasPermission('manage_admissions')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'admissions.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/admissions.php">
                        <i class="fa-solid fa-id-card"></i> <span>Admissions Portal</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Departments CRUD -->
            <?php if (Auth::hasPermission('manage_departments')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'departments.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/departments.php">
                        <i class="fa-solid fa-network-wired"></i> <span>Departments</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Courses CRUD -->
            <?php if (Auth::hasPermission('manage_courses')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'courses.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/courses.php">
                        <i class="fa-solid fa-book-open"></i> <span>Courses</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Faculty CRUD -->
            <?php if (Auth::hasPermission('manage_faculty')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'faculty.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/faculty.php">
                        <i class="fa-solid fa-user-tie"></i> <span>Faculty Directory</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Fee Configuration -->
            <?php if (Auth::hasPermission('manage_fees')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'fees.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/fees.php">
                        <i class="fa-solid fa-indian-rupee-sign"></i> <span>Fee Structure</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Placements cell -->
            <?php if (Auth::hasPermission('manage_placements')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'placements.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/placements.php">
                        <i class="fa-solid fa-briefcase"></i> <span>Placements Cell</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Campus Life & Content -->
            <li class="sidebar-heading mt-2"><span>Campus & Media</span></li>

            <!-- Events CRUD -->
            <?php if (Auth::hasPermission('manage_events')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'events.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/events.php">
                        <i class="fa-solid fa-calendar-days"></i> <span>Events Calendar</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Gallery CRUD -->
            <?php if (Auth::hasPermission('manage_gallery')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'gallery.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/gallery.php">
                        <i class="fa-solid fa-images"></i> <span>Campus Gallery</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Notice Announcements -->
            <?php if (Auth::hasPermission('manage_notices')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'notices.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/notices.php">
                        <i class="fa-solid fa-bullhorn"></i> <span>Notice Board</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Blog approval -->
            <?php if (Auth::hasPermission('approve_blogs') || Auth::hasPermission('submit_blogs')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'blogs.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/blogs.php">
                        <i class="fa-solid fa-pen-nib"></i> <span>Blog Reviews</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- News approval -->
            <?php if (Auth::hasPermission('approve_news') || Auth::hasPermission('submit_news')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'news.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/news.php">
                        <i class="fa-solid fa-newspaper"></i> <span>News Reviews</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Contact Messages -->
            <?php if (Auth::hasPermission('view_contacts')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'contacts.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/contacts.php">
                        <i class="fa-solid fa-envelope-open-text"></i> <span>Contact Inbox</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Intelligence & System -->
            <li class="sidebar-heading mt-2"><span>AI & System Control</span></li>

            <!-- Chatbot Control Suite -->
            <?php if (Auth::hasPermission('manage_chatbot_kb')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'] ?? '', 'ai-chatbot') !== false || $current_sidebar_page === 'chatbot.php') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/ai-chatbot/index.php">
                        <i class="fa-solid fa-robot"></i> <span>CampusAI Suite</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Visitors Tracking -->
            <?php if (Auth::hasPermission('view_visitors')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'visitors.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/visitors.php">
                        <i class="fa-solid fa-users-viewfinder"></i> <span>Visitor Analytics</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Activity & Security Logs -->
            <?php if (Auth::hasPermission('manage_users') || Auth::hasRole('Super Admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'logs.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/logs.php">
                        <i class="fa-solid fa-shield-halved"></i> <span>Audit Logs</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- DB backup & navigation -->
            <?php if (Auth::hasPermission('backup_restore')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'backup.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/backup.php">
                        <i class="fa-solid fa-database"></i> <span>Backup & Restore</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'navigation.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/navigation.php">
                        <i class="fa-solid fa-compass"></i> <span>Navigation Menu</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'settings.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/modules/settings.php">
                        <i class="fa-solid fa-gears"></i> <span>Portal Settings</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
