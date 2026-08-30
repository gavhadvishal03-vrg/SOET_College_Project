<?php
// Sidebar Navigation - dynamically shows links based on RBAC permissions
$current_sidebar_page = basename($_SERVER['PHP_SELF']);
?>
<div class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse px-0 bg-dark" id="sidebarMenu">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <!-- Dashboard Link -->
            <li class="nav-item">
                <a class="nav-link <?php echo $current_sidebar_page === 'dashboard.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/dashboard.php">
                    <i class="fa-solid fa-gauge me-2 text-warning"></i> Dashboard
                </a>
            </li>

            <!-- Users CRUD -->
            <?php if (Auth::hasRole('Super Admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'users.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/users.php">
                        <i class="fa-solid fa-users me-2 text-warning"></i> User Allocation
                    </a>
                </li>
            <?php endif; ?>

            <!-- Departments CRUD -->
            <?php if (Auth::hasPermission('manage_departments')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'departments.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/departments.php">
                        <i class="fa-solid fa-network-wired me-2 text-warning"></i> Departments
                    </a>
                </li>
            <?php endif; ?>

            <!-- Courses CRUD -->
            <?php if (Auth::hasPermission('manage_courses')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'courses.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/courses.php">
                        <i class="fa-solid fa-book-open me-2 text-warning"></i> Courses
                    </a>
                </li>
            <?php endif; ?>

            <!-- Faculty CRUD -->
            <?php if (Auth::hasPermission('manage_faculty')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'faculty.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/faculty.php">
                        <i class="fa-solid fa-user-tie me-2 text-warning"></i> Faculty Directory
                    </a>
                </li>
            <?php endif; ?>

            <!-- Fee Configuration -->
            <?php if (Auth::hasPermission('manage_fees')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'fees.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/fees.php">
                        <i class="fa-solid fa-indian-rupee-sign me-2 text-warning"></i> Fee Structure
                    </a>
                </li>
            <?php endif; ?>

            <!-- Admission Review -->
            <?php if (Auth::hasPermission('manage_admissions')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'admissions.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/admissions.php">
                        <i class="fa-solid fa-id-card me-2 text-warning"></i> Admissions Portal
                    </a>
                </li>
            <?php endif; ?>

            <!-- Events CRUD -->
            <?php if (Auth::hasPermission('manage_events')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'events.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/events.php">
                        <i class="fa-solid fa-calendar-days me-2 text-warning"></i> Events Calendar
                    </a>
                </li>
            <?php endif; ?>

            <!-- Gallery CRUD -->
            <?php if (Auth::hasPermission('manage_gallery')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'gallery.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/gallery.php">
                        <i class="fa-solid fa-images me-2 text-warning"></i> Campus Gallery
                    </a>
                </li>
            <?php endif; ?>

            <!-- Placements cell -->
            <?php if (Auth::hasPermission('manage_placements')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'placements.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/placements.php">
                        <i class="fa-solid fa-briefcase me-2 text-warning"></i> Placements Cell
                    </a>
                </li>
            <?php endif; ?>

            <!-- Notice Announcements -->
            <?php if (Auth::hasPermission('manage_notices')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'notices.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/notices.php">
                        <i class="fa-solid fa-bullhorn me-2 text-warning"></i> Notice Board
                    </a>
                </li>
            <?php endif; ?>

            <!-- Contact Messages -->
            <?php if (Auth::hasPermission('view_contacts')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'contacts.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/contacts.php">
                        <i class="fa-solid fa-envelope-open-text me-2 text-warning"></i> Contact Inbox
                    </a>
                </li>
            <?php endif; ?>

            <!-- Blog approval -->
            <?php if (Auth::hasPermission('approve_blogs') || Auth::hasPermission('submit_blogs')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'blogs.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/blogs.php">
                        <i class="fa-solid fa-pen-nib me-2 text-warning"></i> Blog Reviews
                    </a>
                </li>
            <?php endif; ?>

            <!-- News approval -->
            <?php if (Auth::hasPermission('approve_news') || Auth::hasPermission('submit_news')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'news.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/news.php">
                        <i class="fa-solid fa-newspaper me-2 text-warning"></i> News Reviews
                    </a>
                </li>
            <?php endif; ?>

            <!-- Visitors Tracking -->
            <?php if (Auth::hasPermission('view_visitors')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'visitors.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/visitors.php">
                        <i class="fa-solid fa-users-viewfinder me-2 text-warning"></i> Visitor Analytics
                    </a>
                </li>
            <?php endif; ?>

            <!-- Chatbot Control Suite -->
            <?php if (Auth::hasPermission('manage_chatbot_kb')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'] ?? '', 'ai-chatbot') !== false || $current_sidebar_page === 'chatbot.php') ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/ai-chatbot/index.php">
                        <i class="fa-solid fa-robot me-2 text-warning"></i> AI Chatbot Suite
                    </a>
                </li>
            <?php endif; ?>

            <!-- Activity & Security Logs -->
            <?php if (Auth::hasPermission('manage_users') || Auth::hasRole('Super Admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'logs.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/logs.php">
                        <i class="fa-solid fa-shield-halved me-2 text-warning"></i> Audit & Activity Logs
                    </a>
                </li>
            <?php endif; ?>

            <!-- DB backup -->
            <?php if (Auth::hasPermission('backup_restore')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'backup.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/backup.php">
                        <i class="fa-solid fa-database me-2 text-warning"></i> Backup & Restore
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_sidebar_page === 'settings.php' ? 'active text-white' : 'text-white-50'; ?>" href="<?php echo APP_URL; ?>/admin/modules/settings.php">
                        <i class="fa-solid fa-gears me-2 text-warning"></i> Portal Settings
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
