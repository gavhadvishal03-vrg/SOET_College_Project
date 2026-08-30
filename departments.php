<?php
$page_title = "Academic Departments";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$departments_bg = !empty($settings['departments_hero_image']) ? uploadUrl('settings', $settings['departments_hero_image']) : null;
$departments = $cms->getDepartments();
?>

<!-- Breadcrumb Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $departments_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$departments_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">Academic Departments</h1>
        <p class="lead opacity-75 mb-3">Explore specialized engineering disciplines, research labs, faculty mentors, and academic degree tracks</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Departments</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <!-- Department Quick Jump Navigation Pills -->
    <div class="card border-0 shadow-sm p-3 bg-light mb-5 rounded-3">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-center">
            <span class="small fw-bold text-muted me-2"><i class="fa-solid fa-layer-group text-warning me-1"></i> Quick Jump:</span>
            <?php foreach ($departments as $dept): ?>
                <a href="#dept-<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 font-semibold" style="font-size: 12px;">
                    <?php echo htmlspecialchars($dept['name']); ?> (<?php echo htmlspecialchars($dept['code']); ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Department Cards -->
    <div class="row g-4">
        <?php if (empty($departments)): ?>
            <div class="col-12 text-center text-muted py-5">
                <p>No departments registered yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($departments as $dept): 
                $courses = $cms->getCourses($dept['id']);
                $faculty_members = $cms->getFaculty($dept['id']);
            ?>
                <div class="col-12 mb-5" id="dept-<?php echo $dept['id']; ?>">
                    <div class="card border-0 shadow-sm overflow-hidden hover-elevate rounded-3">
                        <?php 
                        $header_style = !empty($dept['image_path']) 
                            ? "background: linear-gradient(rgba(13, 35, 58, 0.7), rgba(13, 35, 58, 0.95)), url('" . uploadUrl('departments', $dept['image_path']) . "') no-repeat center; background-size: cover;" 
                            : "background-color: var(--primary-color);";
                        ?>
                        <div class="card-header text-white p-4 d-flex justify-content-between align-items-center" style="<?php echo $header_style; ?> border-bottom: 4px solid var(--secondary-color); min-height: 120px;">
                            <div>
                                <h3 class="mb-0 fw-bold text-uppercase" style="font-family: 'Outfit', sans-serif; letter-spacing: 0.5px;"><?php echo htmlspecialchars($dept['name']); ?></h3>
                                <div class="d-flex gap-2 align-items-center mt-2 flex-wrap">
                                    <span class="badge bg-warning text-dark text-uppercase"><?php echo htmlspecialchars($dept['code']); ?> Engineering</span>
                                    <span class="badge bg-light text-dark"><?php echo count($courses); ?> Degree Programs</span>
                                    <span class="badge bg-light text-dark"><?php echo count($faculty_members); ?> Faculty Members</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-graduation-cap fa-3x opacity-50 text-warning d-none d-md-block"></i>
                        </div>
                        <div class="card-body p-4 bg-white">
                            <p class="lead text-muted fs-6"><?php echo htmlspecialchars($dept['description']); ?></p>
                            
                            <div class="row g-4 mt-2">
                                <!-- Courses offered -->
                                <div class="col-lg-6">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                        <h5 class="fw-bold text-primary-color mb-0">
                                            <i class="fa-solid fa-book-bookmark me-2 text-warning"></i>Degree Programs Offered
                                        </h5>
                                        <a href="courses.php" class="btn btn-xs btn-outline-primary py-0.5 px-2 rounded" style="font-size: 11px;">View All Fees</a>
                                    </div>
                                    <?php if (empty($courses)): ?>
                                        <p class="text-muted small">No courses offered currently.</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($courses as $course): ?>
                                                <div class="list-group-item bg-transparent px-0 py-2.5">
                                                    <div class="d-flex w-100 justify-content-between align-items-start mb-1">
                                                        <h6 class="mb-0 fw-bold text-primary-color"><?php echo htmlspecialchars($course['name']); ?></h6>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($course['code']); ?></span>
                                                    </div>
                                                    <p class="text-muted small mb-1"><?php echo htmlspecialchars($course['description']); ?></p>
                                                    <div class="d-flex gap-3 small text-muted">
                                                        <span><i class="fa-regular fa-clock me-1 text-warning"></i><?php echo $course['duration_years']; ?> Years</span>
                                                        <span><i class="fa-solid fa-users me-1 text-primary"></i>Intake: <?php echo $course['intake_capacity']; ?> Seats</span>
                                                        <a href="courses.php?course_id=<?php echo $course['id']; ?>" class="text-decoration-none font-semibold text-warning ms-auto">Curriculum &amp; Syllabus &rarr;</a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Head Faculty / Members -->
                                <div class="col-lg-6">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                        <h5 class="fw-bold text-primary-color mb-0">
                                            <i class="fa-solid fa-chalkboard-user me-2 text-warning"></i>Faculty Members
                                        </h5>
                                        <a href="faculty.php?dept_id=<?php echo $dept['id']; ?>" class="btn btn-xs btn-outline-primary py-0.5 px-2 rounded" style="font-size: 11px;">Full Directory</a>
                                    </div>
                                    <?php if (empty($faculty_members)): ?>
                                        <p class="text-muted small">No faculty members currently mapped to this department.</p>
                                    <?php else: ?>
                                        <div class="row g-2">
                                            <?php foreach ($faculty_members as $faculty): ?>
                                                <div class="col-sm-6">
                                                    <div class="d-flex align-items-center gap-2.5 p-2 border rounded bg-light bg-opacity-50">
                                                        <div class="bg-light d-flex align-items-center justify-content-center rounded-circle overflow-hidden shadow-xs border" style="width: 48px; height: 48px; min-width: 48px;">
                                                            <?php if (!empty($faculty['image_path'])): ?>
                                                                <img src="<?php echo uploadUrl('faculty', $faculty['image_path']); ?>" alt="<?php echo htmlspecialchars($faculty['name']); ?>" class="w-100 h-100" style="object-fit: cover;">
                                                            <?php else: ?>
                                                                <i class="fa-solid fa-user-tie text-secondary"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="overflow-hidden">
                                                            <h6 class="mb-0 fw-bold small text-primary-color text-truncate"><?php echo htmlspecialchars($faculty['name']); ?></h6>
                                                            <span class="badge bg-warning text-dark opacity-90 my-0.5" style="font-size: 9px;"><?php echo htmlspecialchars($faculty['designation']); ?></span>
                                                            <small class="text-muted d-block text-truncate" style="font-size: 10px;"><?php echo htmlspecialchars($faculty['qualification']); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
