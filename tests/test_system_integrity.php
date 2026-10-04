<?php
/**
 * SOET Comprehensive System Integrity & Regression Test Suite
 */

echo "========================================================\n";
echo " SOET COLLEGE SYSTEM INTEGRITY & REGRESSION TEST SUITE\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;
$warnings = [];

function assertTest(string $name, bool $condition, string $detail = ''): void
{
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] " . $name . "\n";
        $passCount++;
    } else {
        echo "[FAIL] " . $name . " - " . $detail . "\n";
        $failCount++;
    }
}

// ---------------------------------------------------------
// 1. Bootstrap & Config Test
// ---------------------------------------------------------
echo "--- 1. Testing Core Bootstrap & Configuration ---\n";
require_once __DIR__ . '/../core/bootstrap.php';

assertTest("APP_NAME defined", defined('APP_NAME') && APP_NAME !== '');
assertTest("APP_URL defined", defined('APP_URL') && APP_URL !== '');
assertTest("UPLOAD_PATH defined and exists", defined('UPLOAD_PATH') && is_dir(UPLOAD_PATH), UPLOAD_PATH);
assertTest("UPLOAD_URL defined", defined('UPLOAD_URL') && UPLOAD_URL !== '');
assertTest("ASSETS_PATH defined and exists", defined('ASSETS_PATH') && is_dir(ASSETS_PATH), ASSETS_PATH);
assertTest("DOCS_PATH defined and exists", defined('DOCS_PATH') && is_dir(DOCS_PATH), DOCS_PATH);

// ---------------------------------------------------------
// 2. Database Connectivity & Table Check
// ---------------------------------------------------------
echo "\n--- 2. Testing Database Connectivity & Schema ---\n";
$db = Database::getInstance();
$pdo = $db->getConnection();
assertTest("Database Singleton Instance", $db instanceof Database);
assertTest("PDO Connection Alive", $pdo instanceof PDO);

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$expectedTables = [
    'activity_logs', 'admissions', 'blogs', 'chat_feedback', 'chat_messages',
    'chat_sessions', 'chatbot_categories', 'chatbot_settings', 'contact_messages',
    'courses', 'departments', 'documents', 'event_registrations', 'events',
    'facilities', 'faculty', 'faq', 'fees', 'gallery', 'homepage_activities',
    'knowledge_base', 'navigation_menu', 'news', 'notices', 'placement_recruiters',
    'placements', 'roles', 'site_settings', 'sub_events', 'unanswered_questions',
    'uploaded_documents', 'users', 'visitors'
];

$missingTables = array_diff($expectedTables, $tables);
assertTest("All 33 Core Tables Present", empty($missingTables), "Missing: " . implode(', ', $missingTables));

// ---------------------------------------------------------
// 3. Upload Directories Integrity Check
// ---------------------------------------------------------
echo "\n--- 3. Testing Upload Storage Directories ---\n";
$expectedUploadDirs = [
    'admissions', 'blogs', 'courses', 'departments', 'documents',
    'events', 'facilities', 'faculty', 'gallery', 'logos',
    'news', 'notices', 'placements', 'settings', 'students'
];
foreach ($expectedUploadDirs as $ud) {
    $dirPath = UPLOAD_PATH . $ud;
    assertTest("Upload Subdir '{$ud}' exists & writable", is_dir($dirPath) && is_writable($dirPath), $dirPath);
}

// ---------------------------------------------------------
// 4. Media Resolution & Fallback Integrity Check
// ---------------------------------------------------------
echo "\n--- 4. Testing Media Resolution (uploadUrl) Across DB ---\n";

