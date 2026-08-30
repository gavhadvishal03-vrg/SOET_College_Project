<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_placements');

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $db = Database::getInstance();
    $rows = $db->fetchAll(
        "SELECT p.student_name, d.name as department_name, c.name as course_name, 
                p.company_name, p.package_lpa, p.placement_year, p.is_active, p.created_at 
         FROM placements p 
         JOIN departments d ON p.department_id = d.id 
         JOIN courses c ON p.course_id = c.id 
         ORDER BY p.placement_year DESC, p.package_lpa DESC"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Placements_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Department', 'Course', 'Company', 'Package LPA (₹)', 'Placement Year', 'Status', 'Record Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['student_name'],
            $r['department_name'],
            $r['course_name'],
            $r['company_name'],
            $r['package_lpa'],
            $r['placement_year'],
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Placements Cell Manager";
include_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_pl = null;
$depts = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
$courses = $db->fetchAll("SELECT * FROM courses WHERE is_active = 1 ORDER BY name");

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_pl = $db->fetchOne("SELECT * FROM placements WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_placement'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $pl_id = isset($_POST['pl_id']) ? (int)$_POST['pl_id'] : null;
    $student_name = trim($_POST['student_name']);
    $dept_id = (int)$_POST['department_id'];
    $course_id = (int)$_POST['course_id'];
    $company_name = trim($_POST['company_name']);
    $package_lpa = (float)$_POST['package_lpa'];
    $placement_year = (int)$_POST['placement_year'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($student_name) || empty($dept_id) || empty($course_id) || empty($company_name) || empty($package_lpa) || empty($placement_year)) {
        setFlash('danger', 'Please enter all mandatory fields.');
    } else {
        $uploaded_image = $edit_pl ? $edit_pl['student_image'] : null;
        $valid_upload = true;

        if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
            $errors = Security::validateUpload($_FILES['student_image'], ALLOWED_IMAGE_TYPES);
            if (!empty($errors)) {
                setFlash('danger', implode(' ', $errors));
                $valid_upload = false;
            } else {
                $uploaded_image = Security::uploadFile($_FILES['student_image'], 'placements', 'student_');
                if (!$uploaded_image) {
                    setFlash('danger', 'Failed to upload student photo.');
                    $valid_upload = false;
                }
            }
        }

        if ($valid_upload) {
            if ($pl_id) {
                // Edit
                $db->update('placements', [
                    'student_name' => $student_name,
                    'department_id' => $dept_id,
                    'course_id' => $course_id,
                    'company_name' => $company_name,
                    'package_lpa' => $package_lpa,
                    'placement_year' => $placement_year,
                    'student_image' => $uploaded_image,
                    'is_active' => $is_active
                ], 'id = ?', [$pl_id]);
                setFlash('success', 'Placement record updated successfully.');
                redirect('placements.php');
            } else {
                // Add
                $db->insert('placements', [
                    'student_name' => $student_name,
                    'department_id' => $dept_id,
                    'course_id' => $course_id,
                    'company_name' => $company_name,
                    'package_lpa' => $package_lpa,
                    'placement_year' => $placement_year,
                    'student_image' => $uploaded_image,
                    'is_active' => $is_active,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                setFlash('success', 'Placement record created successfully.');
                redirect('placements.php');
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('placements', 'id = ?', [$target_id]);
    setFlash('success', 'Placement record deleted.');
    redirect('placements.php');
}

// Fetch placements
$placements = $db->fetchAll(
    "SELECT p.*, d.name as department_name, c.name as course_name 
     FROM placements p 
     JOIN departments d ON p.department_id = d.id 
     JOIN courses c ON p.course_id = c.id 
     ORDER BY p.placement_year DESC, p.student_name ASC"
);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-briefcase text-warning me-2"></i>Placements Registry</h1>
        <small class="text-muted">Manage student corporate hiring records, compensation LPA, and company statistics</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="placements.php?action=export_csv" class="btn btn-success btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="placements.php?action=add" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Log Placement
            </a>
        </div>
    <?php else: ?>
        <a href="placements.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Course</th>
                            <th>Company Name</th>
                            <th class="text-end">Package (LPA)</th>
                            <th class="text-center">Year</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($placements)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">No placement records logged yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($placements as $pl): ?>
                                <tr>
                                    <td class="fw-bold text-primary-color"><?php echo htmlspecialchars($pl['student_name']); ?></td>
                                    <td><span class="small font-semibold text-secondary"><?php echo htmlspecialchars($pl['department_name']); ?></span></td>
                                    <td><span class="small text-muted"><?php echo htmlspecialchars($pl['course_name']); ?></span></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($pl['company_name']); ?></td>
                                    <td class="text-end text-success fw-bold">₹<?php echo number_format($pl['package_lpa'], 2); ?> LPA</td>
                                    <td class="text-center"><?php echo $pl['placement_year']; ?></td>
                                    <td class="text-center"><?php echo statusBadge($pl['is_active'] ? 'active' : 'inactive'); ?></td>
                                    <td class="text-end text-nowrap">
                                        <a href="placements.php?action=edit&id=<?php echo $pl['id']; ?>" class="btn btn-sm btn-outline-primary font-semibold me-1"><i class="fa-solid fa-pen-to-square me-1"></i> Edit Placement</a>
                                        <a href="placements.php?action=delete&id=<?php echo $pl['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm font-semibold"><i class="fa-solid fa-trash-can me-1"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-briefcase text-warning me-2"></i><?php echo $edit_pl ? 'Edit Placement Record' : 'Log Student Placement'; ?></h4>
                <form method="POST" action="placements.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_pl): ?>
                        <input type="hidden" name="pl_id" value="<?php echo $edit_pl['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Student Name *</label>
                            <input type="text" name="student_name" class="form-control" required value="<?php echo $edit_pl ? htmlspecialchars($edit_pl['student_name']) : ''; ?>" placeholder="John Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Recruiting Company *</label>
                            <input type="text" name="company_name" class="form-control" required value="<?php echo $edit_pl ? htmlspecialchars($edit_pl['company_name']) : ''; ?>" placeholder="e.g. TCS / Amazon">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Department Assignment *</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php foreach ($depts as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($edit_pl && $edit_pl['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Course / Program *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($edit_pl && $edit_pl['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold">Package (LPA) *</label>
                            <input type="number" step="0.01" name="package_lpa" class="form-control" required value="<?php echo $edit_pl ? $edit_pl['package_lpa'] : ''; ?>" placeholder="e.g. 5.50">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Placement Year *</label>
                            <input type="number" name="placement_year" class="form-control" required value="<?php echo $edit_pl ? $edit_pl['placement_year'] : date('Y'); ?>" placeholder="e.g. 2026">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Student Photo</label>
                            <input type="file" name="student_image" class="form-control" accept="image/*">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" <?php echo (!$edit_pl || $edit_pl['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActiveCheck">Mark as Active (Show in Placement highlights)</label>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4 text-end">
                            <a href="placements.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_placement" class="btn btn-primary px-4">Log Placement</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
