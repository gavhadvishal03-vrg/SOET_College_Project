<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_SERVER['REQUEST_URI'] = '/project/admin/';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../includes/functions.php';

Session::start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['name'] = 'Admin';

$adminPages = [
    'admin/index.php',
    'admin/login.php',
    'admin/modules/courses.php',
    'admin/modules/departments.php',
    'admin/modules/faculty.php',
    'admin/modules/gallery.php',
    'admin/modules/news.php',
    'admin/modules/blogs.php',
    'admin/modules/settings.php',
    'admin/modules/placements.php',
];

$root = dirname(__DIR__);

echo "=======================================================\n";
echo "VALIDATING ALL ADMIN PAGES IMAGE RENDERING\n";
echo "=======================================================\n\n";

$allOk = true;

foreach ($adminPages as $page) {
    echo "--- Testing Admin Page: $page ---\n";
    $_SERVER['SCRIPT_NAME'] = '/project/' . $page;
    
    ob_start();
    try {
        include $root . '/' . $page;
    } catch (Throwable $e) {
        echo "  [ERROR RENDERING] " . $e->getMessage() . "\n";
    }
    $html = ob_get_clean();

    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $imgMatches);
    $imgSources = $imgMatches[1] ?? [];

    preg_match_all('/url\(["\']?([^"\'\)]+)["\']?\)/i', $html, $urlMatches);
    $urlSources = $urlMatches[1] ?? [];

    $allSources = array_unique(array_merge($imgSources, $urlSources));
    echo "  Found " . count($allSources) . " image resources\n";

    foreach ($allSources as $src) {
        if (str_starts_with($src, 'data:') || str_contains($src, 'fonts.googleapis') || str_contains($src, 'fonts.gstatic') || str_contains($src, 'cdnjs.cloudflare')) {
            continue;
        }

        if (preg_match('#^https?://#i', $src)) {
            if (str_contains($src, 'localhost')) {
                $pathPart = parse_url($src, PHP_URL_PATH);
                $diskRel = preg_replace('#^/project/#i', '', $pathPart);
                $diskRel = ltrim($diskRel, '/');
                $diskFile = $root . '/' . $diskRel;
                if (!file_exists($diskFile)) {
                    echo "  [BROKEN ADMIN IMAGE]: $src (File not on disk: $diskRel)\n";
                    $allOk = false;
                }
            }
        } else {
            $diskRel = ltrim($src, '/');
            $diskRel = preg_replace('#^project/#i', '', $diskRel);
            $diskFile = $root . '/' . $diskRel;
            if (!file_exists($diskFile)) {
                echo "  [BROKEN ADMIN IMAGE]: $src (File not on disk: $diskRel)\n";
                $allOk = false;
            }
        }
    }
    echo "\n";
}

if ($allOk) {
    echo "SUCCESS: All admin panel images and assets exist and resolve perfectly!\n";
} else {
    echo "WARNING: Some admin images are broken!\n";
}
