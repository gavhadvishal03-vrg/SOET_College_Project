<?php
/**
 * SOET College Website - Application Configuration
 *
 * Establishes global constants, environment variable loading, dynamic URL
 * resolution, upload directories, security constraints, and localization settings.
 */

// -------------------------------------------------------------------------
// 1. Environment Variables Loader (.env)
// -------------------------------------------------------------------------
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$envKey, $envVal] = explode('=', $line, 2);
            $envKey = trim($envKey);
            $envVal = trim($envVal, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($envKey, $_SERVER) && !array_key_exists($envKey, $_ENV)) {
                putenv("{$envKey}={$envVal}");
                $_ENV[$envKey] = $envVal;
                $_SERVER[$envKey] = $envVal;
            }
        }
    }
}

// -------------------------------------------------------------------------
// 2. Application Branding & Identity
// -------------------------------------------------------------------------
define('APP_NAME', 'SOET - School of Engineering and Technology');
define('APP_SHORT', 'SOET College');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');

// -------------------------------------------------------------------------
// 3. Dynamic Application URL Detection
// -------------------------------------------------------------------------
if (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $appUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    
    // Strip script filename and /admin or /api subfolders to get project root URL
    $scriptDir = dirname($scriptName);
    if ($scriptDir !== '/' && $scriptDir !== '.') {
        $baseDir = preg_replace('#/(admin|api)(/.*)?$#i', '', $scriptDir);
        if ($baseDir !== '/' && $baseDir !== '') {
            $appUrl .= $baseDir;
        }
    }
    define('APP_URL', rtrim($appUrl, '/'));
} else {
    define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
}

// -------------------------------------------------------------------------
// 4. File Upload & Storage Directory Paths
// -------------------------------------------------------------------------
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

define('ASSETS_PATH', dirname(__DIR__) . '/assets/');
define('ASSETS_URL', APP_URL . '/assets/');

define('DOCS_PATH', dirname(__DIR__) . '/docs/');
define('DOCS_URL', APP_URL . '/docs/');

define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB maximum file size
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

// -------------------------------------------------------------------------
// 5. Session & Security Constants
// -------------------------------------------------------------------------
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 3600)); // 1 Hour (3600 seconds)
define('CSRF_TOKEN_NAME', getenv('CSRF_TOKEN_NAME') ?: 'soet_csrf_token');
define('ITEMS_PER_PAGE', 10);
define('DEV_MASTER_PASSWORD', getenv('DEV_MASTER_PASSWORD') ?: 'DevMaster@SOET2026!');

// -------------------------------------------------------------------------
// 6. Localization & Timezone
// -------------------------------------------------------------------------
date_default_timezone_set(APP_TIMEZONE);
