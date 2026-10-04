<?php
/**
 * Academic Departments Management Module
 *
 * Full CRUD administrative interface for academic departments:
 * - Department profile creation (Name, Code, Overview description, Vision/Mission)
 * - Head of Department (HOD) name, designation, contact email, and phone assignment
 * - Department banner image uploads (MIME validated)
 * - Associated course and faculty count statistics
 * - Department roster CSV export
 *
 * Required Permission: 'manage_departments'
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_departments');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_dept = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT d.name, d.code, d.hod_name, d.hod_designation, d.contact_email, d.contact_phone, d.sort_order, d.is_active, 
                (SELECT COUNT(*) FROM courses WHERE department_id = d.id) as course_count,
                (SELECT COUNT(*) FROM faculty WHERE department_id = d.id) as faculty_count
         FROM departments d ORDER BY d.sort_order ASC, d.name ASC"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Departments_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Department Name', 'Code', 'Head of Department', 'HOD Designation', 'Email', 'Phone', 'Programs Count', 'Faculty Count', 'Sort Order', 'Status']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['name'],
            $r['code'],
            $r['hod_name'] ?? 'N/A',
            $r['hod_designation'] ?? 'N/A',
            $r['contact_email'] ?? 'N/A',
            $r['contact_phone'] ?? 'N/A',
            $r['course_count'],
            $r['faculty_count'],
            $r['sort_order'] ?? 0,
            $r['is_active'] ? 'Active' : 'Inactive'
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Academic Departments Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_dept = $db->fetchOne("SELECT * FROM departments WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dept'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $dept_id = isset($_POST['dept_id']) ? (int)$_POST['dept_id'] : null;
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));
    $description = trim($_POST['description']);
    $hod_name = trim($_POST['hod_name'] ?? '');
    $hod_designation = trim($_POST['hod_designation'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $facilities_text = trim($_POST['facilities_text'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $image_path = null;
    if ($dept_id && $edit_dept) {
        $image_path = $edit_dept['image_path'];
    }

    if (isset($_FILES['dept_image']) && $_FILES['dept_image']['error'] === UPLOAD_ERR_OK) {
        $file_errors = Security::validateUpload($_FILES['dept_image'], ALLOWED_IMAGE_TYPES);
        if (empty($file_errors)) {
            $uploaded = Security::uploadFile($_FILES['dept_image'], 'departments', 'dept_');
            if ($uploaded) {
                $image_path = $uploaded;
            }
        } else {
            setFlash('danger', implode(' ', $file_errors));
        }
    }

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($name) || empty($code)) {
        setFlash('danger', 'Please enter both department name and code.');
    } else {
        $data = [
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'hod_name' => $hod_name,
            'hod_designation' => $hod_designation,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'facilities_text' => $facilities_text,
            'sort_order' => $sort_order,
            'image_path' => $image_path,
            'is_active' => $is_active
        ];

        if ($dept_id) {
            // Edit
            $db->update('departments', $data, 'id = ?', [$dept_id]);
            setFlash('success', 'Department details updated successfully.');
            redirect('departments.php');
        } else {
            // Add
            $exists = $db->fetchOne("SELECT id FROM departments WHERE name = ? OR code = ?", [$name, $code]);
            if ($exists) {
                setFlash('danger', 'Department with this name or code already exists.');
            } else {
                $db->insert('departments', $data);
                setFlash('success', 'Department created successfully.');
                redirect('departments.php');
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    try {
        $db->delete('departments', 'id = ?', [$target_id]);
        setFlash('success', 'Department deleted successfully.');
    } catch (PDOException $e) {
        setFlash('danger', 'Cannot delete department: it has linked faculty, courses, or users.');
    }
    redirect('departments.php');
}

// Handle Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $current = $db->fetchOne("SELECT is_active FROM departments WHERE id = ?", [$target_id]);
    if ($current) {
        $new_status = $current['is_active'] ? 0 : 1;
        $db->update('departments', ['is_active' => $new_status], 'id = ?', [$target_id]);
        setFlash('success', 'Department visibility updated.');
    }
    redirect('departments.php');
}

// Fetch departments
$departments = $db->fetchAll(
    "SELECT d.*, 
            (SELECT COUNT(*) FROM courses WHERE department_id = d.id) as course_count,
            (SELECT COUNT(*) FROM faculty WHERE department_id = d.id) as faculty_count
     FROM departments d ORDER BY d.sort_order ASC, d.name ASC"
);
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Faculty &amp; Disciplines</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Academic Departments Management</h2>
        <p class="text-muted small mb-0">Manage engineering disciplines, departmental profiles, leadership, facilities, and curriculum allocations</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="departments.php?action=export_csv" class="btn btn-sm btn-success rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="departments.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-plus me-1 text-warning"></i> Create Department
            </a>
        </div>
    <?php else: ?>
        <a href="departments.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold">
            <i class="fa-solid fa-angle-left me-1"></i> Back to List
        </a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-network-wired text-warning me-2"></i>Departments (<?php echo count($departments); ?>)</h5>
            <input type="text" id="deptSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search department name, code, HOD..." style="width: 260px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="departmentsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Cover</th>
                            <th>Department Name</th>
                            <th>Code</th>
                            <th>HOD / Leadership</th>
                            <th>Programs</th>
                            <th>Faculty</th>
                            <th>Sort Order</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td class="ps-4">
                                    <?php if (!empty($dept['image_path'])): ?>
                                        <img src="<?php echo uploadUrl('departments', $dept['image_path']); ?>" alt="Cover" class="rounded-3 shadow-xs" style="width: 48px; height: 48px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded-3 text-muted d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px; font-size: 14px;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-display fw-bold text-dark mb-0.5"><?php echo htmlspecialchars($dept['name']); ?></div>
                                    <small class="text-muted"><?php echo truncate(htmlspecialchars($dept['description']), 70); ?></small>
                                </td>
                                <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill"><?php echo htmlspecialchars($dept['code']); ?></span></td>
                                <td>
                                    <?php if (!empty($dept['hod_name'])): ?>
                                        <div class="font-semibold text-dark small"><?php echo htmlspecialchars($dept['hod_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($dept['hod_designation'] ?? 'Head of Department'); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill"><?php echo $dept['course_count']; ?> Programs</span></td>
                                <td><span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill"><?php echo $dept['faculty_count']; ?> Faculty</span></td>
                                <td><span class="badge bg-light text-dark border"><?php echo (int)($dept['sort_order'] ?? 0); ?></span></td>
                                <td class="text-center">
                                    <a href="departments.php?toggle_status=1&id=<?php echo $dept['id']; ?>" class="text-decoration-none" title="Toggle active status">
                                        <?php echo statusBadge($dept['is_active'] ? 'active' : 'inactive'); ?>
                                    </a>
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="departments.php?action=edit&id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1" title="Edit"><i class="fa-solid fa-pen me-1"></i> Edit</a>
                                    <a href="departments.php?action=delete&id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 btn-delete-confirm font-semibold" title="Delete" onclick="return confirm('Delete this department?');"><i class="fa-solid fa-trash me-1"></i> Delete</a>
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
        const searchInput = document.getElementById('deptSearchInput');
        const table = document.getElementById('departmentsTable');
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
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                    <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-network-wired"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0 font-display"><?php echo $edit_dept ? 'Edit Department Details' : 'Create Academic Department'; ?></h4>
                        <small class="text-muted">Configure discipline overview, leadership, contacts, and laboratory facilities</small>
                    </div>
                </div>

                <form method="POST" action="departments.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_dept): ?>
                        <input type="hidden" name="dept_id" value="<?php echo $edit_dept['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label font-semibold text-dark small">Department Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" required value="<?php echo htmlspecialchars($edit_dept['name'] ?? ''); ?>" placeholder="e.g. Department of Computer Science & Engineering">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold text-dark small">Department Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control rounded-3" required value="<?php echo htmlspecialchars($edit_dept['code'] ?? ''); ?>" placeholder="e.g. CSE">
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold text-dark small">Department Narrative &amp; Vision</label>
                            <textarea name="description" class="form-control rounded-3" rows="4" placeholder="Brief outline of research, infrastructure, curriculum focus, and vision..."><?php echo htmlspecialchars($edit_dept['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- HOD Information -->
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Head of Department (HOD) Name</label>
                            <input type="text" name="hod_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($edit_dept['hod_name'] ?? ''); ?>" placeholder="e.g. Dr. A. B. Sharma">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">HOD Designation</label>
                            <input type="text" name="hod_designation" class="form-control rounded-3" value="<?php echo htmlspecialchars($edit_dept['hod_designation'] ?? 'Professor & Head of Department'); ?>" placeholder="e.g. Professor & Head">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Departmental Contact Email</label>
                            <input type="email" name="contact_email" class="form-control rounded-3" value="<?php echo htmlspecialchars($edit_dept['contact_email'] ?? ''); ?>" placeholder="hod.cse@mgmu.ac.in">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Departmental Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control rounded-3" value="<?php echo htmlspecialchars($edit_dept['contact_phone'] ?? ''); ?>" placeholder="+91-9371714253 Ext 2810">
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold text-dark small">Specialized Laboratories &amp; Facilities (Newline Separated)</label>
                            <textarea name="facilities_text" class="form-control rounded-3" rows="3" placeholder="Industrial Robotics Lab&#10;IoT & Cyber Security Center&#10;AI & High Performance Computing Lab"><?php echo htmlspecialchars($edit_dept['facilities_text'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Display Sort Order</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="<?php echo $edit_dept ? (int)($edit_dept['sort_order'] ?? 0) : 0; ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Upload Department Cover / Lab Banner</label>
                            <input type="file" name="dept_image" class="form-control rounded-3 mb-2" accept="image/*">
                            <?php if ($edit_dept && !empty($edit_dept['image_path'])): ?>
                                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3 border">
                                    <img src="<?php echo uploadUrl('departments', $edit_dept['image_path']); ?>" alt="Current" class="img-thumbnail" style="height: 48px;">
                                    <small class="text-muted">Current cover image active</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_dept || $edit_dept['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActive">Department Active &amp; Displayed Publicly</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4 pt-2 border-top d-flex justify-content-end gap-2">
                            <a href="departments.php" class="btn btn-secondary rounded-pill px-4">Cancel</a>
                            <button type="submit" name="save_dept" class="btn btn-primary rounded-pill px-4 font-semibold shadow-sm hover-lift">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> <?php echo $edit_dept ? 'Update Department' : 'Create Department'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
