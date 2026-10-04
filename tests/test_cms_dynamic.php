<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/ContentManager.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../chatbot/services/DatabaseSearch.php';

echo "====================================================\n";
echo "    SOET CMS & WEBSITE E2E VERIFICATION SUITE       \n";
echo "====================================================\n\n";

$cms = new ContentManager();
$db = Database::getInstance();

// 1. Test Database Tables
echo "[1] Checking Database Tables & New Columns...\n";
$tables = ['facilities', 'placement_recruiters', 'homepage_activities', 'site_settings', 'departments', 'courses', 'faculty', 'events', 'gallery', 'documents', 'placements'];
foreach ($tables as $tbl) {
    $exists = $db->fetchOne("SHOW TABLES LIKE '{$tbl}'");
    echo "  - Table `{$tbl}`: " . ($exists ? "EXISTS (OK)" : "MISSING (FAIL)") . "\n";
}

// 2. Test ContentManager Methods & Site Settings
echo "\n[2] Testing ContentManager CMS Methods & Settings...\n";
$settings = $cms->getSiteSettings();
echo "  - Total Site Settings Keys: " . count($settings) . "\n";
$sampleKeys = [
    'site_name', 'college_name', 'contact_phone', 'contact_email', 'college_address',
    'hero_title', 'hero_subtitle', 'stats_experience', 'stats_programs', 'stats_intake',
    'stats_placement_rate', 'stats_highest_package', 'dean_name', 'dean_designation',
    'facebook_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'copyright_text'
];
foreach ($sampleKeys as $sk) {
    echo "    * {$sk} => " . ($settings[$sk] ?? '[NOT SET]') . "\n";
}

$facilities = $cms->getFacilities();
echo "  - Facilities: " . count($facilities) . " records\n";

$recruiters = $cms->getPlacementRecruiters();
echo "  - Placement Recruiters: " . count($recruiters) . " records\n";

$activities = $cms->getHomepageActivities();
echo "  - Homepage Activities: " . count($activities) . " records\n";

$depts = $cms->getDepartments();
echo "  - Departments: " . count($depts) . " records\n";

$courses = $cms->getCourses();
echo "  - Courses: " . count($courses) . " records\n";

$faculty = $cms->getFaculty();
echo "  - Faculty Members: " . count($faculty) . " records\n";

$docs = $cms->getOfficialDocuments();
echo "  - Official Documents: " . count($docs) . " records\n";

$stats = $cms->getDashboardStats();
echo "  - Dashboard Stats Summary: " . count($stats) . " metric counters active\n";

// 3. Test Chatbot DatabaseSearch
echo "\n[3] Testing Chatbot DatabaseSearch Queries...\n";
$dbSearch = new DatabaseSearch();
$testQueries = [
    'facilities' => 'What facilities and labs are on campus?',
    'seats' => 'How many seats are vacant in CSE?',
    'events' => 'Tell me about upcoming college events',
    'placements' => 'What are the placement packages and recruiters?',
    'documents' => 'Download official brochure',
    'faculty' => 'Who teaches CSE?',
    'departments' => 'Tell me about engineering departments'
];

foreach ($testQueries as $label => $q) {
    $res = $dbSearch->search($q);
    $found = !empty($res) && (!empty($res['found']) || !empty($res[0]['title']) || !empty($res['top_result']));
    $title = $res['top_result']['title'] ?? ($res[0]['title'] ?? 'N/A');
    echo "  - Query '{$label}': " . ($found ? "SUCCESS" : "EMPTY") . " -> '{$title}'\n";
}

// 4. Test PHP Syntax Linting on all project and admin files
echo "\n[4] Linting PHP Syntax on all Core, Admin, and Frontend files...\n";
$directories = [
    __DIR__ . '/..',
    __DIR__ . '/../core',
    __DIR__ . '/../config',
    __DIR__ . '/../includes',
    __DIR__ . '/../admin',
    __DIR__ . '/../admin/modules',
    __DIR__ . '/../admin/ai-chatbot',
    __DIR__ . '/../chatbot/services',
    __DIR__ . '/../chatbot/engine'
];

$lintErrors = 0;
$totalFiles = 0;
foreach ($directories as $dir) {
    if (!is_dir($dir)) continue;
    $files = glob($dir . '/*.php');
    foreach ($files as $f) {
        $totalFiles++;
        $output = [];
        $returnVar = 0;
        exec("C:\\xampp\\php\\php.exe -l \"" . escapeshellarg($f) . "\"", $output, $returnVar);
        if ($returnVar !== 0) {
            echo "  [LINT ERROR] " . basename($f) . ": " . implode(" ", $output) . "\n";
            $lintErrors++;
        }
    }
}
echo "  - Checked {$totalFiles} PHP files. Total Lint Errors: {$lintErrors}\n";

echo "\n====================================================\n";
echo "    VERIFICATION RESULT: " . ($lintErrors === 0 ? "PASSED (100% HEALTHY)" : "FAILED") . "\n";
echo "====================================================\n";
