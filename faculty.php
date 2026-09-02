<?php
$page_title = "Faculty of SOET";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$departments = $cms->getDepartments();
$selected_dept = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : null;

// Fetch all active faculty
$all_faculty = $cms->getFaculty($selected_dept);

// Group faculty by department
$grouped_faculty = [];
$director_faculty = [];

foreach ($all_faculty as $fac) {
    if (stripos($fac['designation'], 'Director') !== false || stripos($fac['designation'], 'Dean') !== false || stripos($fac['department_name'], 'Director') !== false) {
        $director_faculty[] = $fac;
    } else {
        $dept_name = $fac['department_name'];
        $grouped_faculty[$dept_name][] = $fac;
    }
}
?>

<style>
/* SOET Official Faculty Page Visual Theme */
.soet-faculty-page {
    background-color: #fff6f0;
    min-height: 100vh;
    padding-top: 2rem;
    padding-bottom: 5rem;
    color: #1a1a1a;
}

.soet-faculty-badge {
    background-color: #ffffff;
    color: #222222;
    font-weight: 700;
    font-size: 1.15rem;
    padding: 8px 24px;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: inline-block;
}

.soet-section-title {
    color: #9e4a2b;
    font-size: 1.35rem;
    font-weight: 600;
    font-family: 'Outfit', 'Albert Sans', sans-serif;
    margin-top: 2.5rem;
    margin-bottom: 1.25rem;
}

.soet-faculty-card-container {
    padding-bottom: 1.25rem;
    margin-bottom: 1.25rem;
    border-bottom: 2px solid #222222;
}

.soet-faculty-card {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    background: transparent;
    padding: 4px;
    position: relative;
}

.soet-faculty-img-wrapper {
    width: 160px;
    height: 200px;
    flex-shrink: 0;
    overflow: hidden;
    border-radius: 16px;
    background-color: #e5dcd5;
}

.soet-faculty-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 16px;
    transition: transform 0.3s ease;
}

.soet-faculty-card:hover .soet-faculty-img {
    transform: scale(1.03);
}

.soet-faculty-info {
    flex-grow: 1;
    padding-right: 0.5rem;
}

.soet-faculty-name {
    font-weight: 800;
    font-size: 1.15rem;
    color: #111111;
    margin-bottom: 4px;
    line-height: 1.3;
}

.soet-faculty-desig {
    color: #555555;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 8px;
}

.soet-faculty-dept {
    color: #c85a28;
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 0;
}

.soet-arrow-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background-color: #733e1e;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    flex-shrink: 0;
}

.soet-arrow-btn:hover {
    background-color: #c85a28;
    transform: scale(1.15);
    color: #ffffff;
}

/* Modal Custom Styling */
.soet-modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.soet-modal-header {
    background-color: #733e1e;
    color: #ffffff;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
}
</style>

