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

<!-- Breadcrumb Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $courses_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$courses_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">Courses, Year-Wise Fees & Syllabus</h1>
        <p class="lead opacity-75 mb-3">Explore B.Tech and engineering programs, annual tuition breakdown, and official semester syllabi</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Courses & Fees</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Sidebar Filter / Quick Links -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-3 bg-light mb-4 position-sticky" style="top: 100px; z-index: 10;">
                <!-- Live Search in Sidebar -->
                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-warning"></i></span>
                        <input type="text" id="courseFilterInput" class="form-control" placeholder="Search branch or keyword...">
                    </div>
                </div>

                <button class="btn btn-dark w-100 text-start d-flex justify-content-between align-items-center py-2.5 px-3 rounded-3 font-bold shadow-xs border-0 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#branchDropdownMenu" aria-expanded="false" aria-controls="branchDropdownMenu" id="branchSelectBtn" style="background-color: var(--primary-color);">
                    <span><i class="fa-solid fa-list-ul text-warning me-2"></i><span id="selectedBranchTitle">Select Branch</span></span>
                    <i class="fa-solid fa-chevron-down text-warning"></i>
                </button>
                
                <div class="collapse show mt-1" id="branchDropdownMenu">
                    <div class="list-group list-group-flush rounded-3 overflow-hidden shadow-xs border bg-white">
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2 font-bold text-dark small border-bottom bg-light" onclick="showAllCourses()">
                            <i class="fa-solid fa-layer-group me-2 text-warning"></i>Show All Programs (<?php echo count($courses); ?>)
                        </a>
                        <?php foreach ($courses as $c): ?>
                            <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2.5 font-semibold text-primary-color small border-bottom branch-nav-item" data-course-id="<?php echo $c['id']; ?>" onclick="filterSingleCourse(<?php echo $c['id']; ?>, '<?php echo addslashes(htmlspecialchars($c['name'])); ?>')">
                                <i class="fa-solid fa-angle-right me-2 text-warning"></i><?php echo htmlspecialchars($c['name']); ?> 
                                <span class="badge bg-secondary ms-1" style="font-size: 10px;"><?php echo htmlspecialchars($c['code']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-3 text-white text-center bg-dark mt-3 rounded-3" style="border-top: 4px solid var(--secondary-color);">
                    <i class="fa-solid fa-headset fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1">Need Admission Advice?</h6>
                    <p class="small text-white-50 mb-2" style="font-size: 12px;">Get in touch with our counselors for seat availability and fee installments.</p>
                    <a href="admissions.php" class="btn btn-xs btn-warning text-dark font-semibold py-1.5 px-3 rounded-pill">Apply Online</a>
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
                        <small class="text-uppercase fw-bold text-muted d-block opacity-75" style="font-size: 10px;">Currently Viewing</small>
                        <strong class="text-dark fs-6" id="activeCourseFilterName">Selected Course</strong>
                    </div>
                </div>
                <button type="button" class="btn btn-dark btn-sm font-semibold px-3 rounded-pill" onclick="showAllCourses()">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Show All Courses
                </button>
            </div>

            <?php if (empty($courses)): ?>
                <div class="card border-0 shadow-sm p-5 text-center text-muted">
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
                    <div class="card border-0 shadow-sm mb-5 overflow-hidden course-detail-card hover-elevate" 
                         id="course-card-<?php echo $course['id']; ?>" 
                         data-course-id="<?php echo $course['id']; ?>"
                         data-course-name="<?php echo strtolower(htmlspecialchars($course['name'])); ?>"
                         data-course-code="<?php echo strtolower(htmlspecialchars($course['code'])); ?>">
                        <?php if (!empty($course['image_path'])): ?>
                            <div class="position-relative" style="height: 220px; overflow: hidden;">
                                <img src="<?php echo uploadUrl('courses', $course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['name']); ?>" class="w-100 h-100" style="object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-4 bg-white">
                            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                <div>
                                    <h3 class="fw-bold text-primary-color mb-1"><?php echo htmlspecialchars($course['name']); ?></h3>
                                    <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($course['code']); ?></span>
                                    <span class="badge bg-light text-dark ms-1 border"><?php echo htmlspecialchars($course['department_name']); ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?php echo $isOpen ? 'bg-success' : 'bg-danger'; ?> px-3 py-1.5 mb-1">
                                        <i class="fa-solid <?php echo $isOpen ? 'fa-circle-check' : 'fa-circle-xmark'; ?> me-1"></i>
                                        <?php echo $isOpen ? "{$vacantSeats} Vacant Seats (Admissions Open)" : 'Admissions Closed'; ?>
                                    </span>
                                    <small class="text-muted d-block">Intake: <strong><?php echo $course['intake_capacity']; ?> Seats</strong></small>
                                </div>
                            </div>
                            
                            <p class="text-muted"><?php echo htmlspecialchars($course['description']); ?></p>
                            
                            <div class="row g-3 my-2 text-center">
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3">
                                        <small class="text-muted d-block">Duration</small>
                                        <strong class="text-primary-color"><?php echo $course['duration_years']; ?> Years</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3">
                                        <small class="text-muted d-block">Semesters</small>
                                        <strong class="text-primary-color"><?php echo $course['semester_count']; ?> Semesters</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3">
                                        <small class="text-muted d-block">Fee Schedule</small>
                                        <strong class="text-primary-color">Annual Instalment</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-3 bg-light rounded-3">
                                        <small class="text-muted d-block">Academic Session</small>
                                        <strong class="text-primary-color">2026-2027</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Year-Wise Fees Table -->
                            <h5 class="fw-bold text-primary-color mt-4 mb-3 border-bottom pb-2">
                                <i class="fa-solid fa-receipt text-warning me-2"></i>Year-Wise Fee Breakdown (INR ₹)
                            </h5>
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-striped align-middle mb-0 small">
                                    <thead class="table-dark">
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
                                            <td class="text-end text-dark fs-6">₹<?php echo number_format($total_tuition, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Year-Wise Syllabus PDF Documents -->
                            <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2">
                                <i class="fa-solid fa-file-pdf text-danger me-2"></i>Year-Wise Official Syllabus Curriculum
                            </h5>
                            <div class="row g-3">
                                <?php for ($y = 1; $y <= 4; $y++): ?>
                                    <?php $pdfKey = "syllabus_pdf_year_{$y}"; ?>
                                    <div class="col-md-6">
                                        <div class="card border p-2.5 bg-light rounded-3 d-flex flex-row justify-content-between align-items-center">
                                            <div>
                                                <h6 class="fw-bold mb-0" style="font-size: 14px;"><i class="fa-solid fa-file-lines me-2 text-danger"></i>Year <?php echo $y; ?> Syllabus</h6>
                                                <small class="text-muted" style="font-size: 11px;">Sem <?php echo ($y * 2 - 1); ?> &amp; <?php echo ($y * 2); ?> Modules</small>
                                            </div>
                                            <div>
                                                <?php if (!empty($course[$pdfKey])): ?>
                                                    <a href="<?php echo uploadUrl('courses', $course[$pdfKey]); ?>" target="_blank" class="btn btn-danger btn-xs font-semibold py-1 px-2.5 rounded">
                                                        <i class="fa-solid fa-download me-1"></i> PDF
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary opacity-75 font-normal">Available Soon</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
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
