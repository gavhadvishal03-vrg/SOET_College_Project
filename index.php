<?php
/**
 * Public Homepage Controller & View
 *
 * Serves the primary landing portal for SOET MGM University.
 * Displays:
 * - Urgent announcement notice banner
 * - Dynamic hero banner with admissions call-to-action
 * - Key institutional statistics and milestones
 * - Academic departments and program highlights
 * - Live notice board and event calendar
 * - Placement recruiter showcase & student testaments
 * - Latest campus news, blog articles, and photo gallery
 */

$page_title = "Home - Where Technology Meets Tomorrow";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$notices = $cms->getActiveNotices(5);
$recent_news = $cms->getPublishedNews(3);
$recent_blogs = $cms->getPublishedBlogs(3);
$settings = $cms->getSiteSettings();
$hero_bg = !empty($settings['home_hero_image'])
    ? uploadUrl('settings', $settings['home_hero_image'])
    : APP_URL . '/assets/images/soet_campus_aerial.jpg';
?>

<?php
$pinnedNotice = null;
foreach ($notices as $n) {
    if ($n['is_pinned']) {
        $pinnedNotice = $n;
        break;
    }
}
?>

<?php if ($pinnedNotice): ?>
    <div class="bg-warning text-dark py-2 px-3 border-bottom shadow-xs">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <span class="badge bg-danger text-white me-2"><i class="fa-solid fa-bell me-1"></i> Urgent Notice</span>
                <strong class="small"><?php echo htmlspecialchars($pinnedNotice['title']); ?>:</strong>
                <span class="small ms-1 text-truncate" style="max-width: 500px;"><?php echo truncate(htmlspecialchars($pinnedNotice['content']), 80); ?></span>
            </div>
            <a href="index.php#notice-board-section" class="btn btn-xs btn-dark rounded-pill px-3 py-1" style="font-size: 14px;">View Details</a>
        </div>
    </div>
<?php endif; ?>

