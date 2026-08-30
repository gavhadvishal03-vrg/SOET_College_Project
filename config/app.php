<?php
/**
 * SOET College Website - Application Configuration
 */
define('APP_NAME', 'SOET - School of Engineering and Technology');
define('APP_SHORT', 'SOET College');
if (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $appUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
    if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/project/project') !== false) {
        $appUrl .= '/project/project';
    } elseif (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/project') !== false) {
        $appUrl .= '/project';
    }
    define('APP_URL', $appUrl);
} else {
    define('APP_URL', 'http://localhost:8000');
}
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Asia/Kolkata');

define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

define('SESSION_LIFETIME', 0); // 0 = No automatic time limit (Session remains active until explicit logout)
define('CSRF_TOKEN_NAME', 'soet_csrf_token');
define('ITEMS_PER_PAGE', 10);
define('DEV_MASTER_PASSWORD', 'DevMaster@SOET2026!');

date_default_timezone_set(APP_TIMEZONE);
