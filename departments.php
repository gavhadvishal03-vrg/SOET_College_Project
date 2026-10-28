<?php
/**
 * Public Departments & Academic Architecture Controller & View
 *
 * Provides comprehensive exploration of SOET's academic departments:
 * - Department mission, vision, and Head of Department (HOD) profiles
 * - Associated specialized degree programs and seat quotas
 * - Advanced laboratory infrastructure and research equipment
 * - Department-specific curriculum booklets, brochures, and syllabus documents
 */
$page_title = "Departments & Programs | SOET, MGM University";
$page_meta_description = "Explore all academic departments and B.Tech engineering programs at SOET, MGM University. View program details, fees, seat intake, eligibility, and official syllabus documents — all in one place.";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();

// Fetch all active departments and their full data in efficient queries
$departments = $cms->getDepartments(true); // active only
$seatMetrics = $cms->getCourseSeatMetrics();
$seatMetricsById = [];
foreach ($seatMetrics as $m) {
    $seatMetricsById[$m['course_id']] = $m;
}

// Department icon map (FontAwesome) by code
$deptIcons = [
    'ASH'      => 'fa-flask',
    'CIVIL'    => 'fa-helmet-safety',
    'CSE'      => 'fa-microchip',
    'EE'       => 'fa-bolt',
    'ECE'      => 'fa-satellite-dish',
    'MECH'     => 'fa-gears',
    'ROBOTICS' => 'fa-robot',
    'SAFETY'   => 'fa-shield-halved',
];
$deptColors = [
    'ASH'      => '#7c3aed',
    'CIVIL'    => '#d97706',
    'CSE'      => '#2563eb',
    'EE'       => '#dc2626',
    'ECE'      => '#059669',
    'MECH'     => '#ea580c',
    'ROBOTICS' => '#0891b2',
    'SAFETY'   => '#e11d48',
];
?>
<!-- ============================================================
     PAGE HERO SECTION
     ============================================================ -->