<!-- Hero Section -->
<div id="heroCarousel" class="hero-section" style="background-image: url('<?php echo $hero_bg; ?>'); background-size: cover; background-position: center; min-height: 540px; position: relative;">
    <div class="hero-overlay" style="position: absolute; inset: 0; background: linear-gradient(135deg, rgba(15, 23, 42, 0.88) 0%, rgba(30, 58, 138, 0.82) 100%);"></div>
    <div class="container h-100 position-relative z-1 py-5 d-flex align-items-center" style="min-height: 540px;">
        <div class="hero-content text-white" style="max-width: 800px;">
            <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-1.5 rounded-pill mb-3" style="backdrop-filter: blur(8px);">
                <span class="badge bg-warning text-dark font-bold px-2 py-0.5" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_hero_badge'] ?? 'ADMISSIONS OPEN'); ?></span>
                <span class="text-white font-semibold" style="font-size: 14px;"><i class="fa-solid fa-sparkles text-warning me-1"></i> <?php echo htmlspecialchars($settings['home_hero_badge_sub'] ?? ('Academic Session ' . ($settings['admission_year'] ?? '2026-27') . ' • ' . ($settings['total_intake_ay2026_27'] ?? '380') . ' Seats')); ?></span>
            </div>
            <h1 class="hero-title text-white font-display fw-extrabold mb-3" style="font-size: 2.8rem; line-height: 1.15;"><?php echo htmlspecialchars($settings['home_hero_title'] ?? 'Where Technology Meets Tomorrow'); ?></h1>
            <p class="hero-subtitle text-white text-opacity-90 lead mb-4" style="font-size: 1.15rem; line-height: 1.7;">
                <?php echo htmlspecialchars($settings['home_hero_subtitle'] ?? "You're not just studying engineering. You're engineering the future. Empowering innovators through cutting-edge laboratories, NEP 2020 aligned curricula, and a 42-year legacy of excellence under MGM Trust."); ?>
            </p>
            <div class="d-flex flex-wrap gap-3">
                <a href="<?php echo htmlspecialchars($settings['home_hero_btn1_url'] ?? 'admissions.php'); ?>" class="btn btn-warning text-dark btn-lg rounded-pill px-4 font-bold shadow-sm hover-lift">
                    <i class="fa-solid fa-user-plus me-1.5"></i> <?php echo htmlspecialchars($settings['home_hero_btn1_text'] ?? 'Apply for AY 2026-27'); ?>
                </a>
                <a href="<?php echo htmlspecialchars($settings['home_hero_btn2_url'] ?? 'courses.php'); ?>" class="btn btn-outline-light btn-lg rounded-pill px-4 font-semibold" style="backdrop-filter: blur(8px);">
                    <i class="fa-solid fa-book-open me-1.5"></i> <?php echo htmlspecialchars($settings['home_hero_btn2_text'] ?? 'Explore Programs & Booklets'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main content area -->
<div class="container my-5">
    <div class="row g-4">
        <!-- Announcements / Notice Board -->
        <div class="col-lg-4" id="notice-board-section">
            <div class="card notice-board shadow-sm h-100 border-0" style="background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                <div class="card-header d-flex align-items-center justify-content-between p-3" style="background: #0f172a; color: #ffffff; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="mb-0 font-semibold" style="font-size: 15px;"><i class="fa-solid fa-bullhorn text-warning me-2"></i>Official Notices</h5>
                    <span class="badge bg-danger rounded-pill px-2.5 py-1">Live Feed</span>
                </div>
                <div class="card-body p-0" style="max-height: 420px; overflow-y: auto;">
                    <?php if (empty($notices)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fa-regular fa-folder-open mb-2" style="font-size: 24px;"></i>
                            <p class="mb-0 small">No active announcements at the moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notices as $notice): ?>
                            <div class="notice-item p-3 border-bottom">
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-muted small"><i class="fa-regular fa-calendar-days me-1"></i><?php echo formatDate($notice['created_at']); ?></span>
                                    <?php if ($notice['is_pinned']): ?>
                                        <span class="notice-badge bg-danger text-white"><i class="fa-solid fa-thumbtack me-1"></i>Important</span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="mb-1 fw-bold text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($notice['title']); ?></h6>
                                <p class="text-muted small mb-2"><?php echo truncate(htmlspecialchars($notice['content']), 90); ?></p>
                                <?php if ($notice['attachment_path']): ?>
                                    <a href="<?php echo uploadUrl('notices', $notice['attachment_path']); ?>" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded-pill" style="font-size: 14px;">
                                        <i class="fa-solid fa-file-pdf me-1 text-danger"></i> View Document
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- College Welcome & Quick Overview (Official Brochure Content) -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 p-md-4 h-100 bg-white" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-1.5 rounded-pill mb-2 font-semibold align-self-start" style="font-size: 14px;">
                    <i class="fa-solid fa-landmark me-1 text-warning"></i> <?php echo htmlspecialchars($settings['home_welcome_badge'] ?? 'MGM University • UGC 2(f) Recognized'); ?>
                </div>
                <h2 class="fw-bold mb-2 text-primary-color font-display" style="font-size: 1.85rem;"><?php echo htmlspecialchars($settings['home_welcome_title'] ?? 'Welcome to School of Engineering & Technology'); ?></h2>
                <h6 class="text-muted mb-3 font-semibold"><?php echo htmlspecialchars($settings['home_welcome_subtitle'] ?? 'Pioneering B.Tech (4-Year after HSC) & Integrated B.Tech (6-Year after SSC) Programs'); ?></h6>
                <p class="text-muted" style="font-size: 14.5px; line-height: 1.75; text-align: justify;">
                    <?php echo nl2br(htmlspecialchars($settings['home_welcome_content'] ?? "Students at SOET MGM University have access to some of the most advanced facilities in the region. From building intelligent machines in the Industrial Robotics Lab to exploring sustainable mobility in the Electric Vehicle Lab, every space is designed to spark innovation and hands-on learning. Whether you're prototyping drones, designing Industry 4.0 systems, or launching your startup at the Innovation Incubation and Research Center, you'll find the tools and mentorship to go beyond the classroom.")); ?>
                </p>
                <div class="row g-3 mt-2">
                    <div class="col-sm-4 text-center">
                        <div class="p-3 bg-light rounded-3 shadow-xs border">
                            <h3 class="fw-extrabold text-primary-color mb-0 font-display"><?php echo htmlspecialchars($settings['home_stat1_val'] ?? '42+ Yrs'); ?></h3>
                            <small class="text-muted font-semibold"><?php echo htmlspecialchars($settings['home_stat1_label'] ?? 'MGM Trust Legacy'); ?></small>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="p-3 bg-light rounded-3 shadow-xs border">
                            <h3 class="fw-extrabold text-primary-color mb-0 font-display"><?php echo htmlspecialchars($settings['home_stat2_val'] ?? ($settings['total_intake_ay2026_27'] ?? '380')); ?></h3>
                            <small class="text-muted font-semibold"><?php echo htmlspecialchars($settings['home_stat2_label'] ?? ('AY ' . ($settings['admission_year'] ?? '2026-27') . ' Intake')); ?></small>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="p-3 bg-light rounded-3 shadow-xs border">
                            <h3 class="fw-extrabold text-warning mb-0 font-display"><?php echo htmlspecialchars($settings['home_stat3_val'] ?? '15+ MoUs'); ?></h3>
                            <small class="text-muted font-semibold"><?php echo htmlspecialchars($settings['home_stat3_label'] ?? 'Industry Partners'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Academic Highlights Banner from Brochure -->
<div class="container mb-5">
    <div class="card border-0 shadow-sm p-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-radius: 18px; border-top: 4px solid var(--accent) !important;">
        <div class="row g-4 align-items-center">
            <div class="col-lg-3 text-center text-lg-start">
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill mb-2 font-bold" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight_badge'] ?? 'ACADEMIC EXCELLENCE'); ?></span>
                <h4 class="fw-bold mb-1 font-display text-white"><?php echo htmlspecialchars($settings['home_highlight_title'] ?? 'Future-Ready Education'); ?></h4>
                <p class="small text-white-50 mb-0"><?php echo htmlspecialchars($settings['home_highlight_subtitle'] ?? 'Empowering student engineers through innovation, research, and ethics.'); ?></p>
            </div>
            <div class="col-lg-9">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-10">
                            <i class="<?php echo htmlspecialchars($settings['home_highlight1_icon'] ?? 'fa-solid fa-graduation-cap'); ?> text-warning fa-lg mb-2"></i>
                            <h6 class="fw-bold text-white mb-0" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight1_title'] ?? 'NEP 2020 Aligned'); ?></h6>
                            <small class="text-white-50" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight1_sub'] ?? 'Flexible Curriculum'); ?></small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-10">
                            <i class="<?php echo htmlspecialchars($settings['home_highlight2_icon'] ?? 'fa-solid fa-diagram-project'); ?> text-warning fa-lg mb-2"></i>
                            <h6 class="fw-bold text-white mb-0" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight2_title'] ?? 'Project-Based (PBL)'); ?></h6>
                            <small class="text-white-50" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight2_sub'] ?? 'Experiential Learning'); ?></small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-10">
                            <i class="<?php echo htmlspecialchars($settings['home_highlight3_icon'] ?? 'fa-solid fa-robot'); ?> text-warning fa-lg mb-2"></i>
                            <h6 class="fw-bold text-white mb-0" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight3_title'] ?? 'Robotics & EV Labs'); ?></h6>
                            <small class="text-white-50" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight3_sub'] ?? 'Mechatronics 4.0'); ?></small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-10">
                            <i class="<?php echo htmlspecialchars($settings['home_highlight4_icon'] ?? 'fa-solid fa-rocket'); ?> text-warning fa-lg mb-2"></i>
                            <h6 class="fw-bold text-white mb-0" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight4_title'] ?? 'Startup & Incubation'); ?></h6>
                            <small class="text-white-50" style="font-size: 14px;"><?php echo htmlspecialchars($settings['home_highlight4_sub'] ?? 'IIRC Research Center'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Official Documents / Program Booklets Download Section -->
<div class="container mb-5">
    <div class="section-header text-center mb-4">
        <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-30 px-3 py-1.5 rounded-pill mb-2 font-semibold" style="font-size: 14px;">
            <i class="fa-solid fa-file-arrow-down text-warning me-1"></i> Official Resources
        </span>
        <h2 class="font-display fw-bold text-primary-color">Official Documents &amp; Program Booklets</h2>
        <p class="text-muted mx-auto" style="max-width: 700px;">Download official admission brochures, information leaflets, and NEP 2020 curriculum booklets published by the Academic Section, MGM University.</p>
    </div>

    <?php 
    $dbDocs = $cms->getOfficialDocuments();
    if (!empty($dbDocs)) {
        $officialDocs = array_map(function($d) {
            $cat = strtolower($d['category'] ?? 'brochure');
            $color = match($cat) {
                'brochure' => '#b45309',
                'leaflet' => '#1e3a8a',
                'curriculum' => '#059669',
                'intake' => '#ea580c',
                default => '#2563eb'
            };
            $icon = match($cat) {
                'brochure' => 'fa-solid fa-file-pdf',
                'leaflet' => 'fa-solid fa-file-lines',
                'curriculum' => 'fa-solid fa-book-open',
                'intake' => 'fa-solid fa-table-list',
                default => 'fa-solid fa-file'
            };
            return [
                'title' => $d['title'],
                'desc' => $d['description'] ?? '',
                'batch' => $d['academic_year'] ?? 'AY 2026-27',
                'file' => $d['file_path'],
                'icon' => $icon,
                'badge' => ucfirst($cat),
                'color' => $color
            ];
        }, $dbDocs);
    } else {
        $officialDocs = [
            [
                'title' => 'SOET Admission Brochure 2026-27',
                'desc' => 'Comprehensive institutional brochure covering 4-Year B.Tech, 6-Year Integrated B.Tech, Diploma programs, facilities & recruiters.',
                'batch' => 'AY 2026-27',
                'file' => 'assets/documents/SOET_Admission_Brochure_2026-27.pdf',
                'icon' => 'fa-solid fa-file-pdf',
                'badge' => 'Admission Brochure',
                'color' => '#b45309'
            ]
        ];
    }
    ?>

    <div class="row g-4">
        <?php foreach ($officialDocs as $doc): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-xs h-100 bg-white rounded-4 overflow-hidden hover-lift" style="border: 1px solid #e2e8f0 !important;">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge rounded-pill fw-bold" style="background: <?php echo $doc['color']; ?>15; color: <?php echo $doc['color']; ?>; border: 1px solid <?php echo $doc['color']; ?>30; font-size: 14px;">
                                    <?php echo htmlspecialchars($doc['badge']); ?>
                                </span>
                                <small class="text-muted font-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($doc['batch']); ?></small>
                            </div>
                            <h5 class="fw-bold text-primary-color mb-2 font-display" style="font-size: 15px; line-height: 1.35;">
                                <i class="<?php echo $doc['icon']; ?> me-1.5" style="color: <?php echo $doc['color']; ?>;"></i>
                                <?php echo htmlspecialchars($doc['title']); ?>
                            </h5>
                            <p class="text-muted small mb-3" style="line-height: 1.6;"><?php echo htmlspecialchars($doc['desc']); ?></p>
                        </div>
                        <div class="d-flex gap-2 pt-2 border-top">
                            <a href="<?php echo APP_URL . '/' . $doc['file']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill flex-fill font-semibold" style="font-size: 14px;">
                                <i class="fa-solid fa-eye me-1"></i> View PDF
                            </a>
                            <a href="<?php echo APP_URL . '/' . $doc['file']; ?>" download class="btn btn-sm btn-primary rounded-pill flex-fill font-semibold" style="font-size: 14px;">
                                <i class="fa-solid fa-download me-1"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Student Activities & Campus Highlights Section (Dynamic from Database) -->
<div class="bg-light py-5 mb-5 border-top border-bottom border-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-30 px-3 py-1.5 rounded-pill mb-2 font-semibold" style="font-size: 14px;">
                <i class="fa-solid fa-trophy text-warning me-1"></i> Vibrant Campus Life
            </span>
            <h2 class="font-display fw-bold text-primary-color">Student Activities &amp; Tech Conclaves</h2>
            <p class="text-muted mx-auto" style="max-width: 700px;">From global tech conferences and cultural fests to robotics training and industrial plant visits, student life at SOET ensures holistic development.</p>
        </div>

        <?php 
        $homeActivities = $cms->getHomepageActivities(true);
        if (empty($homeActivities)) {
            $homeActivities = [
                ['title' => 'Experts Live India Conference', 'badge' => 'Conference', 'year_or_tag' => '2025', 'description' => 'Premier technological conclave bringing international Microsoft MVPs, cloud architects, and AI researchers to interact directly with SOET students.', 'image_path' => 'event_experts_live_india.jpg'],
                ['title' => 'SUPERNOVA & Project Exhibition', 'badge' => 'Tech Fest & Expo', 'year_or_tag' => 'Flagship Event', 'description' => 'Annual engineering hackathon and grand project exhibition where students showcase working IoT prototypes, robotics arms, and AI systems.', 'image_path' => 'event_supernova_group.jpg'],
                ['title' => 'ZESTOPIA Cultural Fest', 'badge' => 'Cultural Fest', 'year_or_tag' => 'Zestopia', 'description' => 'A vibrant celebration of arts, music, theatrical performances, and cultural diversity fostering student creativity beyond academics.', 'image_path' => 'event_zestopia_cultural.jpg'],
                ['title' => 'Robotics & Automation Training', 'badge' => 'Workshop', 'year_or_tag' => 'Live Labs', 'description' => 'Intensive hands-on training on ROS (Robot Operating System), kinematics simulation, PLC programming, and drone assembly in advanced robotics labs.', 'image_path' => 'event_robotics_training.jpg'],
                ['title' => 'Industrial Plant Visit to LIEBHERR', 'badge' => 'Industrial Visit', 'year_or_tag' => 'On-Site', 'description' => 'Regular on-site industrial plant tours to manufacturing leaders like LIEBHERR and Škoda Volkswagen for practical shopfloor insights.', 'image_path' => 'visit_liebherr_industry.jpg'],
                ['title' => 'NSS Outreach & Sports Championship', 'badge' => 'Athletics & NSS', 'year_or_tag' => 'Championship', 'description' => 'National Service Scheme (NSS) social outreach camps and annual inter-collegiate sports championship on Olympic-grade grounds.', 'image_path' => 'activity_sports_team.jpg']
            ];
        }
        ?>

        <div class="row g-4">
            <?php foreach ($homeActivities as $act): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-xs h-100 overflow-hidden bg-white hover-lift rounded-4" style="border: 1px solid #e2e8f0 !important;">
                        <div style="height: 180px; overflow: hidden;">
                            <img src="<?php echo uploadUrl('gallery', $act['image_path']); ?>" alt="<?php echo htmlspecialchars($act['title']); ?>" class="w-100 h-100 object-fit-cover" loading="lazy" decoding="async">
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 font-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($act['badge']); ?></span>
                                <small class="text-muted"><i class="fa-solid fa-calendar me-1 text-warning"></i> <?php echo htmlspecialchars($act['year_or_tag']); ?></small>
                            </div>
                            <h5 class="fw-bold text-primary-color mb-2 font-display" style="font-size: 15px;"><?php echo htmlspecialchars($act['title']); ?></h5>
                            <p class="text-muted small mb-0" style="line-height: 1.65;"><?php echo htmlspecialchars($act['description']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Departments Summary Section -->
<div class="container mb-5">
    <div class="section-header text-center mb-5">
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-1.5 rounded-pill mb-2 font-semibold" style="font-size: 14px;">
            <i class="fa-solid fa-network-wired me-1"></i> Academic Disciplines
        </span>
        <h2 class="font-display fw-bold text-primary-color">Academic Departments</h2>
        <p class="text-muted mx-auto" style="max-width: 700px;">Explore specialized engineering disciplines offering top-tier training, innovative research, and modern laboratories.</p>
    </div>
    <div class="row g-4">
        <?php
        $active_depts = $cms->getDepartments(true);
        if (empty($active_depts)):
        ?>
            <div class="col-12 text-center text-muted">
                <p>No active departments found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($active_depts as $dept):
                $dept_img = !empty($dept['image_path'])
                    ? uploadUrl('departments', $dept['image_path'])
                    : uploadUrl('departments', 'soet_future_tech_banner.jpg');
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="dept-card" style="background-image: url('<?php echo $dept_img; ?>'); height: 260px;">
                        <div class="dept-overlay">
                            <span class="badge bg-warning text-dark mb-1 align-self-start font-bold" style="font-size: 14px;"><?php echo htmlspecialchars($dept['code']); ?></span>
                            <h4 class="fw-bold mb-1 text-white" style="font-size: 1.15rem;"><?php echo htmlspecialchars($dept['name']); ?></h4>
                            <p class="small mb-3 text-white opacity-90"><?php echo truncate(htmlspecialchars($dept['description'] ?? ''), 75); ?></p>
                            <a href="departments.php#dept-<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-light rounded-pill align-self-start px-3 py-1 font-semibold" style="font-size: 14px; backdrop-filter: blur(4px);">Explore Department <i class="fa-solid fa-chevron-right ms-1" style="font-size: 14px;"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- News & Blogs Portal Section -->
<div class="container my-5">
    <div class="row g-4">
        <!-- News Portal -->
        <div class="col-md-6">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <h3 class="fw-bold mb-0 text-primary-color" style="font-size: 1.4rem;"><i class="fa-solid fa-newspaper text-warning me-2"></i>Campus News</h3>
                <a href="news.php" class="small text-muted font-semibold text-decoration-none">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <?php if (empty($recent_news)): ?>
                <p class="text-muted small">No news updates available.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($recent_news as $news): ?>
                        <div class="card border shadow-xs h-100 overflow-hidden d-flex flex-row hover-lift rounded-3">
                            <?php if ($news['image_path']): ?>
                                <img src="<?php echo uploadUrl('news', $news['image_path']); ?>" alt="News banner" style="width: 120px; min-height: 100px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body p-3">
                                <span class="text-muted small d-block mb-1"><i class="fa-regular fa-clock me-1"></i><?php echo formatDate($news['published_at']); ?></span>
                                <h6 class="fw-bold mb-1"><a href="news.php" class="text-decoration-none text-dark"><?php echo htmlspecialchars($news['title']); ?></a></h6>
                                <p class="text-muted small mb-0"><?php echo truncate(htmlspecialchars($news['content']), 80); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Student Blogs -->
        <div class="col-md-6">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <h3 class="fw-bold mb-0 text-primary-color" style="font-size: 1.4rem;"><i class="fa-solid fa-pen-nib text-warning me-2"></i>Student &amp; Faculty Blogs</h3>
                <a href="blogs.php" class="small text-muted font-semibold text-decoration-none">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <?php if (empty($recent_blogs)): ?>
                <p class="text-muted small">No blog entries submitted yet.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($recent_blogs as $blog): ?>
                        <div class="card border shadow-xs h-100 overflow-hidden d-flex flex-row hover-lift rounded-3">
                            <?php if ($blog['image_path']): ?>
                                <img src="<?php echo uploadUrl('blogs', $blog['image_path']); ?>" alt="Blog image" style="width: 120px; min-height: 100px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small"><i class="fa-regular fa-clock me-1"></i><?php echo formatDate($blog['published_at']); ?></span>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 14px;"><?php echo htmlspecialchars($blog['department_name'] ?? 'General'); ?></span>
                                </div>
                                <h6 class="fw-bold mb-1"><a href="blogs.php" class="text-decoration-none text-dark"><?php echo htmlspecialchars($blog['title']); ?></a></h6>
                                <p class="text-muted small mb-0">By: <span class="fw-semibold text-dark"><?php echo htmlspecialchars($blog['author_name']); ?></span></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>