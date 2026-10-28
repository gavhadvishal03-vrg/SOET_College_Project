<?php
/**
 * Public Academic Programs, Syllabi & Fee Structure Controller & View
 *
 * Showcases full degree programs offered at SOET MGM University:
 * - B.Tech 4-Year (Post-HSC), B.Tech Integrated 6-Year (Post-SSC), Direct Second Year (DSE), and Diploma
 * - Real-time seat matrix counters, vacant seat alerts, and admission eligibility
 * - Complete 4-year / 6-year tuition fee breakdown with scholarship concessions
 * - Downloadable academic curriculum booklets and syllabi
 * - Client-side live search and multi-category filtering (UG, Integrated, DSE, Diploma)
 */
$page_title = "Academic Programs, Fees & Syllabi";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$courses_bg = !empty($settings['courses_hero_image']) ? uploadUrl('settings', $settings['courses_hero_image']) : uploadUrl('gallery', 'soet_robotics_mechatronics_lab.jpg');
$courses = $cms->getCourses(null, true);
$seatMetrics = $cms->getCourseSeatMetrics();
$seatMetricsById = [];
foreach ($seatMetrics as $m) {
    $seatMetricsById[$m['course_id']] = $m;
}

$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
?>

<!-- Page Hero Banner -->
<div class="page-hero mb-5" style="<?php echo $courses_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{$courses_bg}') center/cover no-repeat;" : ""; ?>">
    <div class="container position-relative z-1">
        <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill mb-2 fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">
            <i class="fa-solid fa-graduation-cap me-1"></i> MGM University School of Engineering &amp; Technology
        </span>
        <h1 class="font-display fw-extrabold mb-2 text-white">Programs, Curriculum &amp; Fees</h1>
        <p class="lead opacity-80 mb-3" style="font-size: 1.1rem; max-width: 700px;">Explore B.Tech 4-Year (after HSC), B.Tech Integrated 6-Year (after SSC), Lateral Entry, and Diploma Programs with Core Courses, Career Pathways &amp; Fee Details.</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Programs &amp; Fees</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Sidebar Filter / Quick Links -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-3.5 bg-white mb-4 position-sticky" style="top: 90px; z-index: 10; border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                <!-- Live Search in Sidebar -->
                <div class="mb-3">
                    <label class="form-label font-semibold text-muted small mb-1.5"><i class="fa-solid fa-filter text-warning me-1"></i> Quick Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="courseFilterInput" class="form-control border-start-0 ps-0" placeholder="Search branch or code...">
                    </div>
                </div>

                <!-- Program Category Tabs Filter -->
                <div class="mb-3">
                    <label class="form-label font-semibold text-muted small mb-1.5"><i class="fa-solid fa-tags text-warning me-1"></i> Program Category</label>
                    <div class="d-flex flex-column gap-1.5">
                        <button class="btn btn-sm btn-outline-primary text-start font-semibold rounded-pill py-1.5 px-3 active category-filter-btn" data-cat="all" onclick="filterByCategory('all', this)">
                            <i class="fa-solid fa-layer-group me-1.5"></i> All Programs (<?php echo count($courses); ?>)
                        </button>
                        <button class="btn btn-sm btn-outline-primary text-start font-semibold rounded-pill py-1.5 px-3 category-filter-btn" data-cat="ug4" onclick="filterByCategory('ug4', this)">
                            <i class="fa-solid fa-laptop-code me-1.5"></i> UG 4-Year (After HSC)
                        </button>
                        <button class="btn btn-sm btn-outline-primary text-start font-semibold rounded-pill py-1.5 px-3 category-filter-btn" data-cat="int6" onclick="filterByCategory('int6', this)">
                            <i class="fa-solid fa-school me-1.5"></i> Integrated 6-Year (After SSC)
                        </button>
                        <button class="btn btn-sm btn-outline-primary text-start font-semibold rounded-pill py-1.5 px-3 category-filter-btn" data-cat="dse" onclick="filterByCategory('dse', this)">
                            <i class="fa-solid fa-angles-right me-1.5"></i> Direct 2nd Year (Lateral)
                        </button>
                        <button class="btn btn-sm btn-outline-primary text-start font-semibold rounded-pill py-1.5 px-3 category-filter-btn" data-cat="dip" onclick="filterByCategory('dip', this)">
                            <i class="fa-solid fa-certificate me-1.5"></i> Diploma Programs
                        </button>
                    </div>
                </div>

                <div class="card border-0 shadow-xs p-3.5 text-white text-center mt-3 rounded-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-top: 3px solid var(--accent) !important;">
                    <i class="fa-solid fa-headset fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1 font-display">Need Admission Advice?</h6>
                    <p class="small text-white-50 mb-3" style="font-size: 14px; line-height: 1.5;">Connect with our counseling cell for cutoff, merit seats, and fee installments.</p>
                    <a href="admissions.php" class="btn btn-sm btn-warning text-dark font-bold py-1.5 px-3 rounded-pill shadow-xs w-100"><i class="fa-solid fa-paper-plane me-1"></i> Apply Online</a>
                </div>
            </div>
        </div>

        <!-- Course Listings, Core Courses, Career Opportunities & Fees -->
        <div class="col-lg-9" id="courseDetailsArea">
            
            <!-- Active Filter Notification Bar -->
            <div id="filterNotificationBar" class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center mb-4 py-3 px-4 rounded-3 d-none">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-filter fa-lg text-dark me-3"></i>
                    <div>
                        <small class="text-uppercase fw-bold text-muted d-block opacity-75" style="font-size: 14px;">Currently Filtered View</small>
                        <strong class="text-dark fs-6 font-display" id="activeCourseFilterName">Selected Filter</strong>
                    </div>
                </div>
                <button type="button" class="btn btn-dark btn-sm font-semibold px-3 rounded-pill" onclick="resetAllFilters()">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Reset Filters
                </button>
            </div>

            <?php if (empty($courses)): ?>
                <div class="card border-0 shadow-sm p-5 text-center text-muted" style="border-radius: 16px;">
                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-muted opacity-50"></i>
                    <p class="mb-0">No courses listed at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): 
                    $y1_fee = $course['fee_year_1'] ?? 150000.00;
                    $y2_fee = $course['fee_year_2'] ?? 150000.00;
                    $y3_fee = $course['fee_year_3'] ?? 150000.00;
                    $y4_fee = $course['fee_year_4'] ?? 150000.00;
                    $total_tuition = $y1_fee + $y2_fee + $y3_fee + $y4_fee;
                    $metric = $seatMetricsById[$course['id']] ?? null;
                    $vacantSeats = $metric ? $metric['vacant_seats'] : $course['intake_capacity'];
                    $isOpen = ($metric && $metric['is_open']);

                    $cCode = strtoupper($course['code'] ?? '');
                    $cName = $course['name'] ?? '';
                    $categoryTag = 'ug4';
                    $pTypeBadge = 'UG 4-Year B.Tech (After HSC)';
                    $pTypeColor = '#1e3a8a';

                    if (stripos($cCode, 'DIP') !== false || stripos($cCode, 'ADIP') !== false || stripos($cName, 'Diploma in') !== false || stripos($cName, 'Advanced Diploma') !== false) {
                        $categoryTag = 'dip';
                        $pTypeBadge = 'Diploma Program';
                        $pTypeColor = '#b45309';
                    } elseif (stripos($cCode, 'DSE') !== false || stripos($cName, 'Direct 2nd Year') !== false) {
                        $categoryTag = 'dse';
                        $pTypeBadge = 'Direct 2nd Year (Lateral Entry)';
                        $pTypeColor = '#059669';
                    } elseif (stripos($cCode, 'INT') !== false || stripos($cName, 'Integrated') !== false) {
                        $categoryTag = 'int6';
                        $pTypeBadge = 'UG 6-Year B.Tech Integrated (After SSC)';
                        $pTypeColor = '#d97706';
                    }
                ?>
                    <div class="card border-0 shadow-sm mb-5 overflow-hidden course-detail-card hover-lift" 
                         id="course-card-<?php echo $course['id']; ?>" 
                         data-course-id="<?php echo $course['id']; ?>"
                         data-category="<?php echo $categoryTag; ?>"
                         data-course-name="<?php echo strtolower(htmlspecialchars($course['name'])); ?>"
                         data-course-code="<?php echo strtolower(htmlspecialchars($course['code'])); ?>"
                         style="border-radius: 18px; border: 1px solid #e2e8f0 !important;">
                        
                        <?php if (!empty($course['image_path'])): ?>
                            <div style="height: 180px; overflow: hidden; position: relative;">
                                <img src="<?php echo uploadUrl('courses', $course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['name']); ?>" class="w-100 h-100" style="object-fit: cover;" loading="lazy" decoding="async">
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 60px; background: linear-gradient(to top, rgba(0,0,0,0.35), transparent);"></div>
                            </div>
                        <?php endif; ?>

                        <div class="card-body p-4 p-md-4 bg-white">
                            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1.5 flex-wrap">
                                        <span class="badge rounded-pill fw-bold" style="background: <?php echo $pTypeColor; ?>15; color: <?php echo $pTypeColor; ?>; border: 1px solid <?php echo $pTypeColor; ?>35; font-size: 14px;">
                                            <i class="fa-solid fa-layer-group me-1"></i><?php echo $pTypeBadge; ?>
                                        </span>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($course['department_name'] ?? 'Engineering & Technology'); ?></span>
                                    </div>
                                    <h3 class="fw-bold text-primary-color mb-1 font-display"><?php echo htmlspecialchars($course['name']); ?></h3>
                                    <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($course['code']); ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?php echo $isOpen ? 'bg-success' : 'bg-danger'; ?> px-3 py-1.5 mb-1 rounded-pill">
                                        <i class="fa-solid <?php echo $isOpen ? 'fa-circle-check' : 'fa-circle-xmark'; ?> me-1"></i>
                                        <?php echo $isOpen ? "{$vacantSeats} Vacant Seats" : 'Admissions Closed'; ?>
                                    </span>
                                    <small class="text-muted d-block">Approved Intake: <strong><?php echo $course['intake_capacity']; ?> Seats</strong></small>
                                </div>
                            </div>
                            
                            <p class="text-muted" style="line-height: 1.7;"><?php echo htmlspecialchars($course['description']); ?></p>

                            <!-- Key Metrics Highlights -->
                            <div class="row g-3 my-2 text-center">
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3 border border-light">
                                        <small class="text-muted d-block font-semibold">Duration</small>
                                        <strong class="text-primary-color font-display"><?php echo $course['duration_years']; ?> Years</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3 border border-light">
                                        <small class="text-muted d-block font-semibold">Semesters</small>
                                        <strong class="text-primary-color font-display"><?php echo $course['semester_count']; ?> Semesters</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3 border border-light">
                                        <small class="text-muted d-block font-semibold">Teaching Mode</small>
                                        <strong class="text-primary-color font-display">Project-Based (PBL)</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3 border border-light">
                                        <small class="text-muted d-block font-semibold">Academic Session</small>
                                        <strong class="text-primary-color font-display">2026-2027</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Brochure Details: Core Courses & Career Opportunities -->
                            <?php if (!empty($course['core_courses']) || !empty($course['career_opportunities'])): ?>
                                <div class="row g-4 mt-2 mb-3">
                                    <?php if (!empty($course['core_courses'])): 
                                        $coreList = array_filter(array_map('trim', explode("\n", $course['core_courses'])));
                                    ?>
                                        <div class="col-md-6">
                                            <div class="p-3.5 bg-light rounded-3 border h-100">
                                                <h6 class="fw-bold text-primary-color mb-2.5 font-display d-flex align-items-center">
                                                    <i class="fa-solid fa-book-open-reader text-warning me-2"></i>Core Courses &amp; Subjects
                                                </h6>
                                                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small text-muted">
                                                    <?php foreach ($coreList as $cItem): ?>
                                                        <li class="d-flex align-items-start gap-2">
                                                            <i class="fa-solid fa-check text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
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
                                            <div class="p-3.5 bg-light rounded-3 border h-100">
                                                <h6 class="fw-bold text-primary-color mb-2.5 font-display d-flex align-items-center">
                                                    <i class="fa-solid fa-briefcase text-success me-2"></i>Career Opportunities &amp; Roles
                                                </h6>
                                                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small text-muted">
                                                    <?php foreach ($careerList as $carItem): ?>
                                                        <li class="d-flex align-items-start gap-2">
                                                            <i class="fa-solid fa-arrow-trend-up text-success mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                                                            <span><?php echo htmlspecialchars($carItem); ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Eligibility & Admission Information -->
                            <?php if (!empty($course['eligibility']) || !empty($course['admission_info'])): ?>
                                <div class="alert alert-info border-0 bg-primary bg-opacity-10 small text-dark mb-4 py-2.5 px-3 rounded-3">
                                    <?php if (!empty($course['eligibility'])): ?>
                                        <p class="mb-1"><i class="fa-solid fa-id-card-clip text-primary me-2"></i><strong>Eligibility:</strong> <?php echo htmlspecialchars($course['eligibility']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($course['admission_info'])): ?>
                                        <p class="mb-0"><i class="fa-solid fa-circle-info text-info me-2"></i><strong>Admission Info:</strong> <?php echo htmlspecialchars($course['admission_info']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Curriculum Overview -->
                            <?php if (!empty($course['curriculum_overview'])): ?>
                                <div class="p-3 bg-light rounded-3 border mb-4">
                                    <h6 class="fw-bold text-primary-color mb-1.5 font-display"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Curriculum Highlights</h6>
                                    <p class="mb-0 text-muted small" style="line-height: 1.6;"><?php echo htmlspecialchars($course['curriculum_overview']); ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Year-Wise Fees Table -->
                            <?php 
                            $activeAY = $cms->getActiveAcademicYear();
                            $ywFees = $cms->getCourseYearWiseFees($course['id'], $activeAY);
                            $duration = max(1, (int)$course['duration_years']);
                            $total_program_cost = 0;
                            ?>
                            <div class="d-flex justify-content-between align-items-center mt-4 mb-3 border-bottom pb-2 flex-wrap gap-2">
                                <h5 class="fw-bold text-primary-color mb-0 font-display">
                                    <i class="fa-solid fa-receipt text-warning me-2"></i>Year-Wise Fee Breakdown (INR ₹)
                                </h5>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-semibold">
                                    <i class="fa-solid fa-calendar-check me-1"></i> Academic Session <?php echo htmlspecialchars($activeAY); ?>
                                </span>
                            </div>

                            <div class="table-responsive mb-3 rounded-3 border">
                                <table class="table table-hover align-middle mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Study Year / Level</th>
                                            <th>Semesters</th>
                                            <th class="text-end">Tuition Fee</th>
                                            <th class="text-end">Dev. Fee</th>
                                            <th class="text-end">Exam &amp; Other</th>
                                            <th class="text-end pe-3">Annual Total (INR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($ywFees)): ?>
                                            <?php foreach ($ywFees as $yf): 
                                                $total_program_cost += (float)$yf['total_fee'];
                                                $otherCharges = (float)$yf['exam_fee'] + (float)$yf['other_fee'];
                                            ?>
                                                <tr>
                                                    <td class="fw-bold text-dark">
                                                        <i class="fa-solid fa-calendar-check text-warning me-1.5"></i>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill me-1"><?php echo htmlspecialchars($yf['year_name']); ?></span>
                                                    </td>
                                                    <td class="text-muted"><?php echo htmlspecialchars($yf['semesters_included']); ?></td>
                                                    <td class="text-end">₹<?php echo number_format($yf['tuition_fee'], 2); ?></td>
                                                    <td class="text-end">₹<?php echo number_format($yf['development_fee'], 2); ?></td>
                                                    <td class="text-end text-muted">₹<?php echo number_format($otherCharges, 2); ?></td>
                                                    <td class="text-end pe-3 fw-bold text-primary-color fs-6">₹<?php echo number_format($yf['total_fee'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php 
                                            for ($y = 1; $y <= $duration; $y++):
                                                $feeKey = "fee_year_{$y}";
                                                $feeVal = (float)($course[$feeKey] ?? ($course['fee_year_1'] ?? 0));
                                                $total_program_cost += $feeVal;
                                                $semStart = ($y * 2) - 1;
                                                $semEnd = $y * 2;
                                                $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', 5 => '5th Year', 6 => '6th Year'];
                                                $yLabel = $yearLabels[$y] ?? "Year {$y}";
                                                $tuitionFallback = round($feeVal * 0.8695, 2);
                                                $devFallback = round($feeVal * 0.087, 2);
                                                $otherFallback = round($feeVal - ($tuitionFallback + $devFallback), 2);
                                            ?>
                                                <tr>
                                                    <td class="fw-bold"><i class="fa-solid fa-calendar-check text-warning me-2"></i><?php echo $yLabel; ?></td>
                                                    <td>Semester <?php echo $semStart; ?> &amp; Semester <?php echo $semEnd; ?></td>
                                                    <td class="text-end">₹<?php echo number_format($tuitionFallback, 2); ?></td>
                                                    <td class="text-end">₹<?php echo number_format($devFallback, 2); ?></td>
                                                    <td class="text-end text-muted">₹<?php echo number_format($otherFallback, 2); ?></td>
                                                    <td class="text-end pe-3 fw-bold text-primary-color">₹<?php echo number_format($feeVal, 2); ?></td>
                                                </tr>
                                            <?php endfor; ?>
                                        <?php endif; ?>
                                        <tr class="table-warning fw-bold">
                                            <td colspan="5" class="text-end">Total Program Fee (<?php echo count($ywFees) ?: $duration; ?> Study Years):</td>
                                            <td class="text-end pe-3 text-dark fs-6 font-display">₹<?php echo number_format($total_program_cost, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-light border small text-muted mb-4 py-2 px-3 rounded-3">
                                <i class="fa-solid fa-circle-info text-primary me-1.5"></i> <strong>Official MGM University Fee Structure (A.Y. <?php echo htmlspecialchars($activeAY); ?>):</strong> Total fees include Tuition Fee, Development Fee, Examination Fees (₹2,500/semester), Student Activities, Library, Insurance, Deposit, and Welfare Funds. <em>Alumni Fee (Final Year): ₹2,000.</em>
                            </div>

                            <!-- Year-Wise Official Syllabus Curriculum Section -->
                            <?php 
                            $courseVer = !empty($course['updated_at']) ? strtotime($course['updated_at']) : (!empty($course['created_at']) ? strtotime($course['created_at']) : time());
                            $brochureUrl = !empty($course['brochure_document']) ? (APP_URL . '/' . ltrim($course['brochure_document'], '/') . '?v=' . $courseVer) : '';
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 flex-wrap gap-2">
                                <h5 class="fw-bold text-primary-color mb-0 font-display">
                                    <i class="fa-solid fa-file-pdf text-danger me-2"></i>Year-Wise Official Syllabus Curriculum
                                </h5>
                                <?php if ($brochureUrl): ?>
                                    <a href="<?php echo htmlspecialchars($brochureUrl); ?>" target="_blank" class="badge bg-warning text-dark text-decoration-none rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                        <i class="fa-solid fa-book-open me-1"></i> Official Curriculum Booklet PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="row g-2 mb-4">
                                <?php for ($y = 1; $y <= min($duration, 4); $y++): 
                                    $pdfKey = "syllabus_pdf_year_{$y}";
                                    $hasYearPdf = !empty($course[$pdfKey]);
                                ?>
                                    <div class="col-sm-6">
                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light border rounded-3">
                                            <div>
                                                <i class="fa-solid fa-file-lines me-2 text-danger"></i>
                                                <strong class="text-dark">Year <?php echo $y; ?> Syllabus</strong>
                                                <small class="text-muted d-block ms-4">Sem <?php echo ($y * 2 - 1); ?> &amp; Sem <?php echo ($y * 2); ?></small>
                                            </div>
                                            <?php if ($hasYearPdf): ?>
                                                <a href="<?php echo uploadUrl('courses', $course[$pdfKey]) . '?v=' . $courseVer; ?>"
                                                   target="_blank"
                                                   class="btn btn-danger btn-xs rounded-pill fw-bold px-3 py-1"
                                                   aria-label="Download Year <?php echo $y; ?> Syllabus PDF">
                                                    <i class="fa-solid fa-download me-1"></i> View PDF
                                                </a>
                                            <?php elseif ($brochureUrl): ?>
                                                <a href="<?php echo htmlspecialchars($brochureUrl); ?>"
                                                   target="_blank"
                                                   class="btn btn-outline-primary btn-xs rounded-pill fw-semibold px-2.5 py-1"
                                                   aria-label="View Curriculum Booklet PDF">
                                                    <i class="fa-solid fa-file-pdf me-1"></i> Booklet PDF
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-20 text-secondary rounded-pill" style="font-size: 13px;">Available Soon</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="admissions.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm px-4 rounded-pill font-semibold">
                                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Apply for this Program
                                    </a>
                                    <?php if ($brochureUrl): ?>
                                        <a href="<?php echo htmlspecialchars($brochureUrl); ?>" target="_blank" class="btn btn-warning text-dark btn-sm px-3.5 rounded-pill font-bold shadow-xs">
                                            <i class="fa-solid fa-file-pdf me-1"></i> Curriculum Booklet
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <a href="contact.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                                    <i class="fa-solid fa-envelope me-1"></i> Inquire at Admissions Cell
                                </a>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function filterByCategory(cat, btn) {
    document.querySelectorAll('.category-filter-btn').forEach(function(b) {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-primary');
    });
    if (btn) {
        btn.classList.add('active', 'btn-primary');
        btn.classList.remove('btn-outline-primary');
    }

    var cards = document.querySelectorAll('.course-detail-card');
    cards.forEach(function(card) {
        var cardCat = card.getAttribute('data-category');
        if (cat === 'all' || cardCat === cat) {
            card.classList.remove('d-none');
        } else {
            card.classList.add('d-none');
        }
    });

    var titleMap = {
        'all': 'All Programs',
        'ug4': 'UG 4-Year B.Tech (After HSC)',
        'int6': 'UG 6-Year B.Tech Integrated (After SSC)',
        'dse': 'Direct 2nd Year (Lateral Entry)',
        'dip': 'Diploma Programs'
    };

    if (cat !== 'all') {
        document.getElementById('activeCourseFilterName').innerText = titleMap[cat] || cat;
        document.getElementById('filterNotificationBar').classList.remove('d-none');
    } else {
        document.getElementById('filterNotificationBar').classList.add('d-none');
    }
}

function resetAllFilters() {
    var cards = document.querySelectorAll('.course-detail-card');
    cards.forEach(function(card) {
        card.classList.remove('d-none');
    });
    document.querySelectorAll('.category-filter-btn').forEach(function(b) {
        if (b.getAttribute('data-cat') === 'all') {
            b.classList.add('active', 'btn-primary');
            b.classList.remove('btn-outline-primary');
        } else {
            b.classList.remove('active', 'btn-primary');
            b.classList.add('btn-outline-primary');
        }
    });
    var fi = document.getElementById('courseFilterInput');
    if (fi) fi.value = '';
    document.getElementById('filterNotificationBar').classList.add('d-none');
}

// Live Search Filter
document.addEventListener('DOMContentLoaded', function() {
    var filterInput = document.getElementById('courseFilterInput');
    if (filterInput) {
        filterInput.addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            var cards = document.querySelectorAll('.course-detail-card');
            cards.forEach(function(card) {
                var name = card.getAttribute('data-course-name') || '';
                var code = card.getAttribute('data-course-code') || '';
                if (name.includes(q) || code.includes(q)) {
                    card.classList.remove('d-none');
                } else {
                    card.classList.add('d-none');
                }
            });
        });
    }

    var initialCourseId = <?php echo $selected_course_id; ?>;
    if (initialCourseId > 0) {
        var targetCard = document.getElementById('course-card-' + initialCourseId);
        if (targetCard) {
            targetCard.scrollIntoView({ behavior: 'smooth' });
        }
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
