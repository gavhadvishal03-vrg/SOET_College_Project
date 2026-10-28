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
if (isset($_SERVER['HTTP_HOST']) || isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    // Detect HTTPS directly or when forwarded by reverse proxy / VS Code Port Forwarding tunnel
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
        (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') ||
        (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PORT']) && (int)$_SERVER['HTTP_X_FORWARDED_PORT'] === 443)
    );
    $scheme = $isHttps ? 'https' : 'http';

    // VS Code Port Forwarding (Dev Tunnels) sends the public tunnel hostname in HTTP_X_FORWARDED_HOST
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    if (str_contains($host, ',')) {
        $host = trim(explode(',', $host)[0]);
    }

    $appUrl = $scheme . '://' . $host;
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    
    // Normalize script directory across Windows (\) and Unix (/) paths
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '') {
        $baseDir = preg_replace('#/(admin|api|chatbot|tests|includes|core)(/.*)?$#i', '', $scriptDir);
        $baseDir = rtrim($baseDir, '/');
        if ($baseDir !== '' && $baseDir !== '/') {
            $appUrl .= $baseDir;
        }
    }
    define('APP_URL', rtrim($appUrl, '/\\'));
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

define('MAX_UPLOAD_SIZE', 40 * 1024 * 1024); // 40MB maximum file size (matches PHP upload_max_filesize)
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', [
    'application/pdf',
    'application/x-pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png'
]);

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
