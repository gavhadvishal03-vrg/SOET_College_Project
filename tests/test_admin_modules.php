<?php
/**
 * Test Admin Panel Modules Execution with Simulated Super Admin Session
 */

require_once __DIR__ . '/../core/bootstrap.php';

// Simulate authenticated Super Admin session
Session::set('user_id', 1);
Session::set('user_role_id', 1);
Session::set('user_role', 'Super Admin');
Session::set('user_permissions', ['*']);
Session::set('user_username', 'superadmin');
Session::set('user_fullname', 'Administrator');

$adminModules = [
    'admin/dashboard.php',
    'admin/modules/admissions.php',
    'admin/modules/backup.php',
    'admin/modules/blogs.php',
    'admin/modules/contacts.php',
    'admin/modules/courses.php',
    'admin/modules/departments.php',
    'admin/modules/documents.php',
    'admin/modules/events.php',
    'admin/modules/facilities.php',
    'admin/modules/faculty.php',
    'admin/modules/fees.php',
    'admin/modules/gallery.php',
    'admin/modules/homepage_activities.php',
    'admin/modules/logs.php',
    'admin/modules/navigation.php',
    'admin/modules/news.php',
    'admin/modules/notices.php',
    'admin/modules/placements.php',
    'admin/modules/settings.php',
    'admin/modules/users.php',
    'admin/modules/visitors.php',
    'admin/ai-chatbot/index.php',
    'admin/ai-chatbot/knowledge-base.php',
    'admin/ai-chatbot/faq.php',
    'admin/ai-chatbot/conversations.php',
    'admin/ai-chatbot/unanswered.php',
    'admin/ai-chatbot/feedback.php',
    'admin/ai-chatbot/documents.php',
    'admin/ai-chatbot/settings.php',
];

echo "========================================================\n";
echo " TESTING ADMIN PANEL MODULES RUNTIME INTEGRITY\n";
echo "========================================================\n";

$root = dirname(__DIR__);
$pass = 0;
$fail = 0;

foreach ($adminModules as $mod) {
    // Run via php CLI with session pre-seeded
    $code = '<?php
    require_once "' . addslashes($root . '/core/bootstrap.php') . '";
    Session::set("logged_in", true);
    Session::set("user_id", 1);
    Session::set("role_id", 1);
    Session::set("role_name", "Super Admin");
    Session::set("permissions", ["*"]);
    Session::set("username", "superadmin");
    Session::set("full_name", "Administrator");
    $_SERVER["REQUEST_METHOD"] = "GET";
    $_SERVER["REQUEST_URI"] = "/' . $mod . '";
    $_SERVER["HTTP_HOST"] = "localhost";
    include "' . addslashes($root . '/' . $mod) . '";
    ';
    
    $tmpFile = __DIR__ . '/_tmp_admin_test.php';
    file_put_contents($tmpFile, $code);
    
    $cmd = "php -f " . escapeshellarg($tmpFile);
    $output = shell_exec($cmd);
    @unlink($tmpFile);
    
    $hasContent = str_contains($output, '<html') || str_contains($output, 'sidebar') || str_contains($output, '<div') || str_contains($output, 'header');
    $hasErrors = preg_match('/(Fatal error|Parse error|PHP Warning|PHP Notice|<b>Warning<\/b>|<b>Notice<\/b>)/i', $output);
    
    if ($hasContent && !$hasErrors) {
        echo "[PASS] Admin: {$mod} (" . strlen($output) . " bytes)\n";
        $pass++;
    } else {
        echo "[FAIL] Admin: {$mod}\n";
        if ($hasErrors) {
            preg_match_all('/(Fatal error|Parse error|PHP Warning|PHP Notice|<b>Warning<\/b>|<b>Notice<\/b>)[^\n]+/i', $output, $m);
            echo "   Errors: " . implode("\n   ", $m[0]) . "\n";
        }
        $fail++;
    }
}

echo "\nADMIN MODULES TEST SUMMARY: {$pass} PASSED, {$fail} FAILED\n";
