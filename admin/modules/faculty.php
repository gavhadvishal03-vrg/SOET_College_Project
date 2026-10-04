<?php
/**
 * Faculty Directory & Mentors Manager Module
 *
 * Provides full administrative management of teaching and research faculty:
 * - Profile creation and editing (Designation, Qualification, Specialization, Email, Phone)
 * - Department assignment and academic leadership hierarchy tagging
 * - Base experience year configuration for automatic annual experience incrementing
 * - Faculty photo upload with MIME validation and secure storage
 * - Faculty roster CSV export
 *
 * Required Permission: 'manage_faculty'
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_faculty');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_fac = null;
$depts = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT f.name, f.designation, COALESCE(d.name, 'ALL') as department_name, f.qualification, f.email, f.phone, f.specialization, f.experience_years, f.is_active 
         FROM faculty f LEFT JOIN departments d ON f.department_id = d.id 
         ORDER BY 
            CASE WHEN f.department_id IS NULL OR f.designation LIKE '%Director%' THEN 1 ELSE 2 END ASC,
            d.name, f.name"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Faculty_Directory_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Faculty Name', 'Designation', 'Department', 'Qualification', 'Email', 'Phone', 'Specialization', 'Experience (Yrs)', 'Status']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['name'],
            $r['designation'],
            $r['department_name'],
            $r['qualification'],
            $r['email'],
            $r['phone'],
            $r['specialization'],
            $r['experience_years'],
            $r['is_active'] ? 'Active' : 'Inactive'
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Faculty Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_fac = $db->fetchOne("SELECT * FROM faculty WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faculty'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $fac_id = isset($_POST['fac_id']) ? (int)$_POST['fac_id'] : null;
    $raw_dept = $_POST['department_id'] ?? '';
    $dept_id = ($raw_dept !== '' && $raw_dept !== '0' && $raw_dept !== 'all' && $raw_dept !== 'ALL') ? (int)$raw_dept : null;
    $name = trim($_POST['name']);
    $designation = trim($_POST['designation']);
    $qualification = trim($_POST['qualification']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $bio = trim($_POST['bio'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $experience = (int)$_POST['experience_years'];
    $base_year = !empty($_POST['experience_base_year']) ? (int)$_POST['experience_base_year'] : (int)date('Y');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($name) || empty($designation) || $raw_dept === '' || empty($email)) {
        setFlash('danger', 'Please enter all mandatory fields.');
    } else {
        $existing_fac = $fac_id ? $db->fetchOne("SELECT image_path FROM faculty WHERE id = ?", [$fac_id]) : null;
        $uploaded_image = $existing_fac ? $existing_fac['image_path'] : null;
        $valid_upload = true;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $errors = Security::validateUpload($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if (!empty($errors)) {
                setFlash('danger', implode(' ', $errors));
                $valid_upload = false;
            } else {
                $uploaded_image = Security::uploadFile($_FILES['image'], 'faculty', 'fac_');
                if (!$uploaded_image) {
                    setFlash('danger', 'Failed to upload photo.');
                    $valid_upload = false;
                }
            }
        }

        if ($valid_upload) {
            $facData = [
                'department_id' => $dept_id,
                'name' => $name,
                'designation' => $designation,
                'qualification' => $qualification,
                'email' => $email,
                'phone' => $phone,
                'specialization' => $specialization,
                'bio' => $bio,
                'experience_years' => $experience,
                'experience_base_year' => $base_year,
                'sort_order' => $sort_order,
                'image_path' => $uploaded_image,
                'is_active' => $is_active
            ];

            if ($fac_id) {
                // Edit
                $db->update('faculty', $facData, 'id = ?', [$fac_id]);
                setFlash('success', 'Faculty details and auto-calculating experience structure updated successfully.');
                redirect('faculty.php');
            } else {
                // Add
                $db->insert('faculty', $facData);
                setFlash('success', 'Faculty member enrolled with auto-calculating experience.');
                redirect('faculty.php');
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $db->delete('faculty', 'id = ?', [(int)$_GET['id']]);
    setFlash('success', 'Faculty removed from database.');
    redirect('faculty.php');
}

$faculty_list = $db->fetchAll(
    "SELECT f.*, COALESCE(d.name, 'ALL') as department_name, COALESCE(d.code, 'ALL') as department_code 
     FROM faculty f LEFT JOIN departments d ON f.department_id = d.id 
     ORDER BY 
        CASE WHEN f.department_id IS NULL OR f.designation LIKE '%Director%' THEN 1 ELSE 2 END ASC,
        d.name, f.name"
);
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Faculty & Staff</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Faculty Directory Management</h2>
        <p class="text-muted small mb-0">Manage academic faculty profiles, research appointments, and qualifications</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="faculty.php?action=export_csv" class="btn btn-sm btn-success rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="faculty.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold"><i class="fa-solid fa-plus me-1 text-warning"></i> Add Faculty</a>
        </div>
    <?php else: ?>
        <a href="faculty.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-users-rectangle text-warning me-2"></i>Faculty Members (<?php echo count($faculty_list); ?>)</h5>
            <input type="text" id="facultySearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search faculty name, department..." style="width: 240px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="facultyTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 60px;">Photo</th>
                            <th>Faculty Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Email / Phone</th>
                            <th>Specialization</th>
                            <th class="text-center">Experience</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty_list as $f): ?>
                            <tr>
                                <td class="ps-4 text-center">
                                    <div class="rounded-circle overflow-hidden bg-light border d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                                        <?php if (!empty($f['image_path'])): ?>
                                            <img src="<?php echo uploadUrl('faculty', $f['image_path']); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fa-solid fa-user-tie text-secondary small"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><div class="font-display fw-bold text-dark"><?php echo htmlspecialchars($f['name']); ?></div></td>
                                <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill"><?php echo htmlspecialchars($f['designation']); ?></span></td>
                                <td><span class="text-muted small font-semibold"><?php echo htmlspecialchars($f['department_name']); ?></span></td>
                                <td>
                                    <small class="d-block text-dark font-semibold">
                                        <a href="mailto:<?php echo htmlspecialchars($f['email']); ?>" class="text-decoration-none text-dark" title="Compose Email">
                                            <i class="fa-regular fa-envelope text-primary me-1"></i><?php echo htmlspecialchars($f['email']); ?>
                                        </a>
                                    </small>
                                    <?php if (!empty($f['phone'])): ?>
                                        <small class="d-block text-muted">
                                            <a href="tel:<?php echo htmlspecialchars($f['phone']); ?>" class="text-decoration-none text-muted">
                                                <i class="fa-solid fa-phone text-success me-1" style="font-size: 14px;"></i><?php echo htmlspecialchars($f['phone']); ?>
                                            </a>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?php echo htmlspecialchars($f['specialization']); ?></td>
                                <td class="text-center">
                                    <span class="font-display fw-bold text-dark"><?php echo getFacultyExperience($f); ?>+ Yrs</span>
                                    <small class="d-block text-muted" style="font-size: 14px;">Base: <?php echo $f['experience_years']; ?> Yrs (<?php echo $f['experience_base_year'] ?? 2026; ?>)</small>
                                </td>
                                <td class="text-center"><?php echo statusBadge($f['is_active'] ? 'active' : 'inactive'); ?></td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="faculty.php?action=edit&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1" title="Edit Profile"><i class="fa-solid fa-pen me-1"></i> Edit</a>
                                    <a href="faculty.php?action=delete&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 btn-delete-confirm font-semibold" title="Delete Profile" onclick="return confirm('Delete this faculty member?');"><i class="fa-solid fa-trash me-1"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('facultySearchInput');
        const table = document.getElementById('facultyTable');
        if (searchInput && table) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                table.querySelectorAll('tbody tr').forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = (!q || text.includes(q)) ? '' : 'none';
                });
            });
        }
    });
    </script>
<?php else: ?>
    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-user-tie text-warning me-2"></i><?php echo $edit_fac ? 'Edit Faculty Details' : 'Add Faculty Member'; ?></h4>
                <form method="POST" action="faculty.php<?php echo $edit_fac ? '?action=edit&id=' . $edit_fac['id'] : ''; ?>" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_fac): ?>
                        <input type="hidden" name="fac_id" value="<?php echo $edit_fac['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Department *</label>
                            <select name="department_id" class="form-select" id="facultyDeptSelect" required onchange="handleDeptChange(this.value)">
                                <option value="">Select Department...</option>
                                <option value="0" <?php echo ($edit_fac && ($edit_fac['department_id'] === null || $edit_fac['department_id'] == 0)) ? 'selected' : ''; ?> style="font-weight: 700; color: #1e3a8a;">
                                    ALL — Institute Directorate &amp; All Departments
                                </option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ($edit_fac && $edit_fac['department_id'] == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" style="font-size: 14px;">Select <strong>ALL</strong> for Director, Dean, or Institute-wide faculty leadership.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Full Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_fac['name'] ?? ''); ?>" placeholder="Dr. John Doe">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Designation *</label>
                            <div class="input-group">
                                <input type="text" name="designation" id="facultyDesignationInput" class="form-control" required value="<?php echo htmlspecialchars($edit_fac['designation'] ?? ''); ?>" placeholder="Director / Professor & HOD" list="designationPresets">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Options
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item fw-bold text-primary" href="javascript:void(0)" onclick="setDesignation('Director')"><i class="fa-solid fa-crown text-warning me-2"></i>Director</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDesignation('Director & Professor')">Director &amp; Professor</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDesignation('Dean (Academics)')">Dean (Academics)</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDesignation('Head of Department')">Head of Department (HOD)</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDesignation('Professor')">Professor</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDesignation('Associate Professor')">Associate Professor</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDesignation('Assistant Professor')">Assistant Professor</a></li>
                                </ul>
                            </div>
                            <datalist id="designationPresets">
                                <option value="Director">
                                <option value="Director & Professor">
                                <option value="Dean (Academics)">
                                <option value="Head of Department">
                                <option value="Professor">
                                <option value="Associate Professor">
                                <option value="Assistant Professor">
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Qualifications</label>
                            <input type="text" name="qualification" class="form-control" value="<?php echo htmlspecialchars($edit_fac['qualification'] ?? ''); ?>" placeholder="Ph.D., M.Tech">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Email *</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($edit_fac['email'] ?? ''); ?>" placeholder="faculty@mgmu.ac.in">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($edit_fac['phone'] ?? ''); ?>" placeholder="+91 9876543210">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Specialization / Research Interests</label>
                            <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($edit_fac['specialization'] ?? ''); ?>" placeholder="Cloud Computing, AI, Robotics">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Base Experience (Years)</label>
                            <input type="number" name="experience_years" class="form-control" value="<?php echo htmlspecialchars($edit_fac['experience_years'] ?? '0'); ?>" min="0">
                            <?php if ($edit_fac): ?>
                                <small class="text-success font-semibold d-block mt-1">Current Calculated: <strong><?php echo getFacultyExperience($edit_fac); ?>+ Years</strong></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Base Year (As Of)</label>
                            <input type="number" name="experience_base_year" class="form-control" value="<?php echo htmlspecialchars($edit_fac['experience_base_year'] ?? date('Y')); ?>" min="1970" max="2100" placeholder="e.g. <?php echo date('Y'); ?>">
                            <small class="text-muted d-block mt-1" style="font-size: 14px;">Auto-increments every year</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Faculty Bio / Research Overview / Publications</label>
                            <textarea name="bio" class="form-control" rows="3" placeholder="Brief biography, key publications, research domains, patents, awards..."><?php echo htmlspecialchars($edit_fac['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Display Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?php echo $edit_fac ? (int)($edit_fac['sort_order'] ?? 0) : 0; ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Upload Photo</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <?php if ($edit_fac && !empty($edit_fac['image_path'])): ?>
                            <div class="col-12">
                                <label class="form-label font-semibold d-block">Current Photo</label>
                                <img src="<?php echo uploadUrl('faculty', $edit_fac['image_path']); ?>" alt="Current" class="img-thumbnail rounded-circle" style="width: 54px; height: 54px; object-fit: cover;">
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_fac || $edit_fac['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActive">Active Faculty Member</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="faculty.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_faculty" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Profile</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function setDesignation(val) {
        var input = document.getElementById('facultyDesignationInput');
        if (input) {
            input.value = val;
            input.focus();
        }
        if (val === 'Director' || val === 'Director & Professor') {
            var deptSelect = document.getElementById('facultyDeptSelect');
            if (deptSelect && (!deptSelect.value || deptSelect.value === '')) {
                deptSelect.value = '0';
            }
        }
    }

    function handleDeptChange(val) {
        var desigInput = document.getElementById('facultyDesignationInput');
        if (val === '0' && desigInput && desigInput.value.trim() === '') {
            desigInput.value = 'Director';
        }
    }
    </script>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