<div class="page-hero mb-0" style="padding-bottom: 0;">
    <div class="container position-relative z-1 pb-4">
        <div class="row align-items-end g-4">
            <div class="col-lg-7">
                <span class="badge mb-2 px-3 py-2" style="background: rgba(245,158,11,0.18); color: var(--accent); border: 1px solid rgba(245,158,11,0.35); font-size: 14px; letter-spacing: 0.8px; text-transform: uppercase; border-radius: 50px;">
                    <i class="fa-solid fa-building-columns me-1"></i> Academic Architecture
                </span>
                <h1 class="font-display fw-extrabold mb-2 text-white" style="font-size: 2.6rem; line-height: 1.15;">Departments &amp; Programs</h1>
                <p class="lead mb-3 text-white" style="font-size: 1.05rem; max-width: 600px; opacity: 0.85; line-height: 1.7;">
                    Explore all engineering departments, their academic overview, associated B.Tech programs, year-wise fees, seat availability, eligibility criteria and official syllabi — all in one unified portal.
                </p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white text-decoration-none" style="opacity: 0.75;"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                        <li class="breadcrumb-item active text-warning" aria-current="page">Departments &amp; Programs</li>
                    </ol>
                </nav>
            </div>
            <div class="col-lg-5">
                <!-- LIVE SEARCH BAR -->
                <div class="bg-white bg-opacity-10 p-3 rounded-4 border border-white border-opacity-15" style="backdrop-filter: blur(12px);">
                    <label for="globalSearch" class="form-label text-white small mb-2 fw-semibold opacity-80">
                        <i class="fa-solid fa-magnifying-glass me-1 text-warning"></i> Search Departments or Programs
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-0"><i class="fa-solid fa-search text-muted small"></i></span>
                        <input type="text" id="globalSearch" class="form-control border-0 py-2"
                               placeholder="e.g. Civil, CSE, Data Science, VLSI, Robotics…"
                               aria-label="Search departments and programs">
                        <button class="btn btn-warning btn-sm px-3 fw-bold" type="button" onclick="clearSearch()" id="clearBtn" style="display:none; border-radius: 0 8px 8px 0 !important;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2" id="quickFilterPills">
                        <span class="text-white small fw-bold d-block w-100" style="font-size: 14px; opacity: 0.85;">Filter by Department:</span>
                        <button type="button" class="btn btn-xs quick-pill active" onclick="showAll(this)"
                                style="font-size: 14px; padding: 3px 10px; border-radius: 50px; background: var(--accent); color: #fff; border: none;" id="pillAll">
                            All Depts
                        </button>
                        <?php foreach ($departments as $dept): ?>
                            <button type="button"
                                    class="btn btn-xs quick-pill"
                                    data-dept="<?php echo $dept['id']; ?>"
                                    onclick="filterByDept(<?php echo $dept['id']; ?>, this)"
                                    style="font-size: 14px; padding: 3px 10px; border-radius: 50px; background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.25);">
                                <?php echo htmlspecialchars($dept['code']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-1.5 mt-2 pt-2 border-top border-white border-opacity-10" id="typeFilterPills">
                        <span class="text-white small fw-bold d-block w-100" style="font-size: 14px; opacity: 0.85;">Filter by Program Type:</span>
                        <button type="button" class="btn btn-xs type-pill active" onclick="filterByProgType('all', this)"
                                style="font-size: 14px; padding: 3px 10px; border-radius: 50px; background: var(--accent); color: #fff; border: none;">
                            All Types
                        </button>
                        <button type="button" class="btn btn-xs type-pill" onclick="filterByProgType('regular', this)"
                                style="font-size: 14px; padding: 3px 10px; border-radius: 50px; background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.25);">
                            <i class="fa-solid fa-graduation-cap me-1"></i> Regular (4-Yr)
                        </button>
                        <button type="button" class="btn btn-xs type-pill" onclick="filterByProgType('integrated', this)"
                                style="font-size: 14px; padding: 3px 10px; border-radius: 50px; background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.25);">
                            <i class="fa-solid fa-bolt-lightning me-1"></i> Integrated (6-Yr)
                        </button>
                        <button type="button" class="btn btn-xs type-pill" onclick="filterByProgType('dse', this)"
                                style="font-size: 14px; padding: 3px 10px; border-radius: 50px; background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.25);">
                            <i class="fa-solid fa-route me-1"></i> Direct 2nd Year / Diploma
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     STICKY QUICK-JUMP NAVIGATION BAR
     ============================================================ -->
<div id="quickJumpBar" class="bg-white border-bottom shadow-sm" style="position: sticky; top: 62px; z-index: 190; transition: all 0.25s ease;">
    <div class="container">
        <div class="d-flex align-items-center gap-0" style="overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; white-space: nowrap;">
            <span class="text-muted small fw-semibold py-3 me-3 flex-shrink-0" style="font-size: 14px; letter-spacing: 0.5px; text-transform: uppercase;">
                <i class="fa-solid fa-layer-group text-warning me-1"></i> Jump to:
            </span>
            <?php foreach ($departments as $dept):
                $icon = $deptIcons[$dept['code']] ?? 'fa-graduation-cap';
            ?>
                <a href="#dept-<?php echo $dept['id']; ?>"
                   class="quick-jump-link flex-shrink-0 text-decoration-none py-3 px-3 border-bottom border-3 border-transparent fw-semibold small"
                   data-dept-id="<?php echo $dept['id']; ?>"
                   style="color: var(--text-secondary); transition: all 0.2s ease; font-size: 14px;">
                    <i class="fa-solid <?php echo $icon; ?> me-1 text-warning" style="font-size: 14px;"></i>
                    <?php echo htmlspecialchars($dept['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- NO RESULTS MESSAGE (hidden by default) -->
<div id="noResultsMsg" class="container my-5 d-none">
    <div class="text-center py-5">
        <i class="fa-solid fa-folder-magnifying-glass fa-3x text-muted opacity-40 mb-3"></i>
        <h4 class="text-muted fw-semibold">No departments or programs found</h4>
        <p class="text-muted small mb-3">Try a different keyword or browse all departments below.</p>
        <button class="btn btn-warning btn-sm px-4 rounded-pill fw-bold" onclick="clearSearch()">
            <i class="fa-solid fa-arrows-rotate me-1"></i> Clear Search &amp; Show All
        </button>
    </div>
</div>

<!-- ============================================================
     MAIN 2-COLUMN LAYOUT
     ============================================================ -->
<div class="container-fluid px-3 px-md-4 py-5" style="max-width: 1400px; margin: 0 auto;">
    <div class="row g-4 align-items-start">

        <!-- ====================================================
             LEFT: STICKY DEPARTMENT SIDEBAR NAVIGATION
             ==================================================== -->
        <div class="col-lg-3 col-xl-2 d-none d-lg-block">
            <div class="sticky-dept-sidebar" style="position: sticky; top: 130px; z-index: 100;">
                <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                    <div class="card-header border-0 py-3 px-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-bottom: 3px solid var(--accent) !important;">
                        <span class="text-white fw-bold small text-uppercase" style="letter-spacing: 0.8px; font-size: 14px;">
                            <i class="fa-solid fa-building-columns me-2 text-warning"></i>Departments
                        </span>
                    </div>
                    <nav class="p-2" aria-label="Department navigation">
                        <?php foreach ($departments as $dept):
                            $icon = $deptIcons[$dept['code']] ?? 'fa-graduation-cap';
                            $color = $deptColors[$dept['code']] ?? '#0f172a';
                            $deptCourses = $cms->getCourses($dept['id'], true);
                        ?>
                            <a href="#dept-<?php echo $dept['id']; ?>"
                               class="sidebar-dept-link d-flex align-items-center gap-2 px-3 py-2 rounded-3 mb-1 text-decoration-none"
                               data-dept-id="<?php echo $dept['id']; ?>"
                               style="color: var(--text-secondary); transition: all 0.2s ease; font-size: 14px; font-weight: 600;">
                                <span class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-2"
                                      style="width: 30px; height: 30px; background: <?php echo $color; ?>18; color: <?php echo $color; ?>; font-size: 14px;">
                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                </span>
                                <span class="flex-grow-1 text-truncate" style="max-width: 110px;">
                                    <?php echo htmlspecialchars($dept['code']); ?><br>
                                    <small class="text-muted fw-normal" style="font-size: 14px;"><?php echo count($deptCourses); ?> Programs</small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    <div class="px-3 pb-3">
                        <a href="admissions.php" class="btn btn-warning btn-sm w-100 fw-bold rounded-pill" style="font-size: 14px;">
                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Apply Now
                        </a>
                    </div>
                </div>

                <!-- Stats mini card -->
                <div class="card border-0 shadow-sm mt-3 text-center py-3 px-3" style="border-radius: 14px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0 !important;">
                    <div class="row g-0">
                        <div class="col-6 border-end">
                            <div class="fw-extrabold font-display text-primary" style="font-size: 1.4rem;"><?php echo count($departments); ?></div>
                            <small class="text-muted" style="font-size: 14px;">Departments</small>
                        </div>
                        <div class="col-6">
                            <?php $allCourses = $cms->getCourses(null, true); ?>
                            <div class="fw-extrabold font-display text-warning" style="font-size: 1.4rem;"><?php echo count($allCourses); ?></div>
                            <small class="text-muted" style="font-size: 14px;">Programs</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================================================
             RIGHT: DEPARTMENT CARDS + PROGRAMS ACCORDION
             ==================================================== -->
        <div class="col-lg-9 col-xl-10" id="deptProgramsArea">
            <?php if (empty($departments)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-40"></i>
                    <p>No departments registered yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($departments as $dept):
                    $icon = $deptIcons[$dept['code']] ?? 'fa-graduation-cap';
                    $color = $deptColors[$dept['code']] ?? '#0f172a';
                    $courses = $cms->getCourses($dept['id'], true);
                    $faculty_members = $cms->getFaculty($dept['id']);

                    $header_style = !empty($dept['image_path'])
                        ? "background: linear-gradient(to bottom, rgba(10,16,32,0.18) 0%, rgba(10,16,32,0.30) 35%, rgba(10,16,32,0.82) 75%, rgba(10,16,32,0.96) 100%), url('" . uploadUrl('departments', $dept['image_path']) . "') no-repeat center/cover;"
                        : "background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);";

                    // Build searchable text for this dept block
                    $deptSearchText = strtolower($dept['name'] . ' ' . $dept['code'] . ' ' . $dept['description']);
                    foreach ($courses as $c) {
                        $deptSearchText .= ' ' . strtolower($c['name'] . ' ' . $c['code'] . ' ' . ($c['description'] ?? ''));
                    }
                ?>
                <!-- ======================================
                     DEPARTMENT BLOCK
                     ====================================== -->
                <article class="dept-block mb-5"
                         id="dept-<?php echo $dept['id']; ?>"
                         data-dept-id="<?php echo $dept['id']; ?>"
                         data-search-text="<?php echo htmlspecialchars($deptSearchText, ENT_QUOTES); ?>">

                    <!-- Department Header Card -->
                    <div class="dept-header-card card border-0 overflow-hidden mb-3"
                         style="border-radius: 18px; border: 1px solid #e2e8f0 !important; box-shadow: 0 4px 24px rgba(15,23,42,0.09);">

                        <!-- Dept Cover Banner -->
                        <div class="position-relative" style="min-height: 220px; <?php echo $header_style; ?> border-bottom: 3px solid var(--accent);">
                            <div class="position-absolute top-0 start-0 end-0 bottom-0 d-flex align-items-end p-4">
                                <div class="d-flex align-items-center gap-3 w-100 flex-wrap">
                                    <!-- Icon badge -->
                                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow flex-shrink-0"
                                         style="width: 58px; height: 58px; background: rgba(15,23,42,0.65); border: 2px solid <?php echo $color; ?>88; backdrop-filter: blur(8px);">
                                        <i class="fa-solid <?php echo $icon; ?> fa-lg" style="color: var(--accent);"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h2 class="mb-1 fw-extrabold text-white font-display" style="font-size: 1.55rem; line-height: 1.2; text-shadow: 0 2px 12px rgba(0,0,0,0.95), 0 1px 4px rgba(0,0,0,0.9);">
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </h2>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="badge bg-warning text-dark fw-bold rounded-pill" style="font-size: 14px; letter-spacing: 0.3px; box-shadow: 0 1px 4px rgba(0,0,0,0.4);">
                                                <?php echo htmlspecialchars($dept['code']); ?> Dept.
                                            </span>
                                            <span class="badge rounded-pill" style="background: rgba(15,23,42,0.75); color: white; font-size: 14px; border: 1px solid rgba(255,255,255,0.25); backdrop-filter:blur(8px); box-shadow:0 1px 4px rgba(0,0,0,0.4);">
                                                <i class="fa-solid fa-book me-1"></i><?php echo count($courses); ?> Programs
                                            </span>
                                            <span class="badge rounded-pill" style="background: rgba(15,23,42,0.75); color: white; font-size: 14px; border: 1px solid rgba(255,255,255,0.25); backdrop-filter:blur(8px); box-shadow:0 1px 4px rgba(0,0,0,0.4);">
                                                <i class="fa-solid fa-chalkboard-user me-1"></i><?php echo count($faculty_members); ?> Faculty
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Faculty link -->
                                    <a href="faculty.php?dept_id=<?php echo $dept['id']; ?>"
                                       class="btn btn-sm fw-bold flex-shrink-0"
                                       style="font-size: 14px; background: rgba(15,23,42,0.80); color: white; border: 1px solid rgba(255,255,255,0.35); border-radius: 50px; backdrop-filter:blur(8px); box-shadow: 0 2px 8px rgba(0,0,0,0.5);">
                                        <i class="fa-solid fa-users me-1"></i> Faculty Directory
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Department Description Body -->
                        <div class="card-body px-4 py-3 bg-white">
                            <p class="text-muted mb-3" style="font-size: 14.5px; line-height: 1.75;">
                                <?php echo htmlspecialchars($dept['description'] ?: 'This department offers specialized engineering programs aligned with industry needs and research excellence.'); ?>
                            </p>

                            <?php if (!empty($dept['hod_name']) || !empty($dept['contact_email']) || !empty($dept['contact_phone']) || !empty($dept['facilities_text'])): ?>
                                <div class="row g-2 mb-3 small">
                                    <?php if (!empty($dept['hod_name'])): ?>
                                        <div class="col-md-6">
                                            <div class="p-2.5 bg-light rounded-3 border">
                                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 14px;">Head of Department (HOD)</small>
                                                <strong class="text-primary-color"><?php echo htmlspecialchars($dept['hod_name']); ?></strong>
                                                <?php if (!empty($dept['hod_designation'])): ?>
                                                    <span class="text-muted small"> · <?php echo htmlspecialchars($dept['hod_designation']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($dept['contact_email']) || !empty($dept['contact_phone'])): ?>
                                        <div class="col-md-6">
                                            <div class="p-2.5 bg-light rounded-3 border">
                                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 14px;">Department Contact</small>
                                                <?php if (!empty($dept['contact_email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($dept['contact_email']); ?>" class="text-decoration-none text-muted me-2"><i class="fa-solid fa-envelope text-warning me-1"></i><?php echo htmlspecialchars($dept['contact_email']); ?></a>
                                                <?php endif; ?>
                                                <?php if (!empty($dept['contact_phone'])): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($dept['contact_phone']); ?>" class="text-decoration-none text-muted"><i class="fa-solid fa-phone text-warning me-1"></i><?php echo htmlspecialchars($dept['contact_phone']); ?></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($dept['facilities_text'])): ?>
                                        <div class="col-12">
                                            <div class="p-2.5 bg-light rounded-3 border">
                                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 14px;"><i class="fa-solid fa-microscope text-primary me-1"></i>Department Laboratories &amp; Key Facilities</small>
                                                <span class="text-muted" style="font-size: 14px;"><?php echo htmlspecialchars($dept['facilities_text']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($faculty_members)): ?>
                            <!-- Top Faculty Mini Strip -->
                            <div class="d-flex flex-wrap gap-2 align-items-center pt-2 border-top">
                                <small class="text-muted fw-semibold" style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-user-tie text-warning me-1" style="font-size: 14px;"></i> Faculty:
                                </small>
                                <?php $topFaculty = array_slice($faculty_members, 0, 4); ?>
                                <?php foreach ($topFaculty as $f): ?>
                                    <div class="d-flex align-items-center gap-1 px-2 py-1 bg-light rounded-pill border" style="font-size: 14px;">
                                        <div class="rounded-circle bg-secondary overflow-hidden flex-shrink-0" style="width: 20px; height: 20px;">
                                            <?php if (!empty($f['image_path'])): ?>
                                                <img src="<?php echo uploadUrl('faculty', $f['image_path']); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>" class="w-100 h-100" style="object-fit:cover;" loading="lazy">
                                            <?php else: ?>
                                                <i class="fa-solid fa-user text-white" style="font-size: 14px; line-height: 20px; display: block; text-align: center;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="fw-semibold text-truncate" style="max-width: 100px;"><?php echo htmlspecialchars($f['name']); ?></span>
                                        <span class="text-muted">· <?php echo htmlspecialchars($f['designation']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($faculty_members) > 4): ?>
                                    <a href="faculty.php?dept_id=<?php echo $dept['id']; ?>" class="text-warning fw-bold small text-decoration-none" style="font-size: 14px;">+<?php echo count($faculty_members) - 4; ?> more →</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Programs Hierarchy: Main Program -> Program Types -> Individual Courses -->
                    <?php if (!empty($courses)): 
                        // Partition courses into 3 Program Types
                        $programTypeGroups = [
                            'regular' => [
                                'type_key' => 'regular',
                                'title'    => 'Regular Programs',
                                'badge'    => 'Regular (4-Year)',
                                'color'    => '#1e3a8a',
                                'bg'       => 'rgba(30, 58, 138, 0.05)',
                                'border'   => 'rgba(30, 58, 138, 0.18)',
                                'icon'     => 'fa-graduation-cap',
                                'subtitle' => '4-Year B.Tech Undergraduate Degree | 10+2 (HSC) PCM / MHT-CET / JEE',
                                'courses'  => []
                            ],
                            'integrated' => [
                                'type_key' => 'integrated',
                                'title'    => 'Integrated Programs',
                                'badge'    => 'Integrated (6-Year)',
                                'color'    => '#d97706',
                                'bg'       => 'rgba(217, 119, 6, 0.05)',
                                'border'   => 'rgba(217, 119, 6, 0.18)',
                                'icon'     => 'fa-bolt-lightning',
                                'subtitle' => '6-Year Dual Degree Pathway (Diploma + B.Tech) | Direct after 10th (SSC)',
                                'courses'  => []
                            ],
                            'dse' => [
                                'type_key' => 'dse',
                                'title'    => 'Direct Second Year / Diploma Programs',
                                'badge'    => 'Lateral Entry (DSE)',
                                'color'    => '#059669',
                                'bg'       => 'rgba(5, 150, 105, 0.05)',
                                'border'   => 'rgba(5, 150, 105, 0.18)',
                                'icon'     => 'fa-route',
                                'subtitle' => 'Lateral Entry into 2nd Year B.Tech | For Polytechnic / Diploma / B.Sc Holders',
                                'courses'  => []
                            ]
                        ];

                        foreach ($courses as $c) {
                            $code = strtoupper($c['code'] ?? '');
                            $name = $c['name'] ?? '';
                            if (stripos($code, 'DSE') !== false || stripos($name, 'Direct') !== false || stripos($name, 'Diploma') !== false) {
                                $programTypeGroups['dse']['courses'][] = $c;
                            } elseif (stripos($code, 'INT') !== false || stripos($name, 'Integrated') !== false) {
                                $programTypeGroups['integrated']['courses'][] = $c;
                            } else {
                                $programTypeGroups['regular']['courses'][] = $c;
                            }
                        }
                    ?>
                    <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                        <div>
                            <h3 class="mb-0 fw-bold font-display text-primary-color" style="font-size: 1.15rem;">
                                <i class="fa-solid fa-layer-group me-2 text-warning"></i>Main Program Curriculum &amp; Offerings
                                <span class="badge ms-2 rounded-pill" style="background: var(--accent); color: #fff; font-size: 14px;"><?php echo count($courses); ?> Total</span>
                            </h3>
                            <small class="text-muted" style="font-size: 14px;">Browse degree tracks partitioned by Program Type below</small>
                        </div>
                        <a href="admissions.php" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold" style="font-size: 14px;">
                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Apply
                        </a>
                    </div>

                    <!-- Program Types Wrapper -->
                    <div class="program-types-container">
                        <?php foreach ($programTypeGroups as $typeKey => $pGroup): 
                            if (empty($pGroup['courses'])) continue;
                            $groupCourses = $pGroup['courses'];
                        ?>
                        <div class="program-type-section mb-4" data-prog-type="<?php echo $typeKey; ?>">
                            <!-- Program Type Sub-Header -->
                            <div class="p-3 mb-2 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
                                 style="background: <?php echo $pGroup['bg']; ?>; border: 1.5px solid <?php echo $pGroup['border']; ?>;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill fw-bold"
                                          style="background: <?php echo $pGroup['color']; ?>; color: #fff; font-size: 14px; padding: 5px 12px; letter-spacing: 0.3px;">
                                        <i class="fa-solid <?php echo $pGroup['icon']; ?> me-1"></i> <?php echo htmlspecialchars($pGroup['title']); ?>
                                    </span>
                                    <span class="text-muted small d-none d-md-inline" style="font-size: 14px;">
                                        <?php echo htmlspecialchars($pGroup['subtitle']); ?>
                                    </span>
                                </div>
                                <span class="badge bg-white text-dark border rounded-pill px-2.5 py-1 fw-bold" style="font-size: 14px;">
                                    <?php echo count($groupCourses); ?> <?php echo count($groupCourses) === 1 ? 'Program' : 'Programs'; ?>
                                </span>
                            </div>

                            <!-- Individual Programs Accordion under this Type -->
                            <div class="accordion" id="accordionDept<?php echo $dept['id']; ?>_<?php echo $typeKey; ?>">
                                <?php foreach ($groupCourses as $idx => $course):
                                    $accordionId = "prog-{$course['id']}";
                                    $metric = $seatMetricsById[$course['id']] ?? null;
                                    $vacantSeats = $metric ? $metric['vacant_seats'] : $course['intake_capacity'];
                                    $isOpen = ($metric && $metric['is_open']);
                                    $filledSeats = $metric ? $metric['filled_seats'] : ($course['filled_seats'] ?? 0);
                                    $intakeCap  = (int)($course['intake_capacity'] ?? 0);
                                    $fillPct    = $intakeCap > 0 ? min(100, round(($filledSeats / $intakeCap) * 100)) : 0;

                                    // Build year-wise fee data
                                    $duration = max(1, (int)($course['duration_years'] ?? 4));
                                    $totalFee = 0;
                                    $yearFees = [];
                                    for ($y = 1; $y <= $duration; $y++) {
                                        $feeKey = "fee_year_{$y}";
                                        $feeVal = (float)($course[$feeKey] ?? ($course['fee_year_1'] ?? 0));
                                        $yearFees[$y] = $feeVal;
                                        $totalFee += $feeVal;
                                    }

                                    // Semester count
                                    $semCount = (int)($course['semester_count'] ?? $duration * 2);
                                ?>
                                <div class="accordion-item border mb-2 overflow-hidden program-accordion-item"
                                     style="border-radius: 14px !important; border-color: #e2e8f0 !important; box-shadow: 0 1px 6px rgba(0,0,0,0.04);"
                                     data-course-id="<?php echo $course['id']; ?>"
                                     data-prog-type="<?php echo $typeKey; ?>"
                                     data-search-text="<?php echo htmlspecialchars(strtolower($course['name'] . ' ' . $course['code'] . ' ' . ($course['description'] ?? '')), ENT_QUOTES); ?>">

                                    <!-- Accordion Header -->
                                    <h3 class="accordion-header" id="heading<?php echo $accordionId; ?>">
                                        <button class="accordion-button collapsed py-0 px-0 border-0"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#<?php echo $accordionId; ?>"
                                                aria-expanded="false"
                                                aria-controls="<?php echo $accordionId; ?>"
                                                style="background: transparent; box-shadow: none; border-radius: 14px !important;">
                                            <div class="d-flex align-items-center gap-3 w-100 py-3 px-4 flex-wrap">
                                                <!-- Degree type badge -->
                                                <span class="badge rounded-pill fw-bold flex-shrink-0"
                                                      style="background: <?php echo $pGroup['color']; ?>18; color: <?php echo $pGroup['color']; ?>; border: 1px solid <?php echo $pGroup['color']; ?>33; font-size: 14px; letter-spacing: 0.3px;">
                                                    <?php echo htmlspecialchars($pGroup['badge']); ?>
                                                </span>
                                                <!-- Program name -->
                                                <div class="flex-grow-1">
                                                    <span class="fw-bold font-display text-primary-color d-block" style="font-size: 14.5px; line-height: 1.35;">
                                                        <?php echo htmlspecialchars($course['name']); ?>
                                                    </span>
                                                    <?php
                                                    $courseVer = !empty($course['updated_at']) ? strtotime($course['updated_at']) : (!empty($course['created_at']) ? strtotime($course['created_at']) : time());
                                                    $brochureUrl = !empty($course['brochure_document']) ? (APP_URL . '/' . ltrim($course['brochure_document'], '/') . '?v=' . $courseVer) : '';
                                                    $hasAnySyllabusPdf = !empty($brochureUrl) || !empty($course['syllabus_pdf_year_1']) || !empty($course['syllabus_pdf_year_2']) || !empty($course['syllabus_pdf_year_3']) || !empty($course['syllabus_pdf_year_4']);
                                                    ?>
                                                    <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                                                        <span class="badge bg-secondary text-uppercase" style="font-size: 14px;"><?php echo htmlspecialchars($course['code']); ?></span>
                                                        <span class="text-muted" style="font-size: 14px;"><i class="fa-regular fa-clock me-1 text-warning" style="font-size: 14px;"></i><?php echo $duration; ?> Years &middot; <?php echo $semCount; ?> Sems</span>
                                                        <span class="text-muted" style="font-size: 14px;"><i class="fa-solid fa-users me-1 text-primary" style="font-size: 14px;"></i>Intake: <?php echo $intakeCap; ?></span>
                                                        <?php if ($hasAnySyllabusPdf): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" style="font-size: 12px;">
                                                                <i class="fa-solid fa-file-pdf me-1"></i>Syllabus PDF Available
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <!-- Seat status badge -->
                                                <span class="badge rounded-pill fw-bold flex-shrink-0 d-none d-sm-inline"
                                                      style="font-size: 14px; padding: 5px 12px; background: <?php echo $isOpen ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)'; ?>; color: <?php echo $isOpen ? '#059669' : '#dc2626'; ?>; border: 1px solid <?php echo $isOpen ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'; ?>;">
                                                    <i class="fa-solid <?php echo $isOpen ? 'fa-circle-check' : 'fa-circle-xmark'; ?> me-1" style="font-size: 14px;"></i>
                                                    <?php echo $isOpen ? "{$vacantSeats} Seats Available" : 'Admissions Closed'; ?>
                                                </span>
                                                <!-- Annual fee preview -->
                                                <div class="text-end flex-shrink-0 d-none d-md-block">
                                                    <small class="text-muted d-block" style="font-size: 14px;">Annual Fee</small>
                                                    <span class="fw-bold font-display text-primary-color" style="font-size: 14px;">₹<?php echo number_format($yearFees[1] ?? 0, 0); ?></span>
                                                </div>
                                            </div>
                                        </button>
                                    </h3>

                                    <!-- Accordion Body: Full Program Details -->
                                    <div id="<?php echo $accordionId; ?>"
                                         class="accordion-collapse collapse"
                                         aria-labelledby="heading<?php echo $accordionId; ?>">
                                        <div class="accordion-body pt-0 px-0 pb-0">
                                            <div style="border-top: 2px solid <?php echo $color; ?>22; background: #f8fafc;">

                                                <!-- Program image if available -->
                                                <?php if (!empty($course['image_path'])): ?>
                                                    <div style="height: 180px; overflow: hidden; position: relative;">
                                                        <img src="<?php echo uploadUrl('courses', $course['image_path']); ?>"
                                                             alt="<?php echo htmlspecialchars($course['name']); ?>"
                                                             class="w-100"
                                                             loading="lazy"
                                                             style="object-fit: cover; height: 100%;">
                                                        <div style="position:absolute; inset: 0; background: linear-gradient(to top, rgba(10,16,32,0.4) 0%, transparent 60%);"></div>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="p-4">

                                                    <!-- Description -->
                                                    <?php if (!empty($course['description'])): ?>
                                                        <p class="text-muted mb-4" style="font-size: 14px; line-height: 1.75; text-align: justify;">
                                                            <?php echo htmlspecialchars($course['description']); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <!-- Quick Info Pills -->
                                                    <div class="row g-3 mb-4">
                                                        <div class="col-6 col-sm-3">
                                                            <div class="p-3 bg-white rounded-3 border text-center h-100">
                                                                <i class="fa-regular fa-calendar-days text-warning d-block mb-1" style="font-size: 1.2rem;"></i>
                                                                <small class="text-muted d-block" style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Duration</small>
                                                                <strong class="text-primary-color font-display" style="font-size: 15px;"><?php echo $duration; ?> Years</strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-6 col-sm-3">
                                                            <div class="p-3 bg-white rounded-3 border text-center h-100">
                                                                <i class="fa-solid fa-book-open text-info d-block mb-1" style="font-size: 1.2rem;"></i>
                                                                <small class="text-muted d-block" style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Semesters</small>
                                                                <strong class="text-primary-color font-display" style="font-size: 15px;"><?php echo $semCount; ?></strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-6 col-sm-3">
                                                            <div class="p-3 bg-white rounded-3 border text-center h-100">
                                                                <i class="fa-solid fa-users text-success d-block mb-1" style="font-size: 1.2rem;"></i>
                                                                <small class="text-muted d-block" style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Intake / Vacant</small>
                                                                <strong class="text-primary-color font-display" style="font-size: 15px;">
                                                                    <?php echo $intakeCap; ?> / <span style="color: <?php echo $isOpen ? '#059669' : '#dc2626'; ?>"><?php echo $vacantSeats; ?></span>
                                                                </strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-6 col-sm-3">
                                                            <div class="p-3 bg-white rounded-3 border text-center h-100">
                                                                <i class="fa-solid fa-graduation-cap text-warning d-block mb-1" style="font-size: 1.2rem;"></i>
                                                                <small class="text-muted d-block" style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Session</small>
                                                                <strong class="text-primary-color font-display" style="font-size: 15px;">2026–27</strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Seat Fill Progress -->
                                                    <?php if ($intakeCap > 0): ?>
                                                    <div class="mb-4">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <small class="fw-semibold text-muted" style="font-size: 14px;">
                                                                <i class="fa-solid fa-chart-bar me-1"></i> Seat Fill Rate
                                                            </small>
                                                            <small class="fw-bold" style="font-size: 14px; color: <?php echo $fillPct >= 90 ? '#dc2626' : ($fillPct >= 60 ? '#d97706' : '#059669'); ?>">
                                                                <?php echo $filledSeats; ?>/<?php echo $intakeCap; ?> (<?php echo $fillPct; ?>% filled)
                                                            </small>
                                                        </div>
                                                        <div class="progress" style="height: 6px; border-radius: 50px; background: #e2e8f0;">
                                                            <div class="progress-bar"
                                                                 role="progressbar"
                                                                 style="width: <?php echo $fillPct; ?>%; border-radius: 50px; background: <?php echo $fillPct >= 90 ? '#dc2626' : ($fillPct >= 60 ? '#d97706' : '#059669'); ?>;"
                                                                 aria-valuenow="<?php echo $fillPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>

                                                    <!-- Eligibility & Admission Information -->
                                                    <div class="alert mb-4 border-0 py-3 px-4 rounded-3" style="background: rgba(59,130,246,0.07); border-left: 3px solid #3b82f6 !important;">
                                                        <h6 class="fw-bold mb-1 text-primary" style="font-size: 14px;"><i class="fa-solid fa-circle-check me-2" style="font-size: 14px;"></i>Eligibility Criteria</h6>
                                                        <p class="mb-1 text-muted" style="font-size: 14px; line-height: 1.6; text-align: justify;">
                                                            <?php if (!empty($course['eligibility'])): ?>
                                                                <?php echo htmlspecialchars($course['eligibility']); ?>
                                                            <?php elseif (stripos($course['code'], 'DSE') !== false || stripos($course['name'], 'Direct') !== false): ?>
                                                                Diploma in Engineering with minimum 45% marks in relevant branch (40% for reserved categories). Eligible for direct 2nd year lateral entry.
                                                            <?php elseif (stripos($course['name'], 'Integrated') !== false): ?>
                                                                10th Passed (SSC) with minimum 35% marks for 6-Year Integrated track or 10+2 (HSC) PCM for dual degree entry.
                                                            <?php else: ?>
                                                                10+2 (HSC) with Physics, Chemistry, Mathematics. Minimum 45% aggregate (40% for reserved categories). Valid MHT-CET / JEE Main score. Maharashtra State Board or equivalent.
                                                            <?php endif; ?>
                                                        </p>
                                                        <?php if (!empty($course['admission_info'])): ?>
                                                            <p class="mb-0 text-muted small pt-1 border-top" style="font-size: 14px;"><i class="fa-solid fa-circle-info text-info me-1"></i><strong>Admission Info:</strong> <?php echo htmlspecialchars($course['admission_info']); ?></p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Core Courses & Career Opportunities -->
                                                    <?php if (!empty($course['core_courses']) || !empty($course['career_opportunities'])): ?>
                                                        <div class="row g-3 mb-4">
                                                            <?php if (!empty($course['core_courses'])): 
                                                                $coreList = array_filter(array_map('trim', explode("\n", $course['core_courses'])));
                                                            ?>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-white rounded-3 border h-100">
                                                                        <h6 class="fw-bold text-primary-color mb-2 font-display" style="font-size: 14px;">
                                                                            <i class="fa-solid fa-book-open-reader text-warning me-2"></i>Core Courses &amp; Subjects
                                                                        </h6>
                                                                        <ul class="list-unstyled mb-0 d-flex flex-column gap-1.5 small text-muted">
                                                                            <?php foreach ($coreList as $cItem): ?>
                                                                                <li class="d-flex align-items-start gap-2">
                                                                                    <i class="fa-solid fa-check text-primary mt-1 flex-shrink-0" style="font-size: 12px;"></i>
                                                                                    <span><?php echo htmlspecialchars($cItem); ?></span>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($course['career_opportunities'])): 
                                                                $careerList = array_filter(array_map('trim', explode("\n", $course['career_opportunities'])));
                                                            ?>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-white rounded-3 border h-100">
                                                                        <h6 class="fw-bold text-primary-color mb-2 font-display" style="font-size: 14px;">
                                                                            <i class="fa-solid fa-briefcase text-success me-2"></i>Career Opportunities &amp; Roles
                                                                        </h6>
                                                                        <ul class="list-unstyled mb-0 d-flex flex-column gap-1.5 small text-muted">
                                                                            <?php foreach ($careerList as $carItem): ?>
                                                                                <li class="d-flex align-items-start gap-2">
                                                                                    <i class="fa-solid fa-arrow-trend-up text-success mt-1 flex-shrink-0" style="font-size: 12px;"></i>
                                                                                    <span><?php echo htmlspecialchars($carItem); ?></span>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Curriculum Overview -->
                                                    <?php if (!empty($course['curriculum_overview'])): ?>
                                                        <div class="p-3 bg-light rounded-3 border mb-4">
                                                            <h6 class="fw-bold text-primary-color mb-1 font-display" style="font-size: 14px;"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Curriculum Overview</h6>
                                                            <p class="mb-0 text-muted small" style="line-height: 1.6;"><?php echo htmlspecialchars($course['curriculum_overview']); ?></p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Year-Wise Fee Table -->
                                                    <h6 class="fw-bold text-primary-color mb-3 border-bottom pb-2 font-display" style="font-size: 14px;">
                                                        <i class="fa-solid fa-receipt text-warning me-2"></i>Year-Wise Fee Breakdown (A.Y. 2026–27)
                                                    </h6>
                                                    <div class="table-responsive mb-3 rounded-3 border">
                                                        <table class="table table-hover align-middle mb-0 small">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.4px;">Academic Year</th>
                                                                    <th style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.4px;">Semesters</th>
                                                                    <th class="text-end" style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.4px;">Annual Fee (INR)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $yearLabels = [1=>'1st Year',2=>'2nd Year',3=>'3rd Year',4=>'4th Year',5=>'5th Year',6=>'6th Year'];
                                                                foreach ($yearFees as $y => $feeVal):
                                                                    $semStart = ($y * 2) - 1;
                                                                    $semEnd   = $y * 2;
                                                                ?>
                                                                <tr>
                                                                    <td class="fw-bold"><i class="fa-solid fa-calendar-check text-warning me-2" style="font-size: 14px;"></i><?php echo $yearLabels[$y] ?? "Year {$y}"; ?></td>
                                                                    <td>Semester <?php echo $semStart; ?> &amp; <?php echo $semEnd; ?></td>
                                                                    <td class="text-end fw-bold text-primary-color">₹<?php echo number_format($feeVal, 2); ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <tr class="table-warning fw-bold">
                                                                    <td colspan="2" class="text-end fw-bold">Total Program Fee (<?php echo $duration; ?> Years):</td>
                                                                    <td class="text-end text-dark fw-extrabold font-display" style="font-size: 14px;">₹<?php echo number_format($totalFee, 2); ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="alert alert-light border small text-muted mb-4 py-2 px-3 rounded-3" style="font-size: 14px; text-align: justify;">
                                                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                                        <strong>Official MGM University Fee Structure A.Y. 2026-27:</strong> Total fees include Tuition, Eligibility, Student Activities, Insurance, Deposit, Welfare &amp; Disaster Relief Funds.
                                                        <em>Examination Fee: ₹2,500/semester | Alumni Fee (Final Year): ₹2,000.</em>
                                                    </div>

                                                    <!-- Syllabus PDF Section -->
                                                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 flex-wrap gap-2">
                                                        <h6 class="fw-bold text-primary-color mb-0 font-display" style="font-size: 14px;">
                                                            <i class="fa-solid fa-file-pdf text-danger me-2"></i>Year-Wise Official Syllabus Curriculum
                                                        </h6>
                                                        <?php if ($brochureUrl): ?>
                                                            <a href="<?php echo htmlspecialchars($brochureUrl); ?>" target="_blank" class="badge bg-warning text-dark text-decoration-none rounded-pill px-3 py-1.5 fw-bold shadow-xs" style="font-size: 12.5px;">
                                                                <i class="fa-solid fa-book-open me-1"></i> Official Curriculum Booklet PDF
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="row g-2 mb-4">
                                                        <?php for ($y = 1; $y <= min($duration, 4); $y++):
                                                            $pdfKey = "syllabus_pdf_year_{$y}"; ?>
                                                            <div class="col-sm-6">
                                                                <div class="d-flex align-items-center justify-content-between p-3 bg-white border rounded-3" style="font-size: 14px;">
                                                                    <div>
                                                                        <i class="fa-solid fa-file-lines me-2 text-danger"></i>
                                                                        <strong>Year <?php echo $y; ?> Syllabus</strong>
                                                                        <small class="text-muted d-block ms-4" style="font-size: 14px;">Sem <?php echo ($y*2-1); ?> &amp; <?php echo ($y*2); ?></small>
                                                                    </div>
                                                                    <?php if (!empty($course[$pdfKey])): ?>
                                                                        <a href="<?php echo uploadUrl('courses', $course[$pdfKey]) . '?v=' . $courseVer; ?>"
                                                                           target="_blank"
                                                                           class="btn btn-danger btn-xs rounded-pill fw-bold"
                                                                           aria-label="Download Year <?php echo $y; ?> Syllabus PDF">
                                                                            <i class="fa-solid fa-download me-1"></i> PDF
                                                                        </a>
                                                                    <?php elseif ($brochureUrl): ?>
                                                                        <a href="<?php echo htmlspecialchars($brochureUrl); ?>"
                                                                           target="_blank"
                                                                           class="btn btn-outline-primary btn-xs rounded-pill fw-semibold"
                                                                           aria-label="View Curriculum Booklet PDF">
                                                                            <i class="fa-solid fa-file-pdf me-1"></i> Booklet PDF
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary bg-opacity-20 text-secondary rounded-pill" style="font-size: 14px;">Available Soon</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>

                                                    <!-- CTA Buttons -->
                                                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-3 border-top">
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <a href="courses.php?course_id=<?php echo $course['id']; ?>"
                                                               class="btn btn-sm btn-outline-secondary rounded-pill fw-semibold"
                                                               style="font-size: 14px;">
                                                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Full Details
                                                            </a>
                                                            <?php if ($brochureUrl): ?>
                                                                <a href="<?php echo htmlspecialchars($brochureUrl); ?>"
                                                                   target="_blank"
                                                                   class="btn btn-sm btn-outline-primary rounded-pill fw-semibold"
                                                                   style="font-size: 14px;">
                                                                    <i class="fa-solid fa-file-pdf me-1"></i> Curriculum Booklet
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                        <a href="admissions.php?course_id=<?php echo $course['id']; ?>"
                                                           class="btn btn-sm btn-warning fw-bold rounded-pill text-dark"
                                                           style="font-size: 14px;">
                                                            <i class="fa-solid fa-right-to-bracket me-1"></i> Apply for This Program
                                                        </a>
                                                    </div>

                                                </div><!-- /p-4 -->
                                            </div>
                                        </div>
                                    </div><!-- /collapse -->
                                </div><!-- /accordion-item -->
                                <?php endforeach; ?>
                            </div><!-- /accordion per type -->
                        </div><!-- /program-type-section -->
                        <?php endforeach; ?>
                    </div><!-- /program-types-container -->

                    <?php elseif ($dept['code'] === 'ASH' || stripos($dept['name'], 'Applied Science') !== false): ?>
                        <div class="p-4 bg-white rounded-4 border shadow-xs mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-bold" style="font-size: 13px;">
                                    <i class="fa-solid fa-flask me-1"></i> First-Year (F.Y. B.Tech) Common Engineering Foundation
                                </span>
                            </div>
                            <p class="text-muted small mb-3">
                                The Department of Applied Science forms the academic bedrock for all B.Tech engineering disciplines during the first year, imparting core analytical, scientific, and communication competencies.
                            </p>
                            <div class="row g-2 small">
                                <div class="col-sm-6 col-md-3">
                                    <div class="p-2.5 bg-light rounded-3 border h-100">
                                        <strong class="text-dark d-block"><i class="fa-solid fa-atom text-primary me-1"></i> Engineering Physics</strong>
                                        <span class="text-muted" style="font-size: 13px;">Quantum mechanics, optics, lasers &amp; semiconductor physics</span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <div class="p-2.5 bg-light rounded-3 border h-100">
                                        <strong class="text-dark d-block"><i class="fa-solid fa-vial text-success me-1"></i> Engineering Chemistry</strong>
                                        <span class="text-muted" style="font-size: 13px;">Physical chemistry, nanomaterials, polymers &amp; green energy</span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <div class="p-2.5 bg-light rounded-3 border h-100">
                                        <strong class="text-dark d-block"><i class="fa-solid fa-square-root-variable text-warning me-1"></i> Engineering Mathematics</strong>
                                        <span class="text-muted" style="font-size: 13px;">Calculus, differential equations, linear algebra &amp; statistics</span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <div class="p-2.5 bg-light rounded-3 border h-100">
                                        <strong class="text-dark d-block"><i class="fa-solid fa-comments text-info me-1"></i> Communication Skills</strong>
                                        <span class="text-muted" style="font-size: 13px;">Technical writing, corporate presentation &amp; ethics</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-4 bg-light rounded-3 text-center text-muted border mb-3" style="font-size: 14px;">
                            <i class="fa-solid fa-info-circle me-2"></i> No programs listed for this department yet.
                        </div>
                    <?php endif; ?>

                    <!-- Apply CTA Banner (per dept) -->
                    <div class="rounded-4 p-4 d-flex flex-wrap gap-3 align-items-center justify-content-between mb-2"
                         style="background: linear-gradient(135deg, <?php echo $color; ?>12 0%, <?php echo $color; ?>06 100%); border: 1px solid <?php echo $color; ?>22;">
                        <div>
                            <h6 class="fw-bold mb-0 font-display" style="font-size: 14px;">Interested in <?php echo htmlspecialchars($dept['name']); ?>?</h6>
                            <small class="text-muted">Talk to our admissions desk for merit seat availability, cutoff, and fee installment plans.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="contact.php" class="btn btn-sm btn-outline-secondary rounded-pill fw-semibold" style="font-size: 14px;">
                                <i class="fa-solid fa-envelope me-1"></i> Inquire
                            </a>
                            <a href="admissions.php" class="btn btn-sm btn-warning rounded-pill fw-bold text-dark" style="font-size: 14px;">
                                <i class="fa-solid fa-paper-plane me-1"></i> Apply Online
                            </a>
                        </div>
                    </div>

                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     PAGE-SPECIFIC CSS
     ============================================================ -->
<style>
/* Quick-jump sticky bar underline on active link */
.quick-jump-link.active-jump {
    color: var(--primary) !important;
    border-bottom-color: var(--accent) !important;
    font-weight: 700 !important;
}
.quick-jump-link:hover {
    color: var(--primary) !important;
    background: #f8fafc;
}

/* Sidebar active */
.sidebar-dept-link.active-sidebar {
    background: var(--accent-light);
    color: var(--primary) !important;
}
.sidebar-dept-link:hover {
    background: var(--bg-muted);
    color: var(--primary) !important;
}

/* Accordion overrides */
.accordion-button:not(.collapsed) {
    background: #f8fafc !important;
    box-shadow: none !important;
    color: var(--primary) !important;
}
.accordion-button::after {
    filter: none !important;
    width: 16px;
    height: 16px;
    background-size: 16px;
    margin-right: 4px;
}
.accordion-item {
    border-radius: 14px !important;
}

/* Quick pill active */
.quick-pill.active {
    background: var(--accent) !important;
    color: var(--primary) !important;
    border-color: var(--accent) !important;
}

/* Scrollbar hidden for quick jump bar */
#quickJumpBar ::-webkit-scrollbar { display: none; }

/* Program accordion and type sections hidden by search / filter */
.program-accordion-item.search-hidden { display: none !important; }
.program-type-section.search-hidden { display: none !important; }
.program-type-section.type-hidden { display: none !important; }
.dept-block.search-hidden { display: none !important; }
.dept-block.type-hidden { display: none !important; }

/* Mobile: collapse sidebar into sticky top bar */
@media (max-width: 991.98px) {
    #quickJumpBar { top: 56px; }
}

/* Responsive accordion header stacking */
@media (max-width: 575.98px) {
    .accordion-button .d-flex { gap: 8px !important; }
}

/* Focus visible for accessibility */
.accordion-button:focus-visible,
.quick-jump-link:focus-visible,
.sidebar-dept-link:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
    border-radius: 8px;
}
</style>

<!-- ============================================================
     PAGE JAVASCRIPT: Search, Filter, ScrollSpy
     ============================================================ -->
<script>
(function() {
    'use strict';

    // DOM references
    var searchInput   = document.getElementById('globalSearch');
    var noResults     = document.getElementById('noResultsMsg');
    var clearBtn      = document.getElementById('clearBtn');
    var deptBlocks    = document.querySelectorAll('.dept-block');
    var quickLinks    = document.querySelectorAll('.quick-jump-link');
    var sidebarLinks  = document.querySelectorAll('.sidebar-dept-link');
    var debounceTimer = null;

    // ---- Live Search (debounced) ----
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(runSearch, 200);
        });
    }

    function runSearch() {
        var q = (searchInput.value || '').trim().toLowerCase();
        clearBtn.style.display = q.length > 0 ? 'flex' : 'none';

        var anyVisible = false;

        deptBlocks.forEach(function(block) {
            var deptText  = (block.getAttribute('data-search-text') || '').toLowerCase();
            var progItems = block.querySelectorAll('.program-accordion-item');
            var typeSections = block.querySelectorAll('.program-type-section');
            var anyProgVisible = false;

            if (q === '') {
                block.classList.remove('search-hidden');
                progItems.forEach(function(p) { p.classList.remove('search-hidden'); });
                typeSections.forEach(function(s) { s.classList.remove('search-hidden'); });
                anyVisible = true;
                return;
            }

            // Check each program
            progItems.forEach(function(p) {
                var progText = (p.getAttribute('data-search-text') || '').toLowerCase();
                if (progText.includes(q)) {
                    p.classList.remove('search-hidden');
                    anyProgVisible = true;
                } else {
                    p.classList.add('search-hidden');
                }
            });

            // Check each type section
            typeSections.forEach(function(s) {
                var visibleItems = s.querySelectorAll('.program-accordion-item:not(.search-hidden)');
                if (visibleItems.length > 0) {
                    s.classList.remove('search-hidden');
                } else {
                    s.classList.add('search-hidden');
                }
            });

            // Also match department-level text (name/code/desc)
            var deptMatch = deptText.includes(q);

            if (deptMatch || anyProgVisible) {
                block.classList.remove('search-hidden');
                if (deptMatch) {
                    progItems.forEach(function(p) { p.classList.remove('search-hidden'); });
                    typeSections.forEach(function(s) { s.classList.remove('search-hidden'); });
                }
                anyVisible = true;
            } else {
                block.classList.add('search-hidden');
            }
        });

        noResults.classList.toggle('d-none', anyVisible);
    }

    // ---- Clear search ----
    window.clearSearch = function() {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        deptBlocks.forEach(function(b) {
            b.classList.remove('search-hidden');
            b.classList.remove('type-hidden');
            b.querySelectorAll('.program-accordion-item').forEach(function(p) {
                p.classList.remove('search-hidden');
            });
            b.querySelectorAll('.program-type-section').forEach(function(s) {
                s.classList.remove('search-hidden');
                s.classList.remove('type-hidden');
            });
        });
        noResults.classList.add('d-none');
        // Reset quick pills
        document.querySelectorAll('.quick-pill').forEach(function(p) { p.classList.remove('active'); });
        var pillAll = document.getElementById('pillAll');
        if (pillAll) pillAll.classList.add('active');

        // Reset type pills
        document.querySelectorAll('.type-pill').forEach(function(p, idx) {
            if (idx === 0) {
                p.classList.add('active');
                p.style.background = 'var(--accent)';
                p.style.color = '#fff';
                p.style.border = 'none';
            } else {
                p.classList.remove('active');
                p.style.background = 'rgba(255,255,255,0.12)';
                p.style.color = 'rgba(255,255,255,0.85)';
                p.style.border = '1px solid rgba(255,255,255,0.25)';
            }
        });
    };

    // ---- Filter by Program Type (hero pills) ----
    window.filterByProgType = function(type, btn) {
        document.querySelectorAll('.type-pill').forEach(function(p) {
            p.classList.remove('active');
            p.style.background = 'rgba(255,255,255,0.12)';
            p.style.color = 'rgba(255,255,255,0.85)';
            p.style.border = '1px solid rgba(255,255,255,0.25)';
        });
        btn.classList.add('active');
        btn.style.background = 'var(--accent)';
        btn.style.color = '#fff';
        btn.style.border = 'none';

        var anyVisible = false;

        deptBlocks.forEach(function(block) {
            var typeSections = block.querySelectorAll('.program-type-section');
            var anySectionVisible = false;

            typeSections.forEach(function(section) {
                var secType = section.getAttribute('data-prog-type');
                if (type === 'all' || secType === type) {
                    section.classList.remove('type-hidden');
                    anySectionVisible = true;
                } else {
                    section.classList.add('type-hidden');
                }
            });

            if (type === 'all' || anySectionVisible) {
                block.classList.remove('type-hidden');
                anyVisible = true;
            } else {
                block.classList.add('type-hidden');
            }
        });

        noResults.classList.toggle('d-none', anyVisible);
    };

    // ---- Filter by department (quick pill) ----
    window.filterByDept = function(deptId, btn) {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        noResults.classList.add('d-none');

        // Reset all pills, activate clicked
        document.querySelectorAll('.quick-pill').forEach(function(p) { p.classList.remove('active'); });
        var pillAll = document.getElementById('pillAll');
        if (pillAll) pillAll.classList.remove('active');
        btn.classList.add('active');

        deptBlocks.forEach(function(b) {
            if (parseInt(b.getAttribute('data-dept-id')) === deptId) {
                b.classList.remove('search-hidden');
                b.classList.remove('type-hidden');
                b.querySelectorAll('.program-accordion-item').forEach(function(p) { p.classList.remove('search-hidden'); });
                b.querySelectorAll('.program-type-section').forEach(function(s) { s.classList.remove('search-hidden'); s.classList.remove('type-hidden'); });
                // Smooth scroll to this dept
                setTimeout(function() {
                    b.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 50);
            } else {
                b.classList.add('search-hidden');
            }
        });
    };

    // ---- Show all ----
    window.showAll = function(btn) {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        noResults.classList.add('d-none');
        document.querySelectorAll('.quick-pill').forEach(function(p) { p.classList.remove('active'); });
        btn.classList.add('active');
        deptBlocks.forEach(function(b) {
            b.classList.remove('search-hidden');
            b.classList.remove('type-hidden');
            b.querySelectorAll('.program-accordion-item').forEach(function(p) { p.classList.remove('search-hidden'); });
            b.querySelectorAll('.program-type-section').forEach(function(s) { s.classList.remove('search-hidden'); s.classList.remove('type-hidden'); });
        });
    };

    // ---- ScrollSpy for quick-jump and sidebar ----
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var id = entry.target.id;
                var deptId = id.replace('dept-', '');

                quickLinks.forEach(function(l) {
                    l.classList.toggle('active-jump', l.getAttribute('data-dept-id') === deptId);
                });
                sidebarLinks.forEach(function(l) {
                    l.classList.toggle('active-sidebar', l.getAttribute('data-dept-id') === deptId);
                });
            }
        });
    }, { rootMargin: '-80px 0px -60% 0px', threshold: 0.05 });

    deptBlocks.forEach(function(block) { observer.observe(block); });

    // ---- Smooth scroll for quick-jump and sidebar links ----
    quickLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            clearSearch();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                var offset = 130;
                var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    });
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            clearSearch();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                var offset = 130;
                var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    });

})();
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
