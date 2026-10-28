<?php
/**
 * Admin Panel Top Header & Navigation Layout
 *
 * Renders the top administrator navbar:
 * - Enforces active session login guard (Auth::requireLogin)
 * - Restricts non-administrative roles (e.g. Students) from accessing the control panel
 * - Displays logged in user profile, role badge, quick links to public site, and logout action
 * - Contains sidebar collapse toggle trigger
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requireLogin();

if (Auth::hasRole('Student')) {
    setFlash('danger', 'Access denied to Admin Control Panel.');
    redirect(APP_URL . '/index.php');
}

$user = Auth::user();
$current_admin_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOET Control Panel | <?php echo APP_SHORT; ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Custom stylesheet -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <script>
        window.APP_URL = "<?php echo APP_URL; ?>";
    </script>
</head>
<body class="bg-light">

<!-- Top navbar -->
<nav class="navbar navbar-expand-lg navbar-dark admin-top-navbar sticky-top">
    <div class="container-fluid px-3 px-md-4">
        <div class="d-flex align-items-center">
            <button class="btn btn-sm btn-outline-light me-3 d-none d-md-inline-flex align-items-center justify-content-center" id="sidebarToggleBtn" style="width: 34px; height: 34px; border-radius: 8px; border-color: rgba(255,255,255,0.2);" title="Toggle Sidebar (Expand/Collapse)">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center brand-highlight-link" href="<?php echo APP_URL; ?>/admin/dashboard.php">
                <div class="brand-logo-frame me-2">
                    <img src="<?php echo APP_URL; ?>/assets/images/logo.svg" alt="MGMU Logo" class="brand-logo-img" style="height: 38px;">
                </div>
                <div class="brand-text-block">
                    <span class="d-block lh-1 text-uppercase brand-acronym" style="font-size: 19px;">SOET Admin</span>
                    <small class="text-white-50" style="font-size: 14px; font-weight: 500;">Role: <?php echo htmlspecialchars($user['role_name']); ?></small>
                </div>
            </a>
        </div>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="text-white d-none d-md-block text-end">
                <small class="d-block lh-1 font-semibold text-warning" style="font-size: 15px;"><?php echo htmlspecialchars($user['full_name']); ?></small>
                <small class="opacity-75" style="font-size: 14px;"><?php echo htmlspecialchars($user['email']); ?></small>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px; border-color: rgba(255,255,255,0.25);">
                    <i class="fa-regular fa-user"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-0" aria-labelledby="userMenu" style="border-radius: 12px; background: #1e293b;">
                    <li><a class="dropdown-item py-2 px-3 text-white font-semibold small d-flex align-items-center" href="<?php echo APP_URL; ?>/index.php" target="_blank"><i class="fa-solid fa-globe me-2 text-warning"></i> View Website</a></li>
                    <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                    <li><a class="dropdown-item py-2 px-3 text-danger font-bold small d-flex align-items-center" href="<?php echo APP_URL; ?>/admin/logout.php"><i class="fa-solid fa-right-from-bracket me-2 text-danger"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 admin-main-content" style="min-height: calc(100vh - 65px);">
            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show shadow-sm small" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
