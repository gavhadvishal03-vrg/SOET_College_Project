<?php
$page_title = "Faculty Directory";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$faculty_bg = !empty($settings['faculty_hero_image']) ? uploadUrl('settings', $settings['faculty_hero_image']) : null;
$departments = $cms->getDepartments();
$selected_dept = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : null;

// Fetch faculty
$faculty_list = $cms->getFaculty($selected_dept);
?>

<!-- Breadcrumb Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $faculty_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$faculty_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">Faculty Directory</h1>
        <p class="lead opacity-75 mb-3">Meet our distinguished professors, department heads, and research mentors</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Faculty</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <!-- Filter & Live Search Bar -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-5">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-warning"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="facultySearchInput" class="form-control border-start-0" placeholder="Search faculty by name, specialization, or qualification...">
            </div>
        </div>
        <div class="col-md-6 col-lg-7">
            <div class="card border-0 shadow-sm p-2 bg-light d-flex flex-row justify-content-md-end align-items-center gap-2 flex-wrap">
                <span class="small fw-bold text-muted me-1"><i class="fa-solid fa-filter text-warning me-1"></i> Dept:</span>
                <a href="faculty.php" class="btn btn-xs <?php echo !$selected_dept ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-2.5 py-1" style="font-size: 12px;">All (<?php echo count($faculty_list); ?>)</a>
                <?php foreach ($departments as $dept): ?>
                    <a href="faculty.php?dept_id=<?php echo $dept['id']; ?>" class="btn btn-xs <?php echo $selected_dept === $dept['id'] ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-2.5 py-1" style="font-size: 12px;">
                        <?php echo htmlspecialchars($dept['code']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Faculty Cards Grid -->
    <div class="row g-4" id="facultyGrid">
        <?php if (empty($faculty_list)): ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="fa-solid fa-users-slash fa-3x mb-3 text-secondary"></i>
                <p>No faculty members found in this department category.</p>
            </div>
        <?php else: ?>
            <?php foreach ($faculty_list as $fac): ?>
                <?php $isDirector = (stripos($fac['designation'], 'Director') !== false || stripos($fac['department_name'], 'Director') !== false); ?>
                <div class="<?php echo $isDirector ? 'col-12 col-lg-6 mb-3' : 'col-md-6 col-lg-3'; ?> faculty-card-col" 
                     data-name="<?php echo strtolower(htmlspecialchars($fac['name'])); ?>"
                     data-dept="<?php echo strtolower(htmlspecialchars($fac['department_name'])); ?>"
                     data-spec="<?php echo strtolower(htmlspecialchars($fac['specialization'] ?: '')); ?>"
                     data-qual="<?php echo strtolower(htmlspecialchars($fac['qualification'] ?: '')); ?>">
                    <div class="card custom-card h-100 text-center p-3 border-0 shadow-sm hover-elevate <?php echo $isDirector ? 'border-warning' : ''; ?>" 
                         style="<?php echo $isDirector ? 'border: 2px solid #bfa15f !important; box-shadow: 0 12px 32px rgba(191, 161, 95, 0.28) !important; background: linear-gradient(145deg, #ffffff, #fffcf5);' : ''; ?>">
                        
                        <?php if ($isDirector): ?>
                            <div class="position-absolute top-0 start-50 translate-middle-x mt-2">
                                <span class="badge bg-danger text-white px-3 py-1.5 rounded-pill shadow-sm" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-crown text-warning me-1"></i> INSTITUTE DIRECTORATE
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="text-center <?php echo $isDirector ? 'mt-4 mb-3' : 'my-3'; ?>">
                            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle shadow-sm" 
                                 style="width: <?php echo $isDirector ? '130px' : '110px'; ?>; height: <?php echo $isDirector ? '130px' : '110px'; ?>; overflow: hidden; border: <?php echo $isDirector ? '4px solid #bfa15f' : '3px solid var(--secondary-color)'; ?>;">
                                <?php if (!empty($fac['image_path'])): ?>
                                    <img src="<?php echo uploadUrl('faculty', $fac['image_path']); ?>" alt="<?php echo htmlspecialchars($fac['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fa-solid fa-user-tie text-secondary fa-4x"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-1">
                            <h5 class="<?php echo $isDirector ? 'fw-extrabold text-primary-color fs-4' : 'fw-bold text-primary-color'; ?> mb-1"><?php echo htmlspecialchars($fac['name']); ?></h5>
                            <span class="badge <?php echo $isDirector ? 'bg-danger text-white' : 'bg-warning text-dark'; ?> mb-2"><?php echo htmlspecialchars($fac['designation']); ?></span>
                            <span class="d-block text-secondary small fw-semibold mb-3"><?php echo htmlspecialchars($fac['department_name']); ?></span>
                            
                            <hr class="my-2 bg-light">
                            
                            <div class="text-start text-muted small my-3">
                                <p class="mb-1"><strong class="text-dark">Qualification:</strong> <?php echo htmlspecialchars($fac['qualification']); ?></p>
                                <p class="mb-1"><strong class="text-dark">Specialization:</strong> <?php echo htmlspecialchars($fac['specialization'] ?: 'General'); ?></p>
                                <p class="mb-1"><strong class="text-dark">Experience:</strong> <span class="badge bg-light text-dark border"><?php echo $fac['experience_years']; ?>+ Years</span></p>
                            </div>
                            
                            <hr class="my-2 bg-light">
                            
                            <div class="d-flex justify-content-center gap-3 mt-3 text-secondary small">
                                <a href="mailto:<?php echo htmlspecialchars($fac['email']); ?>" class="btn btn-xs btn-outline-primary py-1 px-2 rounded" title="<?php echo htmlspecialchars($fac['email']); ?>">
                                    <i class="fa-regular fa-envelope me-1"></i> Email
                                </a>
                                <a href="tel:<?php echo htmlspecialchars($fac['phone']); ?>" class="btn btn-xs btn-outline-success py-1 px-2 rounded" title="<?php echo htmlspecialchars($fac['phone']); ?>">
                                    <i class="fa-solid fa-phone me-1"></i> Call
                                </a>
                            </div>
                        </div>
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
    const noMatch = document.getElementById('noFacultyMatch');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const dept = card.getAttribute('data-dept') || '';
                const spec = card.getAttribute('data-spec') || '';
                const qual = card.getAttribute('data-qual') || '';

                if (name.includes(query) || dept.includes(query) || spec.includes(query) || qual.includes(query)) {
                    card.classList.remove('d-none');
                    visibleCount++;
                } else {
                    card.classList.add('d-none');
                }
            });

            if (noMatch) {
                if (visibleCount === 0 && cards.length > 0) {
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
