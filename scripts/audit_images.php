<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();

echo "=======================================================\n";
echo "AUDITING ALL DATABASE IMAGES AND ASSET AVAILABILITY\n";
echo "=======================================================\n\n";

$checks = [
    'site_settings' => ['query' => "SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE '%image%' OR setting_key LIKE '%logo%' OR setting_key LIKE '%photo%'", 'col' => 'setting_value', 'key' => 'setting_key', 'dir' => 'settings'],
    'courses' => ['query' => "SELECT id, name, image_path FROM courses", 'col' => 'image_path', 'key' => 'name', 'dir' => 'courses'],
    'departments' => ['query' => "SELECT id, name, image_path FROM departments", 'col' => 'image_path', 'key' => 'name', 'dir' => 'departments'],
    'faculty' => ['query' => "SELECT id, name, image_path FROM faculty", 'col' => 'image_path', 'key' => 'name', 'dir' => 'faculty'],
    'gallery' => ['query' => "SELECT id, title, image_path FROM gallery", 'col' => 'image_path', 'key' => 'title', 'dir' => 'gallery'],
    'placements' => ['query' => "SELECT id, student_name, student_image FROM placements", 'col' => 'student_image', 'key' => 'student_name', 'dir' => 'placements'],
    'news' => ['query' => "SELECT id, title, image_path FROM news", 'col' => 'image_path', 'key' => 'title', 'dir' => 'news'],
    'blogs' => ['query' => "SELECT id, title, image_path FROM blogs", 'col' => 'image_path', 'key' => 'title', 'dir' => 'blogs'],
];

$missingCount = 0;
$foundCount = 0;

foreach ($checks as $table => $cfg) {
    echo "--- TABLE: $table ---\n";
    $rows = $db->fetchAll($cfg['query']);
    foreach ($rows as $r) {
        $val = trim($r[$cfg['col']] ?? '');
        $label = $r[$cfg['key']] ?? 'Unknown';
        if (empty($val)) {
            echo "  [EMPTY] $label -> (no image assigned)\n";
            continue;
        }
        
        $expectedPath = __DIR__ . '/../assets/uploads/' . $cfg['dir'] . '/' . $val;
        $inImages = __DIR__ . '/../assets/images/' . $val;
        $inImagesDir = __DIR__ . '/../assets/images/' . $cfg['dir'] . '/' . $val;
        $inUploads = __DIR__ . '/../assets/uploads/' . $val;

        if (file_exists($expectedPath)) {
            echo "  [OK - in uploads/{$cfg['dir']}] $label -> $val\n";
            $foundCount++;
        } elseif (file_exists($inImages)) {
            echo "  [MISLOCATED - in assets/images/] $label -> $val (Needs copy to uploads/{$cfg['dir']}/)\n";
            $missingCount++;
        } elseif (file_exists($inImagesDir)) {
            echo "  [MISLOCATED - in assets/images/{$cfg['dir']}/] $label -> $val (Needs copy to uploads/{$cfg['dir']}/)\n";
            $missingCount++;
        } elseif (file_exists($inUploads)) {
            echo "  [MISLOCATED - in assets/uploads/] $label -> $val\n";
            $missingCount++;
        } else {
            echo "  [MISSING ENTIRELY] $label -> $val\n";
            $missingCount++;
        }
    }
    echo "\n";
}

echo "Total Found: $foundCount, Total Missing / Mislocated: $missingCount\n";
