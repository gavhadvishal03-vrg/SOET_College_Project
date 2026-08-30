<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_departments');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_dept = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT d.name, d.code, d.description, d.is_active, 
                (SELECT COUNT(*) FROM courses WHERE department_id = d.id) as course_count,
                (SELECT COUNT(*) FROM faculty WHERE department_id = d.id) as faculty_count
         FROM departments d ORDER BY d.name"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Departments_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Department Name', 'Code', 'Description', 'Courses Offered', 'Faculty Members', 'Status']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['name'],
            $r['code'],
            $r['description'],
            $r['course_count'],
            $r['faculty_count'],
            $r['is_active'] ? 'Active' : 'Inactive'
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Departments Manager";
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
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $image_path = null;
    if ($dept_id) {
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
        if ($dept_id) {
            // Edit
            $db->update('departments', [
                'name' => $name,
                'code' => $code,
                'description' => $description,
                'image_path' => $image_path,
                'is_active' => $is_active
            ], 'id = ?', [$dept_id]);
            setFlash('success', 'Department details updated successfully.');
            redirect('departments.php');
        } else {
            // Add
            $exists = $db->fetchOne("SELECT id FROM departments WHERE name = ? OR code = ?", [$name, $code]);
            if ($exists) {
                setFlash('danger', 'Department with this name or code already exists.');
            } else {
                $db->insert('departments', [
                    'name' => $name,
                    'code' => $code,
                    'description' => $description,
                    'image_path' => $image_path,
                    'is_active' => $is_active
                ]);
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

// Fetch departments
$departments = $db->fetchAll(
    "SELECT d.*, 
            (SELECT COUNT(*) FROM courses WHERE department_id = d.id) as course_count,
            (SELECT COUNT(*) FROM faculty WHERE department_id = d.id) as faculty_count
     FROM departments d ORDER BY d.name"
);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-building-columns text-warning me-2"></i>Academic Departments Management</h1>
        <small class="text-muted">Manage academic branches, engineering disciplines, and lab facility mappings</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="departments.php?action=export_csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="departments.php?action=add" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Create Department</a>
        </div>
    <?php else: ?>
        <a href="departments.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-network-wired text-warning me-2"></i>Departments (<?php echo count($departments); ?>)</h5>
            <input type="text" id="deptSearchInput" class="form-control form-control-sm" placeholder="Search department name, code..." style="width: 250px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="departmentsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Cover Image</th>
                            <th>Department Name</th>
                            <th>Code</th>
                            <th>Allocated Programs</th>
                            <th>Faculty Strength</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($dept['image_path'])): ?>
                                        <img src="<?php echo uploadUrl('departments', $dept['image_path']); ?>" alt="Cover" class="rounded img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded text-muted d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px; font-size: 10px;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary-color"><?php echo htmlspecialchars($dept['name']); ?></div>
                                    <small class="text-muted"><?php echo truncate(htmlspecialchars($dept['description']), 80); ?></small>
                                </td>
                                <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($dept['code']); ?></span></td>
                                <td><span class="badge bg-primary"><?php echo $dept['course_count']; ?> Courses</span></td>
                                <td><span class="badge bg-info text-dark"><?php echo $dept['faculty_count']; ?> Faculty</span></td>
                                <td class="text-center"><?php echo statusBadge($dept['is_active'] ? 'active' : 'inactive'); ?></td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="departments.php?action=edit&id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <a href="departments.php?action=delete&id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete" onclick="return confirm('Delete this department?');"><i class="fa-solid fa-trash"></i></a>
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
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-network-wired text-warning me-2"></i><?php echo $edit_dept ? 'Edit Department Details' : 'Create Department'; ?></h4>
                <form method="POST" action="departments.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_dept): ?>
                        <input type="hidden" name="dept_id" value="<?php echo $edit_dept['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label font-semibold">Department Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_dept['name'] ?? ''); ?>" placeholder="Department of Computer Science & Engineering">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold">Department Code *</label>
                            <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($edit_dept['code'] ?? ''); ?>" placeholder="CSE">
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Department Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Brief outline of research, infrastructure, and vision..."><?php echo htmlspecialchars($edit_dept['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label font-semibold">Upload Cover / Lab Banner</label>
                            <input type="file" name="dept_image" class="form-control" accept="image/*">
                        </div>

                        <?php if ($edit_dept && !empty($edit_dept['image_path'])): ?>
                            <div class="col-md-4">
                                <label class="form-label font-semibold d-block">Current Cover</label>
                                <img src="<?php echo uploadUrl('departments', $edit_dept['image_path']); ?>" alt="Current" class="img-thumbnail" style="height: 60px;">
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_dept || $edit_dept['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActive">Department Active &amp; Displayed Publicly</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="departments.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_dept" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Department</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
