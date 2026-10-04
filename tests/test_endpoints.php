<?php
/**
 * Test Frontend Pages, Admin Pages & API Endpoints Execution
 */

require_once __DIR__ . '/../core/bootstrap.php';

$pages = [
    'index.php',
    'about.php',
    'admissions.php',
    'courses.php',
    'departments.php',
    'faculty.php',
    'placements.php',
    'gallery.php',
    'events.php',
    'news.php',
    'blogs.php',
    'contact.php',
    'submit-blog.php',
    'submit-news.php',
];

echo "========================================================\n";
echo " TESTING FRONTEND PAGES RENDERING & RUNTIME INTEGRITY\n";
echo "========================================================\n";

$root = dirname(__DIR__);

foreach ($pages as $p) {
    $cmd = "php -f " . escapeshellarg($root . '/' . $p);
    $output = shell_exec($cmd);
    $hasHtml = str_contains($output, '<html') || str_contains($output, '<!DOCTYPE') || str_contains($output, 'navbar');
    $hasErrors = preg_match('/Fatal error|Parse error|Warning:|Notice:/i', $output);
    
    if ($hasHtml && !$hasErrors) {
        echo "[PASS] Page: {$p} rendered successfully (" . strlen($output) . " bytes)\n";
    } else {
        echo "[FAIL] Page: {$p}\n";
        if ($hasErrors) {
            preg_match_all('/(Fatal error|Parse error|Warning|Notice):[^\n]+/i', $output, $m);
            echo "   Errors: " . implode("\n   ", $m[0]) . "\n";
        }
    }
}

echo "\n========================================================\n";
echo " TESTING API ENDPOINTS EXECUTION\n";
echo "========================================================\n";

$apis = [
    'api/seats.php',
    'api/quick_search.php?q=engineering',
    'chatbot/api/suggestions.php',
];

foreach ($apis as $api) {
    $parts = explode('?', $api);
    $script = $parts[0];
    $qs = $parts[1] ?? '';
    
    $cmd = "php -f " . escapeshellarg($root . '/' . $script);
    $output = shell_exec($cmd);
    $json = json_decode($output, true);
    
    if (is_array($json)) {
        echo "[PASS] API: {$script} returned valid JSON\n";
    } else {
        echo "[FAIL] API: {$script} did not return valid JSON\nOutput: " . substr($output, 0, 150) . "\n";
    }
}
