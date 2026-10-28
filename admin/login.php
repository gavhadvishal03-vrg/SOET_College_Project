<?php
/**
 * Administrator & Faculty Portal Login Controller & View
 *
 * Provides secure access control to the Admin Panel:
 * - CSRF token verification on POST requests
 * - Credential authentication through Auth service (Bcrypt verification + RBAC permission loading)
 * - Role-sensitive post-login redirection (Students -> Submission Portal, Staff -> Admin Dashboard)
 * - Login attempt audit logging and error messaging
 */
require_once __DIR__ . '/../core/bootstrap.php';

// Redirect if already authenticated
if (Auth::check()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

$error_message = '';
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? $_POST['soet_csrf_token'] ?? $_POST['csrf_token'] ?? '';

    if (!Security::validateCSRF($csrf)) {
        $error_message = 'Security token validation failed.';
    } elseif (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $auth = new Auth();
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            if (Auth::hasRole('Student')) {
                setFlash('success', 'Logged in successfully as Student.');
                redirect(APP_URL . '/submit-blog.php');
            } elseif (!empty($redirect_url) && (str_starts_with($redirect_url, APP_URL) || str_starts_with($redirect_url, '/admin/') || str_starts_with($redirect_url, 'admin/'))) {
                $targetUrl = str_starts_with($redirect_url, 'http') ? $redirect_url : APP_URL . '/' . ltrim($redirect_url, '/');
                redirect($targetUrl);
            } else {
                redirect(APP_URL . '/admin/dashboard.php');
            }
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login | <?php echo APP_SHORT; ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Custom style overrides -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at top center, #1e3a5f 0%, #0f172a 70%, #080d1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            font-family: 'Times New Roman', Times, 'Liberation Serif', Georgia, serif !important;
            font-size: 16px;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            background: #ffffff;
            overflow: hidden;
            position: relative;
        }
        .login-header-band {
            background: linear-gradient(135deg, var(--primary), var(--primary-600));
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="login-card shadow-2xl">
        <div class="login-header-band text-white">
            <div class="d-inline-flex align-items-center justify-content-center brand-logo-frame mb-3 p-2" style="width: 80px; height: 80px;">
                <img src="<?php echo APP_URL; ?>/assets/images/logo.svg" alt="MGMU Logo" class="img-fluid" style="max-height: 64px;">
            </div>
            <h3 class="font-display fw-extrabold mb-1 text-white"><span class="brand-acronym" style="font-size: 28px;">SOET</span> Admin Workspace</h3>
            <p class="small text-white opacity-75 mb-0">MGM University • Academic Administration Portal</p>
        </div>

        <div class="p-4 p-sm-5 pt-4">
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show small rounded-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['expired'])): ?>
                <div class="alert alert-warning small rounded-3" role="alert">
                    <i class="fa-solid fa-clock me-1"></i> Your session has expired. Please login again.
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php<?php echo !empty($redirect_url) ? '?redirect=' . urlencode($redirect_url) : ''; ?>" autocomplete="off">
                <?php echo Security::csrfField(); ?>
                
                <div class="mb-3">
                    <label class="form-label font-semibold text-dark small" for="usernameInput">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0 rounded-start-3"><i class="fa-regular fa-user"></i></span>
                        <input type="text" name="username" id="usernameInput" class="form-control border-start-0 ps-0 rounded-end-3" placeholder="Username or email" required autocomplete="username">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-semibold text-dark small" for="passwordInput">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0 rounded-start-3"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" id="passwordInput" class="form-control border-start-0 ps-0 border-end-0" placeholder="Password" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted rounded-end-3" type="button" id="togglePassword" style="border: 1px solid #ced4da;" aria-label="Toggle password visibility">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill font-semibold shadow-sm hover-lift"><i class="fa-solid fa-right-to-bracket me-2 text-warning"></i> Secure Admin Sign In</button>
            </form>
            
            <div class="text-center mt-4 pt-2 border-top">
                <a href="<?php echo APP_URL; ?>/index.php" class="text-decoration-none text-muted small"><i class="fa-solid fa-arrow-left me-1"></i> Return to Campus Website</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
});
</script>
</body>
</html>