$mediaQueries = [
    'faculty' => ['sql' => "SELECT id, name, image_path FROM faculty", 'col' => 'image_path', 'dir' => 'faculty'],
    'courses' => ['sql' => "SELECT id, name, image_path FROM courses", 'col' => 'image_path', 'dir' => 'courses'],
    'departments' => ['sql' => "SELECT id, name, image_path FROM departments", 'col' => 'image_path', 'dir' => 'departments'],
    'events' => ['sql' => "SELECT id, title, image_path FROM events", 'col' => 'image_path', 'dir' => 'events'],
    'facilities' => ['sql' => "SELECT id, title, image_path FROM facilities", 'col' => 'image_path', 'dir' => 'facilities'],
    'gallery' => ['sql' => "SELECT id, title, image_path FROM gallery", 'col' => 'image_path', 'dir' => 'gallery'],
    'homepage_activities' => ['sql' => "SELECT id, title, image_path FROM homepage_activities", 'col' => 'image_path', 'dir' => 'events'],
    'news' => ['sql' => "SELECT id, title, image_path FROM news", 'col' => 'image_path', 'dir' => 'news'],
    'blogs' => ['sql' => "SELECT id, title, image_path FROM blogs", 'col' => 'image_path', 'dir' => 'blogs'],
];

$totalMediaChecked = 0;
$validMediaResolved = 0;

foreach ($mediaQueries as $table => $cfg) {
    $rows = $pdo->query($cfg['sql'])->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $val = $r[$cfg['col']] ?? '';
        if (!empty($val)) {
            $totalMediaChecked++;
            $url = uploadUrl($cfg['dir'], $val);
            if (!empty($url) && str_contains($url, APP_URL)) {
                $validMediaResolved++;
            }
        }
    }
}
assertTest("DB Media Resolution Rate (Checked: {$totalMediaChecked})", $validMediaResolved === $totalMediaChecked, "Resolved {$validMediaResolved}/{$totalMediaChecked}");

// ---------------------------------------------------------
// 5. System Health & CMS Repository Check
// ---------------------------------------------------------
echo "\n--- 5. Testing ContentManager Operations ---\n";
$cms = new ContentManager();
$health = $cms->getSystemHealth();
assertTest("CMS getSystemHealth returns valid array", is_array($health) && isset($health['php_version']));
assertTest("CMS getDepartments returns active departments", count($cms->getDepartments()) > 0);
assertTest("CMS getCourses returns registered programs", count($cms->getCourses()) > 0);
assertTest("CMS getFacilities returns facilities", count($cms->getFacilities()) > 0);
assertTest("CMS getCourseSeatMetrics returns seat data", count($cms->getCourseSeatMetrics()) > 0);

// ---------------------------------------------------------
// 6. Chatbot Subsystem Integration Check
// ---------------------------------------------------------
echo "\n--- 6. Testing CampusAI Chatbot Engine ---\n";
require_once __DIR__ . '/../chatbot/engine/SmartChatEngine.php';
$engine = new SmartChatEngine();
$response = $engine->processMessage("What programs are offered at SOET?", "test_session_integrity");
assertTest("Chatbot processes query successfully", isset($response['success']) && $response['success'] === true);
assertTest("Chatbot returns meaningful response text", !empty($response['raw_text']) || !empty($response['formatted_html']));

// ---------------------------------------------------------
// 7. PHP Syntax / Lint Test across all files
// ---------------------------------------------------------
echo "\n--- 7. PHP Syntax Linting across Entire Project ---\n";
$root = dirname(__DIR__);
$allPhpFiles = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $allPhpFiles[] = $file->getPathname();
    }
}

$syntaxErrors = [];
foreach ($allPhpFiles as $phpFile) {
    $cmd = "php -l " . escapeshellarg($phpFile);
    $out = shell_exec($cmd);
    if (!str_contains($out, 'No syntax errors detected')) {
        $syntaxErrors[] = $phpFile . ": " . trim($out);
    }
}
assertTest("All " . count($allPhpFiles) . " PHP files pass syntax lint", empty($syntaxErrors), implode("\n", $syntaxErrors));

// ---------------------------------------------------------
// Summary
// ---------------------------------------------------------
echo "\n========================================================\n";
echo " INTEGRITY TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
