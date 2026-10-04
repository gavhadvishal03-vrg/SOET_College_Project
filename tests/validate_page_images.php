<?php
// Mock server environment to simulate web request
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_SERVER['REQUEST_URI'] = '/project/';
$_SERVER['SCRIPT_NAME'] = '/project/test.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../includes/functions.php';

$pages = [
    'index.php',
    'about.php',
    'courses.php',
    'departments.php',
    'faculty.php',
    'gallery.php',
    'placements.php',
    'news.php',
    'blogs.php',
    'contact.php',
    'admissions.php'
];

$root = dirname(__DIR__);

echo "=======================================================\n";
echo "VALIDATING ALL IMAGES RENDERED ON PUBLIC WEB PAGES\n";
echo "=======================================================\n\n";

$allOk = true;

foreach ($pages as $page) {
    echo "--- Testing Page: $page ---\n";
    $_SERVER['SCRIPT_NAME'] = '/project/' . $page;
    
    // Capture page output
    ob_start();
    try {
        include $root . '/' . $page;
    } catch (Throwable $e) {
        echo "  [ERROR RENDERING] " . $e->getMessage() . "\n";
    }
    $html = ob_get_clean();

    // 1. Find all <img src="...">
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $imgMatches);
    $imgSources = $imgMatches[1] ?? [];

    // 2. Find all url('...') in inline styles or style attributes
    preg_match_all('/url\(["\']?([^"\'\)]+)["\']?\)/i', $html, $urlMatches);
    $urlSources = $urlMatches[1] ?? [];

    $allSources = array_unique(array_merge($imgSources, $urlSources));
    
    echo "  Found " . count($allSources) . " image / background resources\n";

    foreach ($allSources as $src) {
        // Skip data URIs or external font URLs
        if (str_starts_with($src, 'data:') || str_contains($src, 'fonts.googleapis') || str_contains($src, 'fonts.gstatic') || str_contains($src, 'cdnjs.cloudflare')) {
            continue;
        }

        // Check if external Unsplash or other URL
        if (preg_match('#^https?://#i', $src)) {
            if (str_contains($src, 'localhost')) {
                // Parse relative path from localhost URL
                $pathPart = parse_url($src, PHP_URL_PATH);
                // Strip /project
                $diskRel = preg_replace('#^/project/#i', '', $pathPart);
                $diskRel = ltrim($diskRel, '/');
                $diskFile = $root . '/' . $diskRel;
                if (file_exists($diskFile)) {
                    // OK
                } else {
                    echo "  [BROKEN LOCAL IMAGE]: $src (File not on disk: $diskRel)\n";
                    $allOk = false;
                }
            } else {
                echo "  [EXTERNAL IMAGE URL]: $src\n";
            }
        } else {
            $diskRel = ltrim($src, '/');
            $diskRel = preg_replace('#^project/#i', '', $diskRel);
            $diskFile = $root . '/' . $diskRel;
            if (file_exists($diskFile)) {
                // OK
            } else {
                echo "  [BROKEN RELATIVE IMAGE]: $src (File not on disk: $diskRel)\n";
                $allOk = false;
            }
        }
    }
    echo "\n";
}

if ($allOk) {
    echo "SUCCESS: All local images and assets exist and resolve perfectly!\n";
} else {
    echo "WARNING: Some images are missing or broken!\n";
}