<div class="soet-faculty-page">
    <div class="container">
        
        <!-- Top Breadcrumb & Title Pill Badge -->
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2" style="font-size: 13px;">
                    <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none opacity-75">Home</a></li>
                    <li class="breadcrumb-item"><a href="about.php" class="text-dark text-decoration-none opacity-75">About</a></li>
                    <li class="breadcrumb-item active text-danger font-semibold" aria-current="page">Faculty of SOET</li>
                </ol>
            </nav>
            <div class="soet-faculty-badge">
                Faculty of SOET
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="row g-3 align-items-center mb-4">
            <div class="col-md-5">
                <div class="input-group shadow-sm" style="border-radius: 25px; overflow: hidden;">
                    <span class="input-group-text bg-white border-0 text-warning ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="facultySearchInput" class="form-control border-0 py-2" placeholder="Search faculty name, specialization, qualification..." style="box-shadow: none;">
                </div>
            </div>
            <div class="col-md-7">
                <div class="d-flex flex-wrap justify-content-md-end gap-1.5">
                    <a href="faculty.php" class="btn btn-sm <?php echo !$selected_dept ? 'btn-dark' : 'btn-outline-dark'; ?> rounded-pill px-3 py-1.5" style="font-size: 12px; font-weight: 600;">
                        All Faculty (<?php echo count($all_faculty); ?>)
                    </a>
                    <?php foreach ($departments as $dept): ?>
                        <a href="faculty.php?dept_id=<?php echo $dept['id']; ?>" class="btn btn-sm <?php echo $selected_dept === $dept['id'] ? 'btn-dark' : 'btn-outline-dark'; ?> rounded-pill px-3 py-1.5" style="font-size: 12px; font-weight: 600;">
                            <?php echo htmlspecialchars($dept['code']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Faculty Sections -->
        <?php if (empty($all_faculty)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-users-slash fa-3x mb-3 text-secondary"></i>
                <p class="fs-5">No faculty members found in this category.</p>
            </div>
        <?php else: ?>

            <!-- Director / Leadership Section (if present) -->
            <?php if (!empty($director_faculty) && !$selected_dept): ?>
                <div class="soet-section-title">Director</div>
                <div class="row">
                    <?php foreach ($director_faculty as $df): ?>
                        <div class="col-lg-6 faculty-card-col" 
                             data-name="<?php echo strtolower(htmlspecialchars($df['name'])); ?>"
                             data-dept="<?php echo strtolower(htmlspecialchars($df['department_name'])); ?>"
                             data-spec="<?php echo strtolower(htmlspecialchars($df['specialization'] ?: '')); ?>"
                             data-qual="<?php echo strtolower(htmlspecialchars($df['qualification'] ?: '')); ?>">
                            <div class="soet-faculty-card-container">
                                <div class="soet-faculty-card">
                                    <div class="soet-faculty-img-wrapper">
                                        <?php if (!empty($df['image_path'])): ?>
                                            <img src="<?php echo uploadUrl('faculty', $df['image_path']); ?>" alt="<?php echo htmlspecialchars($df['name']); ?>" class="soet-faculty-img">
                                        <?php else: ?>
                                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-secondary">
                                                <i class="fa-solid fa-user-tie fa-4x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="soet-faculty-info">
                                        <div class="soet-faculty-name"><?php echo htmlspecialchars($df['name']); ?></div>
                                        <div class="soet-faculty-desig"><?php echo htmlspecialchars($df['designation']); ?></div>
                                        <div class="soet-faculty-dept"><?php echo htmlspecialchars($df['department_name']); ?></div>
                                    </div>
                                    <button class="soet-arrow-btn" onclick="openFacultyModal(<?php echo htmlspecialchars(json_encode($df)); ?>)" title="View Details">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Department-wise Grouped Faculty -->
            <?php foreach ($grouped_faculty as $dept_name => $fac_list): ?>
                <div class="soet-section-title"><?php echo htmlspecialchars($dept_name); ?></div>
                <div class="row">
                    <?php foreach ($fac_list as $fac): ?>
                        <div class="col-lg-6 faculty-card-col" 
                             data-name="<?php echo strtolower(htmlspecialchars($fac['name'])); ?>"
                             data-dept="<?php echo strtolower(htmlspecialchars($fac['department_name'])); ?>"
                             data-spec="<?php echo strtolower(htmlspecialchars($fac['specialization'] ?: '')); ?>"
                             data-qual="<?php echo strtolower(htmlspecialchars($fac['qualification'] ?: '')); ?>">
                            <div class="soet-faculty-card-container">
                                <div class="soet-faculty-card">
                                    <div class="soet-faculty-img-wrapper">
                                        <?php if (!empty($fac['image_path'])): ?>
                                            <img src="<?php echo uploadUrl('faculty', $fac['image_path']); ?>" alt="<?php echo htmlspecialchars($fac['name']); ?>" class="soet-faculty-img">
                                        <?php else: ?>
                                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-secondary">
                                                <i class="fa-solid fa-user-tie fa-4x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="soet-faculty-info">
                                        <div class="soet-faculty-name"><?php echo htmlspecialchars($fac['name']); ?></div>
                                        <div class="soet-faculty-desig"><?php echo htmlspecialchars($fac['designation']); ?></div>
                                        <div class="soet-faculty-dept"><?php echo htmlspecialchars($fac['department_name']); ?></div>
                                    </div>
                                    <button class="soet-arrow-btn" onclick="openFacultyModal(<?php echo htmlspecialchars(json_encode($fac)); ?>)" title="View Profile">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <!-- No Live Matches Search Result -->
        <div id="noFacultyMatch" class="text-center py-5 d-none">
            <i class="fa-solid fa-magnifying-glass fa-3x text-muted mb-3 opacity-50"></i>
            <h5 class="text-muted">No faculty members found matching your search.</h5>
            <button class="btn btn-sm btn-outline-dark rounded-pill mt-2" onclick="document.getElementById('facultySearchInput').value=''; document.getElementById('facultySearchInput').dispatchEvent(new Event('input'));">Clear Search</button>
        </div>

    </div>
</div>

<!-- Faculty Detail Modal -->
<div class="modal fade" id="facultyDetailModal" tabindex="-1" aria-labelledby="facultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content soet-modal-content">
            <div class="modal-header soet-modal-header py-3">
                <h5 class="modal-title font-bold" id="facultyModalLabel"><i class="fa-solid fa-id-card me-2"></i> Faculty Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="d-inline-block rounded-circle overflow-hidden shadow" style="width: 100px; height: 100px; border: 3px solid #733e1e;">
                        <img id="mFacImg" src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h5 id="mFacName" class="fw-bold mt-2 mb-0 text-dark"></h5>
                    <span id="mFacDesig" class="badge bg-warning text-dark mt-1"></span>
                    <p id="mFacDept" class="text-danger small font-semibold mb-0 mt-1"></p>
                </div>
                <hr class="my-3">
                <div class="small">
                    <p class="mb-2"><strong class="text-dark"><i class="fa-solid fa-graduation-cap me-1 text-primary"></i> Qualification:</strong> <span id="mFacQual"></span></p>
                    <p class="mb-2"><strong class="text-dark"><i class="fa-solid fa-award me-1 text-warning"></i> Specialization:</strong> <span id="mFacSpec"></span></p>
                    <p class="mb-2"><strong class="text-dark"><i class="fa-solid fa-briefcase me-1 text-success"></i> Experience:</strong> <span id="mFacExp"></span></p>
                    <p class="mb-2"><strong class="text-dark"><i class="fa-solid fa-envelope me-1 text-info"></i> Email:</strong> <a id="mFacEmail" href="" class="text-decoration-none"></a></p>
                    <p class="mb-0"><strong class="text-dark"><i class="fa-solid fa-phone me-1 text-secondary"></i> Contact:</strong> <a id="mFacPhone" href="" class="text-decoration-none"></a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openFacultyModal(fac) {
    document.getElementById('mFacName').innerText = fac.name;
    document.getElementById('mFacDesig').innerText = fac.designation;
    document.getElementById('mFacDept').innerText = fac.department_name;
    document.getElementById('mFacQual').innerText = fac.qualification || 'N/A';
    document.getElementById('mFacSpec').innerText = fac.specialization || 'General';
    document.getElementById('mFacExp').innerText = (fac.experience_years || '0') + '+ Years';
    
    document.getElementById('mFacEmail').innerText = fac.email || 'N/A';
    document.getElementById('mFacEmail').href = fac.email ? 'mailto:' + fac.email : '#';
    
    document.getElementById('mFacPhone').innerText = fac.phone || 'N/A';
    document.getElementById('mFacPhone').href = fac.phone ? 'tel:' + fac.phone : '#';

    var imgUrl = fac.image_path ? ('assets/uploads/faculty/' + fac.image_path) : 'assets/images/placeholder.png';
    document.getElementById('mFacImg').src = imgUrl;

    var modal = new bootstrap.Modal(document.getElementById('facultyDetailModal'));
    modal.show();
}

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
