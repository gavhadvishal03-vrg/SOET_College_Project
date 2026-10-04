<?php
/**
 * Public Portal Navigation Header Component
 *
 * Renders the global responsive navigation bar, dynamic multi-level dropdowns
 * from the navigation_menu table, OpenGraph and Schema.org metadata, and triggers
 * per-page visitor tracking.
 *
 * @var string|null $page_title Custom title passed from parent page
 */
require_once __DIR__ . '/../core/bootstrap.php';

// Record unique page impression in analytics
$visitor = new Visitor();
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$visitor->track($current_page);

$site_title = APP_NAME;
$header_cms = new ContentManager();
$header_settings = $header_cms->getSiteSettings();
$logo_src = !empty($header_settings['site_logo']) 
    ? uploadUrl('settings', $header_settings['site_logo']) 
    : APP_URL . '/assets/images/logo.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | " . APP_SHORT : APP_NAME; ?></title>
    <!-- Meta tags for SEO -->
    <meta name="description" content="School of Engineering and Technology (SOET), MGM University is a premier institute offering B.Tech in CSE, AI/ML, Cyber Security, ECE, Mechanical, and Civil Engineering with industry placements.">
    <meta name="keywords" content="SOET, MGM University, engineering college, B.Tech, CSE, AI ML, Cyber Security, placements, admissions 2026, AI chatbot, Dr. Parminder Kaur Dhingra">
    <meta name="theme-color" content="#1e3a8a">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo APP_URL; ?>/manifest.json">
    <link rel="icon" type="image/svg+xml" href="<?php echo $logo_src; ?>">

    <!-- OpenGraph Metadata -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . " | " . APP_SHORT : APP_NAME; ?>">
    <meta property="og:description" content="Official Academic & Admissions Portal for School of Engineering & Technology, MGM University.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo APP_URL . '/' . $current_page; ?>">

    <!-- Schema.org EducationalOrganization JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "CollegeOrUniversity",
      "name": "School of Engineering & Technology (SOET), MGM University",
      "alternateName": "SOET MGMU",
      "url": "<?php echo APP_URL; ?>",
      "logo": "<?php echo $logo_src; ?>",
      "telephone": "+91-9371714253",
      "email": "soet@mgmu.ac.in",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "MGM Campus, CIDCO, N-6",
        "addressLocality": "Chhatrapati Sambhajinagar (Aurangabad)",
        "addressRegion": "Maharashtra",
        "postalCode": "431003",
        "addressCountry": "IN"
      }
    }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <script>
        window.APP_URL = "<?php echo APP_URL; ?>";
    </script>
</head>
<body>

<?php
// Fetch Dynamic Navigation Items from Database
$header_db = Database::getInstance();
$dbNavMain = [];
$dbNavSubGrouped = [];
try {
    $dbNavMain = $header_db->fetchAll("SELECT * FROM navigation_menu WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order ASC, title ASC");
    $dbNavSubAll = $header_db->fetchAll("SELECT * FROM navigation_menu WHERE parent_id IS NOT NULL AND is_active = 1 ORDER BY sort_order ASC, title ASC");
    foreach ($dbNavSubAll as $sub) {
        $dbNavSubGrouped[$sub['parent_id']][] = $sub;
    }
} catch (Exception $e) {
    $dbNavMain = [];
}
?>
<!-- Header Navigation -->
<nav class="navbar navbar-expand-xl navbar-dark sticky-top py-2" style="border-bottom: 2px solid var(--accent); box-shadow: 0 4px 25px rgba(0,0,0,0.2); background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container-fluid px-2 px-md-3 px-xl-4">
        <a class="navbar-brand d-flex align-items-center me-2 me-xl-3 brand-highlight-link" href="<?php echo APP_URL; ?>/index.php" title="<?php echo htmlspecialchars($header_settings['college_name'] ?? 'School of Engineering & Technology, MGM University'); ?>">
            <div class="brand-logo-frame me-2">
                <img src="<?php echo $logo_src; ?>" alt="MGM University SOET Logo" class="brand-logo-img">
            </div>
            <div class="brand-text-block">
                <span class="d-block lh-1 text-uppercase brand-acronym">SOET</span>
                <span class="d-block text-white brand-college-name">MGM University</span>
            </div>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-xl-0 align-items-center gap-1">
                <?php if (!empty($dbNavMain)): ?>
                    <?php foreach ($dbNavMain as $mainNav): ?>
                        <?php 
                            $hasSub = isset($dbNavSubGrouped[$mainNav['id']]);
                            $is_active = ($current_page === $mainNav['url']);
                            $target = !empty($mainNav['target']) ? $mainNav['target'] : '_self';
                            $url = (strpos($mainNav['url'], 'http') === 0 || strpos($mainNav['url'], 'javascript:') === 0 || $mainNav['url'] === '#') 
                                ? $mainNav['url'] 
                                : APP_URL . '/' . ltrim($mainNav['url'], '/');
                        ?>
                        <?php if ($hasSub): ?>
                            <?php 
                                $subUrls = array_map(function($s) { return $s['url']; }, $dbNavSubGrouped[$mainNav['id']]);
                                $dropdownActive = in_array($current_page, $subUrls);
                            ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo $dropdownActive ? 'active' : ''; ?>" href="#" id="dropdownNav<?php echo $mainNav['id']; ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo htmlspecialchars($mainNav['title']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-dark bg-dark border-0 shadow-lg" aria-labelledby="dropdownNav<?php echo $mainNav['id']; ?>">
                                    <?php foreach ($dbNavSubGrouped[$mainNav['id']] as $subItem): ?>
                                        <?php 
                                            $subTarget = !empty($subItem['target']) ? $subItem['target'] : '_self';
                                            $subUrl = (strpos($subItem['url'], 'http') === 0 || strpos($subItem['url'], 'javascript:') === 0) 
                                                ? $subItem['url'] 
                                                : APP_URL . '/' . ltrim($subItem['url'], '/');
                                        ?>
                                        <li>
                                            <a class="dropdown-item py-2 d-flex align-items-center <?php echo ($current_page === $subItem['url']) ? 'active text-warning' : ''; ?>" href="<?php echo $subUrl; ?>" target="<?php echo $subTarget; ?>">
                                                <i class="fa-solid <?php echo htmlspecialchars(!empty($subItem['icon']) ? $subItem['icon'] : 'fa-circle-chevron-right'); ?> me-2 text-warning flex-shrink-0" style="width: 18px; text-align: center;"></i>
                                                <span><?php echo htmlspecialchars($subItem['title']); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $is_active ? 'active' : ''; ?>" href="<?php echo $url; ?>" target="<?php echo $target; ?>">
                                    <?php echo htmlspecialchars($mainNav['title']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'departments.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/departments.php">Departments</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'courses.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/courses.php">Programs</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'faculty.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/faculty.php">Faculty</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'placements.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/placements.php">Placements</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/gallery.php">Gallery</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'events.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/events.php">Events</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/contact.php">Contact</a></li>
                <?php endif; ?>

                <li class="nav-item ms-xl-1">
                    <a class="btn btn-admission text-center" href="<?php echo APP_URL; ?>/admissions.php"><i class="fa-solid fa-right-to-bracket me-1"></i> Admissions</a>
                </li>
                <li class="nav-item ms-xl-1 mt-2 mt-xl-0">
                    <a class="btn btn-admin-portal text-center" href="<?php echo APP_URL; ?>/admin/login.php"><i class="fa-solid fa-user-shield me-1"></i> Admin Portal</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
