<?php
$page_title = "Courses, Year-Wise Fees & Syllabus";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$courses_bg = !empty($settings['courses_hero_image']) ? uploadUrl('settings', $settings['courses_hero_image']) : null;
$courses = $cms->getCourses(null, true);
$seatMetrics = $cms->getCourseSeatMetrics();
$seatMetricsById = [];
foreach ($seatMetrics as $m) {
    $seatMetricsById[$m['course_id']] = $m;
}

$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
?>

<!-- Page Hero Banner -->
<div class="page-hero mb-5">
    <div class="container position-relative z-1">
        <h1 class="font-display fw-extrabold mb-2 text-white">Academic Courses, Fees &amp; Syllabi</h1>
        <p class="lead opacity-80 mb-3" style="font-size: 1.1rem; max-width: 650px;">Explore B.Tech engineering programs, 4-year tuition fee breakdowns, seat intakes, and official curriculum documents.</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Courses &amp; Fees</li>
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
                    <label class="form-label font-semibold text-muted small mb-1.5"><i class="fa-solid fa-filter text-warning me-1"></i> Quick Filter</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="courseFilterInput" class="form-control border-start-0 ps-0" placeholder="Search branch or code...">
                    </div>
                </div>

                <button class="btn btn-dark w-100 text-start d-flex justify-content-between align-items-center py-2.5 px-3 rounded-3 font-bold shadow-xs border-0 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#branchDropdownMenu" aria-expanded="false" aria-controls="branchDropdownMenu" id="branchSelectBtn" style="background: #0f172a;">
                    <span><i class="fa-solid fa-list-ul text-warning me-2"></i><span id="selectedBranchTitle">All Branches</span></span>
                    <i class="fa-solid fa-chevron-down text-warning"></i>
                </button>
                
                <div class="collapse show mt-1" id="branchDropdownMenu">
                    <div class="list-group list-group-flush rounded-3 overflow-hidden shadow-xs border bg-white">
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2.5 font-bold text-dark small border-bottom bg-light d-flex justify-content-between align-items-center" onclick="showAllCourses()">
                            <span><i class="fa-solid fa-layer-group me-2 text-warning"></i>All Programs</span>
                            <span class="badge bg-dark rounded-pill"><?php echo count($courses); ?></span>
                        </a>
                        <?php foreach ($courses as $c): ?>
                            <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2.5 font-semibold text-primary-color small border-bottom branch-nav-item d-flex justify-content-between align-items-center" data-course-id="<?php echo $c['id']; ?>" onclick="filterSingleCourse(<?php echo $c['id']; ?>, '<?php echo addslashes(htmlspecialchars($c['name'])); ?>')">
                                <span><i class="fa-solid fa-angle-right me-1.5 text-warning"></i><?php echo htmlspecialchars($c['name']); ?></span>
                                <span class="badge bg-secondary bg-opacity-15 text-secondary" style="font-size: 10px;"><?php echo htmlspecialchars($c['code']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-xs p-3.5 text-white text-center mt-3 rounded-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-top: 3px solid var(--accent) !important;">
                    <i class="fa-solid fa-headset fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1 font-display">Need Admission Advice?</h6>
                    <p class="small text-white-50 mb-3" style="font-size: 12px; line-height: 1.5;">Connect with our admissions desk for cutoff, merit seats, and fee installments.</p>
                    <a href="admissions.php" class="btn btn-sm btn-warning text-dark font-bold py-1.5 px-3 rounded-pill shadow-xs w-100"><i class="fa-solid fa-paper-plane me-1"></i> Apply Online</a>
                </div>
            </div>
        </div>

        <!-- Course Listings, Year-Wise Fees Table & Syllabus PDFs -->
        <div class="col-lg-9" id="courseDetailsArea">
            
            <!-- Active Filter Notification Bar -->
            <div id="filterNotificationBar" class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center mb-4 py-3 px-4 rounded-3 d-none">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-filter fa-lg text-dark me-3"></i>
                    <div>
                        <small class="text-uppercase fw-bold text-muted d-block opacity-75" style="font-size: 10px;">Currently Filtered Branch</small>
                        <strong class="text-dark fs-6 font-display" id="activeCourseFilterName">Selected Course</strong>
                    </div>
                </div>
                <button type="button" class="btn btn-dark btn-sm font-semibold px-3 rounded-pill" onclick="showAllCourses()">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Show All Courses
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
                ?>
                    <div class="card border-0 shadow-sm mb-5 overflow-hidden course-detail-card hover-lift" 
                         id="course-card-<?php echo $course['id']; ?>" 
                         data-course-id="<?php echo $course['id']; ?>"
                         data-course-name="<?php echo strtolower(htmlspecialchars($course['name'])); ?>"
                         data-course-code="<?php echo strtolower(htmlspecialchars($course['code'])); ?>"
                         style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                        <?php if (!empty($course['image_path'])): ?>
                            <div class="position-relative" style="height: 220px; overflow: hidden;">
                                <img src="<?php echo uploadUrl('courses', $course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['name']); ?>" class="w-100 h-100" style="object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-4 bg-white">
                            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                <div>
                                    <h3 class="fw-bold text-primary-color mb-1 font-display"><?php echo htmlspecialchars($course['name']); ?></h3>
                                    <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($course['code']); ?></span>
                                    <span class="badge bg-light text-dark ms-1 border"><?php echo htmlspecialchars($course['department_name']); ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?php echo $isOpen ? 'bg-success' : 'bg-danger'; ?> px-3 py-1.5 mb-1 rounded-pill">
                                        <i class="fa-solid <?php echo $isOpen ? 'fa-circle-check' : 'fa-circle-xmark'; ?> me-1"></i>
                                        <?php echo $isOpen ? "{$vacantSeats} Vacant Seats" : 'Admissions Closed'; ?>
                                    </span>
                                    <small class="text-muted d-block">Intake: <strong><?php echo $course['intake_capacity']; ?> Seats</strong></small>
                                </div>
                            </div>
                            
                            <p class="text-muted" style="line-height: 1.7;"><?php echo htmlspecialchars($course['description']); ?></p>
                            
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
                                        <small class="text-muted d-block font-semibold">Fee Schedule</small>
                                        <strong class="text-primary-color font-display">Annual Instalment</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3 border border-light">
                                        <small class="text-muted d-block font-semibold">Academic Session</small>
                                        <strong class="text-primary-color font-display">2026-2027</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Year-Wise Fees Table -->
                            <h5 class="fw-bold text-primary-color mt-4 mb-3 border-bottom pb-2 font-display">
                                <i class="fa-solid fa-receipt text-warning me-2"></i>Year-Wise Fee Breakdown (INR ₹)
                            </h5>
                            <div class="table-responsive mb-4 rounded-3 border">
                                <table class="table table-hover align-middle mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Academic Year</th>
                                            <th>Semesters Included</th>
                                            <th class="text-end">Annual Fee (INR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold"><i class="fa-solid fa-calendar-check text-warning me-2"></i>1st Year (First Year)</td>
                                            <td>Semester I &amp; Semester II</td>
                                            <td class="text-end fw-bold text-primary-color">₹<?php echo number_format($y1_fee, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold"><i class="fa-solid fa-calendar-check text-warning me-2"></i>2nd Year (Second Year)</td>
                                            <td>Semester III &amp; Semester IV</td>
                                            <td class="text-end fw-bold text-primary-color">₹<?php echo number_format($y2_fee, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold"><i class="fa-solid fa-calendar-check text-warning me-2"></i>3rd Year (Third Year)</td>
                                            <td>Semester V &amp; Semester VI</td>
                                            <td class="text-end fw-bold text-primary-color">₹<?php echo number_format($y3_fee, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold"><i class="fa-solid fa-calendar-check text-warning me-2"></i>4th Year (Final Year)</td>
                                            <td>Semester VII &amp; Semester VIII</td>
                                            <td class="text-end fw-bold text-primary-color">₹<?php echo number_format($y4_fee, 2); ?></td>
                                        </tr>
                                        <tr class="table-warning fw-bold">
                                            <td colspan="2" class="text-end">Total Program Tuition Fee (4 Years):</td>
                                            <td class="text-end text-dark fs-6 font-display">₹<?php echo number_format($total_tuition, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Year-Wise Syllabus PDF Documents -->
                            <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2 font-display">
                                <i class="fa-solid fa-file-pdf text-danger me-2"></i>Year-Wise Official Syllabus Curriculum
                            </h5>
                            <div class="row g-3">
                                <?php for ($y = 1; $y <= 4; $y++): ?>
                                    <?php $pdfKey = "syllabus_pdf_year_{$y}"; ?>
                                    <div class="col-md-6">
                                        <div class="card border p-3 bg-light rounded-3 d-flex flex-row justify-content-between align-items-center">
                                            <div>
                                                <h6 class="fw-bold mb-0 font-display" style="font-size: 14px;"><i class="fa-solid fa-file-lines me-2 text-danger"></i>Year <?php echo $y; ?> Syllabus</h6>
                                                <small class="text-muted" style="font-size: 11px;">Sem <?php echo ($y * 2 - 1); ?> &amp; <?php echo ($y * 2); ?> Modules</small>
                                            </div>
                                            <div>
                                                <?php if (!empty($course[$pdfKey])): ?>
                                                    <a href="<?php echo uploadUrl('courses', $course[$pdfKey]); ?>" target="_blank" class="btn btn-danger btn-xs font-semibold py-1 px-3 rounded-pill">
                                                        <i class="fa-solid fa-download me-1"></i> PDF
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill font-normal">Available Soon</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <a href="admissions.php" class="btn btn-primary btn-sm px-4 rounded-pill">
                                    <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Apply for this Branch
                                </a>
                                <a href="contact.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                                    <i class="fa-solid fa-envelope me-1"></i> Inquire
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
function filterSingleCourse(id, name) {
    var cards = document.querySelectorAll('.course-detail-card');
    cards.forEach(function(card) {
        card.classList.add('d-none');
    });

    var targetCard = document.getElementById('course-card-' + id);
    if (targetCard) {
        targetCard.classList.remove('d-none');
    }

    document.getElementById('selectedBranchTitle').innerText = name;
    document.getElementById('activeCourseFilterName').innerText = name;
    document.getElementById('filterNotificationBar').classList.remove('d-none');
    document.getElementById('courseDetailsArea').scrollIntoView({ behavior: 'smooth' });
}

function showAllCourses() {
    var cards = document.querySelectorAll('.course-detail-card');
    cards.forEach(function(card) {
        card.classList.remove('d-none');
    });

    document.getElementById('selectedBranchTitle').innerText = 'Select Branch';
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
            var courseName = targetCard.querySelector('h3').innerText;
            filterSingleCourse(initialCourseId, courseName);
        }
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
