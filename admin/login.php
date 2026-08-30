<?php
require_once __DIR__ . '/../core/bootstrap.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

$error_message = '';
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

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
            } elseif (!empty($redirect_url) && str_starts_with($redirect_url, APP_URL)) {
                redirect($redirect_url);
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom style overrides -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--bg-dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            border: none;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            background-color: #ffffff;
            overflow: hidden;
            border-top: 5px solid var(--secondary-color);
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="login-card p-4 p-sm-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle border border-warning mb-3" style="width: 100px; height: 100px; overflow: hidden; padding: 8px;">
                <img src="<?php echo APP_URL; ?>/assets/images/logo.svg" alt="MGMU" class="img-fluid">
            </div>
            <h4 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;"><span style="color: var(--secondary-color); font-weight: 800; letter-spacing: 0.5px;">SOET</span> <span class="text-primary-color" style="font-weight: 700;">Admin Portal</span></h4>
            <small class="text-muted d-block" style="font-family: 'Inter', sans-serif; font-weight: 500; margin-top: 3px;">MGM University CMS Workspace</small>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning small" role="alert">
                <i class="fa-solid fa-clock me-1"></i> Your session has expired. Please login again.
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php<?php echo !empty($redirect_url) ? '?redirect=' . urlencode($redirect_url) : ''; ?>" autocomplete="off">
            <?php echo Security::csrfField(); ?>
            
            <div class="mb-3">
                <label class="form-label font-semibold small">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-regular fa-user"></i></span>
                    <input type="text" name="username" class="form-control border-start-0 ps-0" placeholder="Username" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label font-semibold small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control border-start-0 ps-0 border-end-0" placeholder="Password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                    <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="button" id="togglePassword" style="border: 1px solid #ced4da;">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-bold"><i class="fa-solid fa-right-to-bracket me-1"></i> Secure Login</button>
        </form>
        
        <div class="text-center mt-4">
            <a href="<?php echo APP_URL; ?>/index.php" class="text-decoration-none text-muted small"><i class="fa-solid fa-arrow-left me-1"></i> Back to public website</a>
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
