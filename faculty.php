<?php
/**
 * Public Faculty Directory Controller & View
 *
 * Displays academic leadership, professors, research mentors, and departmental faculty:
 * - Dynamic department-wise grouping with leadership ordering (Director, HODs, Professors)
 * - Real-time auto-updating experience calculations (years of experience)
 * - Research interests, qualifications, email contacts, and profiles
 * - Instant client-side search filtering by name, specialization, or qualification
 */
$page_title = "Faculty Directory";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$faculty_bg = !empty($settings['faculty_hero_image']) ? uploadUrl('settings', $settings['faculty_hero_image']) : uploadUrl('gallery', 'soet_campus_aerial.jpg');
$departments = $cms->getDepartments();
$selected_dept = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : null;

// Fetch faculty records
$faculty_list = $cms->getFaculty($selected_dept);

// Group faculty by department
$grouped_faculty = [];
foreach ($faculty_list as $fac) {
    $dept_name = $fac['department_name'];
    $grouped_faculty[$dept_name][] = $fac;
}
?>

<!-- Page Hero Banner -->
<div class="page-hero mb-5" style="<?php echo $faculty_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{$faculty_bg}') center/cover no-repeat;" : ""; ?>">
    <div class="container position-relative z-1">
        <h1 class="font-display fw-extrabold mb-2 text-white">Faculty Directory &amp; Mentors</h1>
        <p class="lead opacity-80 mb-3" style="font-size: 1.1rem; max-width: 650px;">Meet our distinguished engineering professors, doctorates, research mentors, and academic leadership.</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faculty Directory</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <!-- Filter & Live Search Bar -->
    <div class="row g-3 mb-5 align-items-center">
        <div class="col-md-6 col-lg-5">
            <div class="input-group shadow-xs">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="facultySearchInput" class="form-control border-start-0 ps-0" placeholder="Search faculty by name, specialization, or qualification...">
            </div>
        </div>
        <div class="col-md-6 col-lg-7">
            <div class="card border-0 shadow-xs p-2 bg-white d-flex flex-row justify-content-md-end align-items-center gap-1.5 flex-wrap rounded-3" style="border: 1px solid #e2e8f0 !important;">
                <span class="small font-semibold text-muted me-1"><i class="fa-solid fa-filter text-warning me-1"></i> Dept:</span>
                <a href="faculty.php" class="btn btn-xs <?php echo !$selected_dept ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3 py-1 font-semibold" style="font-size: 14px;">All (<?php echo count($faculty_list); ?>)</a>
                <?php foreach ($departments as $dept): ?>
                    <a href="faculty.php?dept_id=<?php echo $dept['id']; ?>" class="btn btn-xs <?php echo $selected_dept === $dept['id'] ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3 py-1 font-semibold" style="font-size: 14px;">
                        <?php echo htmlspecialchars($dept['code']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Department Partitioning Container -->
    <div id="facultyPartitionContainer">
        <?php if (empty($grouped_faculty)): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                <p>No faculty members found in this department category.</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_faculty as $dept_name => $fac_members): ?>
                <div class="dept-partition-block mb-5" data-dept-name="<?php echo htmlspecialchars($dept_name); ?>">
                    
                    <!-- Department Header & Divider Line -->
                    <div class="d-flex align-items-center mb-4">
                        <h4 class="fw-bold text-primary-color mb-0 me-3 font-display">
                            <i class="fa-solid <?php echo ($dept_name === 'ALL' || stripos($dept_name, 'Director') !== false) ? 'fa-crown' : 'fa-building-columns'; ?> text-warning me-2"></i><?php echo htmlspecialchars($dept_name === 'ALL' ? 'Institute Directorate & Academic Leadership' : $dept_name); ?>
                        </h4>
                        <div class="flex-grow-1 border-bottom border-2 border-warning opacity-50"></div>
                        <span class="badge bg-light text-dark border ms-3 font-semibold px-3 py-1.5 rounded-pill"><?php echo count($fac_members); ?> <?php echo ($dept_name === 'ALL') ? 'Leadership Members' : 'Faculty Mentors'; ?></span>
                    </div>

                    <!-- Cards Row for Department -->
                    <div class="row g-4">
                         <?php foreach ($fac_members as $fac): ?>
                            <?php 
                                $isDirector = (stripos($fac['designation'], 'Director') !== false || stripos($fac['department_name'], 'Director') !== false || $fac['department_name'] === 'ALL' || empty($fac['department_id']));
                                $experienceYears = getFacultyExperience($fac);
                            ?>
                            <div class="<?php echo $isDirector ? 'col-12 col-lg-6 mb-3' : 'col-md-6 col-lg-3'; ?> faculty-card-col" 
                                 data-name="<?php echo strtolower(htmlspecialchars($fac['name'])); ?>"
                                 data-dept="<?php echo strtolower(htmlspecialchars($fac['department_name'])); ?>"
                                 data-spec="<?php echo strtolower(htmlspecialchars($fac['specialization'] ?: '')); ?>"
                                 data-qual="<?php echo strtolower(htmlspecialchars($fac['qualification'] ?: '')); ?>"
                                 data-email="<?php echo strtolower(htmlspecialchars($fac['email'] ?: '')); ?>"
                                 data-phone="<?php echo strtolower(htmlspecialchars($fac['phone'] ?: '')); ?>"
                                 data-bio="<?php echo strtolower(htmlspecialchars($fac['bio'] ?: '')); ?>">
                                <div class="card h-100 text-center p-3.5 border-0 shadow-sm hover-lift <?php echo $isDirector ? 'border-warning' : ''; ?>" 
                                     style="<?php echo $isDirector ? 'border: 2px solid var(--accent) !important; box-shadow: 0 12px 32px rgba(245, 158, 11, 0.2) !important; background: linear-gradient(145deg, #ffffff, #fffcf5); border-radius: 18px;' : 'border-radius: 16px; border: 1px solid #e2e8f0 !important; background: #ffffff;'; ?>">
                                    
                                    <?php if ($isDirector): ?>
                                        <div class="position-absolute top-0 start-50 translate-middle-x mt-2">
                                            <span class="badge bg-danger text-white px-3 py-1.5 rounded-pill shadow-sm" style="font-size: 14px; font-weight: 700; letter-spacing: 0.5px;">
                                                <i class="fa-solid fa-crown text-warning me-1"></i> INSTITUTE DIRECTORATE
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="text-center <?php echo $isDirector ? 'mt-4 mb-3' : 'my-3'; ?>">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle shadow-xs" 
                                             style="width: <?php echo $isDirector ? '130px' : '110px'; ?>; height: <?php echo $isDirector ? '130px' : '110px'; ?>; overflow: hidden; border: <?php echo $isDirector ? '4px solid var(--accent)' : '3px solid #e2e8f0'; ?>;">
                                            <?php if (!empty($fac['image_path'])): ?>
                                                <img src="<?php echo uploadUrl('faculty', $fac['image_path']); ?>" alt="<?php echo htmlspecialchars($fac['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fa-solid fa-user-tie text-secondary fa-3x"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body p-1">
                                        <h5 class="<?php echo $isDirector ? 'fw-extrabold text-primary-color fs-4' : 'fw-bold text-primary-color'; ?> mb-1 font-display"><?php echo htmlspecialchars($fac['name']); ?></h5>
                                        <span class="badge <?php echo $isDirector ? 'bg-danger text-white' : 'bg-warning bg-opacity-25 text-dark'; ?> rounded-pill mb-2"><?php echo htmlspecialchars($fac['designation']); ?></span>
                                        <span class="d-block text-secondary small font-semibold mb-3"><?php echo htmlspecialchars($fac['department_name']); ?></span>
                                        
                                        <hr class="my-2 border-light">
                                        
                                        <div class="text-start text-muted small my-3" style="font-size: 14px; line-height: 1.6;">
                                            <p class="mb-1"><strong class="text-dark">Qualification:</strong> <?php echo htmlspecialchars($fac['qualification']); ?></p>
                                            <p class="mb-1"><strong class="text-dark">Specialization:</strong> <?php echo htmlspecialchars($fac['specialization'] ?: 'General'); ?></p>
                                            <p class="mb-1"><strong class="text-dark">Experience:</strong> <span class="badge bg-light text-dark border rounded-pill"><?php echo $experienceYears; ?>+ Years</span></p>
                                            <p class="mb-1 text-truncate"><strong class="text-dark">Email:</strong> <a href="mailto:<?php echo htmlspecialchars(trim($fac['email'] ?: 'soet@mgmu.ac.in')); ?>" class="text-muted text-decoration-none" title="<?php echo htmlspecialchars($fac['email'] ?: 'soet@mgmu.ac.in'); ?>"><?php echo htmlspecialchars(trim($fac['email'] ?: 'soet@mgmu.ac.in')); ?></a></p>
                                            <?php if (!empty($fac['phone'])): ?>
                                                <p class="mb-1 text-truncate"><strong class="text-dark">Contact:</strong> <a href="tel:<?php echo htmlspecialchars(trim($fac['phone'])); ?>" class="text-muted text-decoration-none" title="<?php echo htmlspecialchars($fac['phone']); ?>"><?php echo htmlspecialchars(trim($fac['phone'])); ?></a></p>
                                            <?php endif; ?>
                                            <?php if (!empty($fac['bio'])): ?>
                                                <p class="mb-0 text-muted fst-italic mt-2 pt-2 border-top small" style="font-size: 14px; line-height: 1.4;"><i class="fa-solid fa-quote-left text-warning me-1"></i><?php echo htmlspecialchars($fac['bio']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- No Live Matches State -->
    <div id="noFacultyMatch" class="text-center py-5 d-none">
        <i class="fa-solid fa-magnifying-glass fa-3x text-muted mb-3 opacity-50"></i>
        <h5 class="text-muted">No faculty members found matching your search.</h5>
        <button class="btn btn-sm btn-outline-secondary mt-2" onclick="document.getElementById('facultySearchInput').value=''; document.getElementById('facultySearchInput').dispatchEvent(new Event('input'));">Clear Search</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('facultySearchInput');
    const cards = document.querySelectorAll('.faculty-card-col');
    const deptBlocks = document.querySelectorAll('.dept-partition-block');
    const noMatch = document.getElementById('noFacultyMatch');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let totalVisible = 0;

            deptBlocks.forEach(block => {
                let visibleInBlock = 0;
                const blockCards = block.querySelectorAll('.faculty-card-col');
                
                blockCards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const dept = card.getAttribute('data-dept') || '';
                    const spec = card.getAttribute('data-spec') || '';
                    const qual = card.getAttribute('data-qual') || '';
                    const email = card.getAttribute('data-email') || '';
                    const phone = card.getAttribute('data-phone') || '';
                    const bio = card.getAttribute('data-bio') || '';

                    if (name.includes(query) || dept.includes(query) || spec.includes(query) || qual.includes(query) || email.includes(query) || phone.includes(query) || bio.includes(query)) {
                        card.classList.remove('d-none');
                        visibleInBlock++;
                        totalVisible++;
                    } else {
                        card.classList.add('d-none');
                    }
                });

                if (visibleInBlock === 0) {
                    block.classList.add('d-none');
                } else {
                    block.classList.remove('d-none');
                }
            });

            if (noMatch) {
                if (totalVisible === 0 && cards.length > 0) {
                    noMatch.classList.remove('d-none');
                } else {
                    noMatch.classList.add('d-none');
                }
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
