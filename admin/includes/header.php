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
</head>
<body class="bg-light">

<!-- Top navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2" style="border-bottom: 3px solid var(--secondary-color);">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo APP_URL; ?>/admin/dashboard.php">
            <img src="<?php echo APP_URL; ?>/assets/images/logo.svg" alt="MGMU Logo" class="me-3" style="height: 52px; object-fit: contain;">
            <div>
                <span class="d-block lh-1 text-uppercase" style="font-size: 20px; font-weight: 800; color: var(--secondary-color); letter-spacing: 1px; font-family: 'Outfit', sans-serif;">SOET Admin</span>
                <small class="text-white opacity-75" style="font-size: 9px; font-weight: 500; font-family: 'Inter', sans-serif;">Role: <?php echo htmlspecialchars($user['role_name']); ?></small>
            </div>
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="text-white d-none d-md-block text-end">
                <small class="d-block lh-1 font-semibold text-warning"><?php echo htmlspecialchars($user['full_name']); ?></small>
                <small class="opacity-75" style="font-size: 11px;"><?php echo htmlspecialchars($user['email']); ?></small>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="width: 35px; height: 35px;">
                    <i class="fa-regular fa-user"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userMenu">
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
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4" style="min-height: calc(100vh - 60px);">
            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show shadow-sm small" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
