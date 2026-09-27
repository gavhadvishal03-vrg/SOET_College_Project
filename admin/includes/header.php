<?php
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom stylesheet -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Top navbar -->
<nav class="navbar navbar-expand-lg navbar-dark admin-top-navbar sticky-top">
    <div class="container-fluid px-3 px-md-4">
        <div class="d-flex align-items-center">
            <button class="btn btn-sm btn-outline-light me-3 d-none d-md-inline-flex align-items-center justify-content-center" id="sidebarToggleBtn" style="width: 34px; height: 34px; border-radius: 8px; border-color: rgba(255,255,255,0.2);" title="Toggle Sidebar (Expand/Collapse)">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="<?php echo APP_URL; ?>/admin/dashboard.php">
                <img src="<?php echo APP_URL; ?>/assets/images/logo.svg" alt="MGMU Logo" class="me-2" style="height: 42px; object-fit: contain;">
                <div>
                    <span class="d-block lh-1 text-uppercase" style="font-size: 18px; font-weight: 800; color: var(--accent); letter-spacing: 0.5px; font-family: 'Outfit', sans-serif;">SOET Admin</span>
                    <small class="text-white-50" style="font-size: 10px; font-weight: 500; font-family: 'Inter', sans-serif;">Role: <?php echo htmlspecialchars($user['role_name']); ?></small>
                </div>
            </a>
        </div>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="text-white d-none d-md-block text-end">
                <small class="d-block lh-1 font-semibold text-warning"><?php echo htmlspecialchars($user['full_name']); ?></small>
                <small class="opacity-75" style="font-size: 11px;"><?php echo htmlspecialchars($user['email']); ?></small>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px; border-color: rgba(255,255,255,0.25);">
                    <i class="fa-regular fa-user"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="userMenu" style="border-radius: 12px;">
                    <li><a class="dropdown-item py-2 small" href="<?php echo APP_URL; ?>/index.php" target="_blank"><i class="fa-solid fa-globe me-2 text-muted"></i> View Website</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 small text-danger fw-bold" href="<?php echo APP_URL; ?>/admin/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
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
