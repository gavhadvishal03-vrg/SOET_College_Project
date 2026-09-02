<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_faculty');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_fac = null;
$depts = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT f.name, f.designation, d.name as department_name, f.qualification, f.email, f.phone, f.specialization, f.experience_years, f.is_active 
         FROM faculty f JOIN departments d ON f.department_id = d.id 
         ORDER BY d.name, f.name"
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
    $dept_id = (int)$_POST['department_id'];
    $name = trim($_POST['name']);
    $designation = trim($_POST['designation']);
    $qualification = trim($_POST['qualification']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $experience = (int)$_POST['experience_years'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($name) || empty($designation) || empty($dept_id) || empty($email)) {
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
            if ($fac_id) {
                // Edit
                $db->update('faculty', [
                    'department_id' => $dept_id,
                    'name' => $name,
                    'designation' => $designation,
                    'qualification' => $qualification,
                    'email' => $email,
                    'phone' => $phone,
                    'specialization' => $specialization,
                    'experience_years' => $experience,
                    'image_path' => $uploaded_image,
                    'is_active' => $is_active
                ], 'id = ?', [$fac_id]);
                setFlash('success', 'Faculty details updated successfully.');
                redirect('faculty.php');
            } else {
                // Add
                $db->insert('faculty', [
                    'department_id' => $dept_id,
                    'name' => $name,
                    'designation' => $designation,
                    'qualification' => $qualification,
                    'email' => $email,
                    'phone' => $phone,
                    'specialization' => $specialization,
                    'experience_years' => $experience,
                    'image_path' => $uploaded_image,
                    'is_active' => $is_active
                ]);
                setFlash('success', 'Faculty member enrolled successfully.');
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
    "SELECT f.*, d.name as department_name 
     FROM faculty f JOIN departments d ON f.department_id = d.id 
     ORDER BY d.name, f.name"
);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-chalkboard-user text-warning me-2"></i>Faculty Directory Management</h1>
        <small class="text-muted">Manage academic faculty profiles, research areas, and departmental appointments</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="faculty.php?action=export_csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="faculty.php?action=add" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Add Faculty</a>
        </div>
    <?php else: ?>
        <a href="faculty.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-users-rectangle text-warning me-2"></i>Faculty Members (<?php echo count($faculty_list); ?>)</h5>
            <input type="text" id="facultySearchInput" class="form-control form-control-sm" placeholder="Search faculty name, department..." style="width: 240px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="facultyTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 60px;">Photo</th>
                            <th>Faculty Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Email / Phone</th>
                            <th>Specialization</th>
                            <th class="text-center">Experience</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty_list as $f): ?>
                            <tr>
                                <td class="text-center">
                                    <div class="rounded-circle overflow-hidden bg-light border d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                        <?php if (!empty($f['image_path'])): ?>
                                            <img src="<?php echo uploadUrl('faculty', $f['image_path']); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fa-solid fa-user-tie text-secondary small"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="fw-bold text-primary-color"><?php echo htmlspecialchars($f['name']); ?></td>
                                <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($f['designation']); ?></span></td>
                                <td><span class="text-muted small fw-semibold"><?php echo htmlspecialchars($f['department_name']); ?></span></td>
                                <td>
                                    <small class="d-block text-secondary"><?php echo htmlspecialchars($f['email']); ?></small>
                                    <small class="d-block text-muted"><?php echo htmlspecialchars($f['phone']); ?></small>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($f['specialization']); ?></td>
                                <td class="text-center"><?php echo $f['experience_years']; ?> Years</td>
                                <td class="text-center"><?php echo statusBadge($f['is_active'] ? 'active' : 'inactive'); ?></td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="faculty.php?action=edit&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Profile"><i class="fa-solid fa-pen"></i></a>
                                        <a href="faculty.php?action=delete&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete Profile" onclick="return confirm('Delete this faculty member?');"><i class="fa-solid fa-trash"></i></a>
                                    </div>
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
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department...</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ($edit_fac && $edit_fac['department_id'] == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Full Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_fac['name'] ?? ''); ?>" placeholder="Dr. John Doe">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Designation *</label>
                            <input type="text" name="designation" class="form-control" required value="<?php echo htmlspecialchars($edit_fac['designation'] ?? ''); ?>" placeholder="Professor & HOD">
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

                        <div class="col-md-8">
                            <label class="form-label font-semibold">Specialization / Research Interests</label>
                            <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($edit_fac['specialization'] ?? ''); ?>" placeholder="Cloud Computing, AI, Robotics">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Experience (Years)</label>
                            <input type="number" name="experience_years" class="form-control" value="<?php echo htmlspecialchars($edit_fac['experience_years'] ?? '0'); ?>" min="0">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label font-semibold">Upload Photo</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <?php if ($edit_fac && !empty($edit_fac['image_path'])): ?>
                            <div class="col-md-4">
                                <label class="form-label font-semibold d-block">Current Photo</label>
                                <img src="<?php echo uploadUrl('faculty', $edit_fac['image_path']); ?>" alt="Current" class="img-thumbnail" style="height: 60px;">
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
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
