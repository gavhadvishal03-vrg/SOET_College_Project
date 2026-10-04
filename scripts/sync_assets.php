<?php
$rootDir = realpath(__DIR__ . '/..');
$uploadsDir = $rootDir . '/assets/uploads';
$imagesDir = $rootDir . '/assets/images';

$dirs = ['courses', 'departments', 'faculty', 'gallery', 'news', 'blogs', 'events', 'placements', 'settings', 'admissions', 'notices'];

foreach ($dirs as $d) {
    $target = $uploadsDir . '/' . $d;
    if (!is_dir($target)) {
        mkdir($target, 0777, true);
        echo "Created folder: $target\n";
    }
}

// 1. Copy images from assets/images to relevant uploads folders
$imagesToCopy = [
    // Settings & Branding
    'settings' => [
        'mgm_university_official_logo.png',
        'soet_campus_aerial.jpg',
        'soet_robotics_mechatronics_lab.jpg',
        'about_hero_6a92e30a6a591_1788011274.jpg',
        'director_photo_6a70572926c22_1785747241.jpeg',
        'home_hero_6a830344f14cd_1786970948.png',
        'home_welcome_6a830344f3cc2_1786970948.png',
        'logo.svg'
    ],
    // Courses
    'courses' => [
        'soet_future_tech_banner.jpg',
        'event_robotics_training.jpg',
        'soet_robotics_mechatronics_lab.jpg',
        'soet_campus_aerial.jpg',
        'activity_nss_camp.jpg',
        'ai_lab_hd.jpg',
        'robotics_hd.jpg',
        'canteen_hd.jpg',
        'hostel_hd.jpg',
        'library_hd.jpg',
        'sports_hd.jpg',
        'dept_cse.jpg',
        'dept_ece.jpg',
        'dept_ee.jpg',
        'dept_mech.jpg',
        'dept_civil.jpg',
        'dept_ash.jpg'
    ],
    // Departments
    'departments' => [
        'soet_future_tech_banner.jpg',
        'soet_robotics_mechatronics_lab.jpg',
        'event_robotics_training.jpg',
        'soet_campus_aerial.jpg',
        'activity_nss_camp.jpg',
        'dept_cse.jpg',
        'dept_ece.jpg',
        'dept_ee.jpg',
        'dept_mech.jpg',
        'dept_civil.jpg',
        'dept_ash.jpg'
    ],
    // Blogs
    'blogs' => [
        'blog_6a83045645703_1786971222.png',
        'soet_future_tech_banner.jpg',
        'soet_robotics_mechatronics_lab.jpg'
    ],
    // Events
    'events' => [
        'event_experts_live_india.jpg',
        'event_supernova_group.jpg',
        'event_supernova_stage.jpg',
        'event_supernova_tech.jpg',
        'event_project_exhibition.jpg',
        'event_zestopia_cultural.jpg',
        'event_robotics_training.jpg',
        'visit_liebherr_industry.jpg',
        'event_author_discussion.jpg',
        'activity_nss_camp.jpg',
        'activity_sports_team.jpg'
    ],
    // Gallery
    'gallery' => [
        'event_experts_live_india.jpg',
        'event_supernova_group.jpg',
        'event_supernova_stage.jpg',
        'event_supernova_tech.jpg',
        'event_project_exhibition.jpg',
        'event_zestopia_cultural.jpg',
        'event_robotics_training.jpg',
        'visit_liebherr_industry.jpg',
        'event_author_discussion.jpg',
        'activity_nss_camp.jpg',
        'activity_sports_team.jpg',
        'soet_campus_aerial.jpg',
        'soet_robotics_mechatronics_lab.jpg',
        'ai_lab_hd.jpg',
        'canteen_hd.jpg',
        'hostel_hd.jpg',
        'library_hd.jpg',
        'robotics_hd.jpg',
        'sports_hd.jpg'
    ],
    // News
    'news' => [
        'event_experts_live_india.jpg',
        'event_supernova_group.jpg',
        'event_supernova_tech.jpg',
        'event_project_exhibition.jpg',
        'soet_future_tech_banner.jpg',
        'soet_campus_aerial.jpg',
        'soet_robotics_mechatronics_lab.jpg'
    ]
];

foreach ($imagesToCopy as $targetSubdir => $fileList) {
    foreach ($fileList as $file) {
        $sourcePaths = [
            $imagesDir . '/' . $file,
            $uploadsDir . '/' . $file,
            $uploadsDir . '/gallery/' . $file,
            $uploadsDir . '/settings/' . $file,
            $uploadsDir . '/faculty/' . $file,
            $rootDir . '/scratch/extracted_media/SOET_ADMISSION_BROCHURE/' . $file
        ];
        
        $copied = false;
        foreach ($sourcePaths as $src) {
            if (file_exists($src)) {
                $dst = $uploadsDir . '/' . $targetSubdir . '/' . $file;
                if (!file_exists($dst)) {
                    copy($src, $dst);
                    echo "Copied: $file -> uploads/$targetSubdir/\n";
                }
                $copied = true;
                break;
            }
        }
        if (!$copied) {
            echo "WARN: Source not found for $file (target: uploads/$targetSubdir)\n";
        }
    }
}

// 2. Create placeholder SVG if missing
$placeholderSvg = $imagesDir . '/placeholder.svg';
$svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400" width="100%" height="100%">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="100%" stop-color="#1e3a5f"/>
    </linearGradient>
  </defs>
  <rect width="600" height="400" fill="url(#g)"/>
  <circle cx="300" cy="170" r="45" fill="#f59e0b" opacity="0.2"/>
  <path d="M280 185 L300 150 L320 185 Z" fill="#f59e0b"/>
  <text x="300" y="245" font-family="system-ui, sans-serif" font-size="18" font-weight="600" fill="#ffffff" text-anchor="middle" opacity="0.9">SOET MGM University</text>
  <text x="300" y="270" font-family="system-ui, sans-serif" font-size="13" fill="#cbd5e1" text-anchor="middle" opacity="0.6">School of Engineering &amp; Technology</text>
</svg>';

file_put_contents($placeholderSvg, $svgContent);
echo "Created: assets/images/placeholder.svg\n";

// Also save placeholder.png
$placeholderPng = $imagesDir . '/placeholder.png';
if (!file_exists($placeholderPng)) {
    // If gd is available, create a clean png placeholder
    if (extension_loaded('gd')) {
        $im = imagecreatetruecolor(600, 400);
        $bg = imagecolorallocate($im, 15, 23, 42);
        imagefilledrectangle($im, 0, 0, 599, 399, $bg);
        $gold = imagecolorallocate($im, 245, 158, 11);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagestring($im, 5, 210, 180, "SOET MGM UNIVERSITY", $gold);
        imagestring($im, 3, 225, 210, "Engineering Portal", $white);
        imagepng($im, $placeholderPng);
        imagedestroy($im);
        echo "Created: assets/images/placeholder.png via GD\n";
    }
}

echo "Asset Sync Complete!\n";
