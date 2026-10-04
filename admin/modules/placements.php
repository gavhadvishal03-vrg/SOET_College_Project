<?php
/**
 * Placements Cell & Corporate Recruiters Manager (Admin Panel)
 *
 * Provides complete administrative control over:
 * 1. Student placement records (hiring company, package in LPA, graduation batch, student photos)
 * 2. Corporate recruiting partner companies (branding icons, logo graphics, sorting priority)
 * 3. Homepage and placement page highlight counters (highest package, placement rate, average LPA)
 * 4. Dual CSV data export pipelines for placements and recruiter directories
 *
 * @package SOET_College_Project
 * @subpackage Admin_Modules
 */

require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_placements');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'students'; // 'students' or 'recruiters' or 'stats'

// -------------------------------------------------------------------------
// CSV Data Export: Student Placements
// -------------------------------------------------------------------------
if ($action === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT p.student_name, p.job_role, d.name as department_name, c.name as course_name, 
                p.company_name, p.package_lpa, p.placement_year, p.batch, p.is_active, p.created_at 
         FROM placements p 
         JOIN departments d ON p.department_id = d.id 
         JOIN courses c ON p.course_id = c.id 
         ORDER BY p.placement_year DESC, p.package_lpa DESC"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Placements_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Job Role / Designation', 'Department', 'Course', 'Company', 'Package LPA (₹)', 'Placement Year', 'Batch', 'Status', 'Record Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['student_name'],
            $r['job_role'] ?? 'Graduate Trainee',
            $r['department_name'],
            $r['course_name'],
            $r['company_name'],
            $r['package_lpa'],
            $r['placement_year'],
            $r['batch'] ?? ($r['placement_year'] . ' Batch'),
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// -------------------------------------------------------------------------
// CSV Data Export: Corporate Recruiters
// -------------------------------------------------------------------------
if ($action === 'export_recruiters_csv') {
    $rows = $db->fetchAll("SELECT name, category, icon, color, bg_color, sort_order, is_active, created_at FROM placement_recruiters ORDER BY sort_order ASC, name ASC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Recruiters_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Company Name', 'Category', 'Icon', 'Color', 'Bg Color', 'Sort Order', 'Status', 'Created Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['name'],
            $r['category'],
            $r['icon'],
            $r['color'],
            $r['bg_color'],
            $r['sort_order'],
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Placements Cell & Corporate Recruiters Manager";
include_once __DIR__ . '/../includes/header.php';

$edit_pl = null;
$edit_recruiter = null;
$depts = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
$courses = $db->fetchAll("SELECT * FROM courses WHERE is_active = 1 ORDER BY name");

// Retrieve single placement record for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_pl = $db->fetchOne("SELECT * FROM placements WHERE id = ?", [(int)$_GET['id']]);
    $tab = 'students';
}

// Retrieve single recruiter record for editing
if ($action === 'edit_recruiter' && isset($_GET['id'])) {
    $edit_recruiter = $db->fetchOne("SELECT * FROM placement_recruiters WHERE id = ?", [(int)$_GET['id']]);
    $tab = 'recruiters';
}

// -------------------------------------------------------------------------
// POST: Create or Update Student Placement Record
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_placement'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $pl_id = isset($_POST['pl_id']) ? (int)$_POST['pl_id'] : null;
    $student_name = trim($_POST['student_name']);
    $job_role = trim($_POST['job_role'] ?? 'Graduate Engineer Trainee');
    $dept_id = (int)$_POST['department_id'];
    $course_id = (int)$_POST['course_id'];
    $company_name = trim($_POST['company_name']);
    $package_lpa = (float)$_POST['package_lpa'];
    $placement_year = (int)$_POST['placement_year'];
    $batch = trim($_POST['batch'] ?? ($placement_year . ' Batch'));
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($student_name) || empty($dept_id) || empty($course_id) || empty($company_name) || empty($package_lpa) || empty($placement_year)) {
        setFlash('danger', 'Please enter all mandatory fields.');
    } else {
        $uploaded_image = $edit_pl ? $edit_pl['student_image'] : null;
        $valid_upload = true;

        // Process student portrait photo upload
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
            $data = [
                'student_name' => $student_name,
                'job_role' => $job_role,
                'department_id' => $dept_id,
                'course_id' => $course_id,
                'company_name' => $company_name,
                'package_lpa' => $package_lpa,
                'placement_year' => $placement_year,
                'batch' => $batch,
                'student_image' => $uploaded_image,
                'sort_order' => $sort_order,
                'is_active' => $is_active
            ];

            if ($pl_id) {
                // Update placement record
                $db->update('placements', $data, 'id = ?', [$pl_id]);
                setFlash('success', 'Placement record updated successfully.');
            } else {
                // Insert new placement record
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('placements', $data);
                setFlash('success', 'Student placement record created successfully.');
            }
            redirect('placements.php?tab=students');
        }
    }
}

// -------------------------------------------------------------------------
// POST: Create or Update Corporate Recruiter Record
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_recruiter'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $rec_id = isset($_POST['rec_id']) ? (int)$_POST['rec_id'] : null;
    $name = trim($_POST['rec_name']);
    $category = trim($_POST['rec_category'] ?? 'Prime Recruiter');
    $icon = trim($_POST['rec_icon'] ?? 'fa-solid fa-briefcase');
    $color = trim($_POST['rec_color'] ?? '#2563eb');
    $bg_color = trim($_POST['rec_bg_color'] ?? 'rgba(37, 99, 235, 0.08)');
    $sort_order = (int)($_POST['rec_sort_order'] ?? 0);
    $is_active = isset($_POST['rec_is_active']) ? 1 : 0;

    $logo_image = $edit_recruiter ? $edit_recruiter['logo_image'] : null;

    // Process recruiter logo upload
    if (isset($_FILES['rec_logo']) && $_FILES['rec_logo']['error'] === UPLOAD_ERR_OK) {
        $errors = Security::validateUpload($_FILES['rec_logo'], ALLOWED_IMAGE_TYPES);
        if (empty($errors)) {
            $uploaded = Security::uploadFile($_FILES['rec_logo'], 'placements', 'recruiter_');
            if ($uploaded) {
                $logo_image = $uploaded;
            }
        } else {
            setFlash('danger', implode(' ', $errors));
        }
    }

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($name)) {
        setFlash('danger', 'Please enter company name.');
    } else {
        $recData = [
            'name' => $name,
            'category' => $category,
            'icon' => $icon,
            'color' => $color,
            'bg_color' => $bg_color,
            'logo_image' => $logo_image,
            'sort_order' => $sort_order,
            'is_active' => $is_active
        ];

        if ($rec_id) {
            // Update recruiter
            $db->update('placement_recruiters', $recData, 'id = ?', [$rec_id]);
            setFlash('success', 'Recruiter partner details updated.');
        } else {
            // Insert recruiter
            $recData['created_at'] = date('Y-m-d H:i:s');
            $db->insert('placement_recruiters', $recData);
            setFlash('success', 'New recruiter partner registered.');
        }
        redirect('placements.php?tab=recruiters');
    }
}

// -------------------------------------------------------------------------
// POST: Save Placement Key Highlight Statistics
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_placement_stats'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $statKeys = [
            'placements_stat1_val', 'placements_stat1_label',
            'placements_stat2_val', 'placements_stat2_label',
            'placements_stat3_val', 'placements_stat3_label',
            'placements_stat4_val', 'placements_stat4_label'
        ];
        foreach ($statKeys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                $exists = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
                if ($exists) {
                    $db->update('site_settings', ['setting_value' => $val], 'setting_key = ?', [$key]);
                } else {
                    $db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $val]);
                }
            }
        }
        setFlash('success', 'Placement statistics counters updated successfully.');
        redirect('placements.php?tab=stats');
    }
}

// -------------------------------------------------------------------------
// GET: Delete Student Placement Record
// -------------------------------------------------------------------------
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('placements', 'id = ?', [$target_id]);
    setFlash('success', 'Placement record deleted.');
    redirect('placements.php?tab=students');
}

// -------------------------------------------------------------------------
// GET: 1-Click Toggle Student Placement Active Status
// -------------------------------------------------------------------------
if (isset($_GET['toggle_pl_status']) && isset($_GET['id'])) {
    $pl_id = (int)$_GET['id'];
    $current = $db->fetchOne("SELECT is_active FROM placements WHERE id = ?", [$pl_id]);
    if ($current) {
        $new_status = $current['is_active'] ? 0 : 1;
        $db->update('placements', ['is_active' => $new_status], 'id = ?', [$pl_id]);
        setFlash('success', 'Student placement status updated.');
    }
    redirect('placements.php?tab=students');
}

// -------------------------------------------------------------------------
// GET: Delete Recruiter Partner
// -------------------------------------------------------------------------
if ($action === 'delete_recruiter' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('placement_recruiters', 'id = ?', [$target_id]);
    setFlash('success', 'Recruiter partner removed.');
    redirect('placements.php?tab=recruiters');
}

// -------------------------------------------------------------------------
// GET: 1-Click Toggle Recruiter Active Status
// -------------------------------------------------------------------------
if (isset($_GET['toggle_rec_status']) && isset($_GET['id'])) {
    $rec_id = (int)$_GET['id'];
    $current = $db->fetchOne("SELECT is_active FROM placement_recruiters WHERE id = ?", [$rec_id]);
    if ($current) {
        $new_status = $current['is_active'] ? 0 : 1;
        $db->update('placement_recruiters', ['is_active' => $new_status], 'id = ?', [$rec_id]);
        setFlash('success', 'Recruiter visibility updated.');
    }
    redirect('placements.php?tab=recruiters');
}

// -------------------------------------------------------------------------
// Fetch Placements, Recruiters, and Metric Settings
// -------------------------------------------------------------------------
$placements = $db->fetchAll(
    "SELECT p.*, d.name as department_name, c.name as course_name 
     FROM placements p 
     JOIN departments d ON p.department_id = d.id 
     JOIN courses c ON p.course_id = c.id 
     ORDER BY p.sort_order ASC, p.placement_year DESC, p.package_lpa DESC"
);

$recruiters = $db->fetchAll("SELECT * FROM placement_recruiters ORDER BY sort_order ASC, name ASC");

$rawStats = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'placements_stat%'");
$statsSettings = [];
foreach ($rawStats as $s) {
    $statsSettings[$s['setting_key']] = $s['setting_value'];
}
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Corporate Relations &amp; Placement Cell</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Placements &amp; Prime Recruiters Hub</h2>
        <p class="text-muted small mb-0">Manage corporate partner companies, student hiring records, compensation LPA packages, and hiring statistics</p>
    </div>
    
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if ($tab === 'students' && $action === 'list'): ?>
            <a href="placements.php?action=export_csv" class="btn btn-success btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export Placements CSV
            </a>
            <a href="placements.php?action=add&tab=students" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-plus me-1 text-warning"></i> Log Student Placement
            </a>
        <?php elseif ($tab === 'recruiters' && $action === 'list'): ?>
            <a href="placements.php?action=export_recruiters_csv" class="btn btn-success btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export Recruiters CSV
            </a>
            <a href="placements.php?action=add_recruiter&tab=recruiters" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-plus me-1 text-warning"></i> Add Recruiter Partner
            </a>
        <?php else: ?>
            <a href="placements.php?tab=<?php echo htmlspecialchars($tab); ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-angle-left me-1"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills gap-2 mb-4 bg-white p-2 rounded-4 shadow-sm border border-light-subtle" role="tablist">
    <li class="nav-item flex-fill" role="presentation">
        <a class="nav-link text-center font-semibold rounded-pill py-2.5 <?php echo $tab === 'students' ? 'active' : 'text-dark'; ?>" href="placements.php?tab=students">
            <i class="fa-solid fa-user-graduate me-1.5"></i> Student Placements (<?php echo count($placements); ?>)
        </a>
    </li>
    <li class="nav-item flex-fill" role="presentation">
        <a class="nav-link text-center font-semibold rounded-pill py-2.5 <?php echo $tab === 'recruiters' ? 'active' : 'text-dark'; ?>" href="placements.php?tab=recruiters">
            <i class="fa-solid fa-building-circle-check me-1.5"></i> Prime &amp; Corporate Recruiters (<?php echo count($recruiters); ?>)
        </a>
    </li>
    <li class="nav-item flex-fill" role="presentation">
        <a class="nav-link text-center font-semibold rounded-pill py-2.5 <?php echo $tab === 'stats' ? 'active' : 'text-dark'; ?>" href="placements.php?tab=stats">
            <i class="fa-solid fa-chart-line me-1.5"></i> Placement Statistics Counters
        </a>
    </li>
</ul>

<?php if ($tab === 'students'): ?>
    <?php if ($action === 'list'): ?>
        <!-- Student Placements Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Logged Placements (<?php echo count($placements); ?>)</h5>
                <input type="text" id="plSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search student, company, branch..." style="width: 260px;">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="placementsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Student &amp; Job Role</th>
                                <th>Department</th>
                                <th>Course</th>
                                <th>Company Name</th>
                                <th class="text-end">Package (LPA)</th>
                                <th class="text-center">Batch / Year</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Actions</th>
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
                                        <td class="ps-4 font-display fw-bold text-dark">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <?php if (!empty($pl['student_image'])): ?>
                                                    <img src="<?php echo uploadUrl('placements', $pl['student_image']); ?>" class="rounded-circle shadow-xs" style="width: 38px; height: 38px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 14px;">
                                                        <?php echo strtoupper(substr($pl['student_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <span class="d-block text-dark fw-bold"><?php echo htmlspecialchars($pl['student_name']); ?></span>
                                                    <small class="text-primary fw-semibold d-block" style="font-size: 14px;"><i class="fa-solid fa-briefcase me-1 text-primary"></i><?php echo htmlspecialchars($pl['job_role'] ?? 'Graduate Trainee'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="small font-semibold text-secondary"><?php echo htmlspecialchars($pl['department_name']); ?></span></td>
                                        <td><span class="small text-muted"><?php echo htmlspecialchars($pl['course_name']); ?></span></td>
                                        <td class="font-display fw-bold text-primary"><?php echo htmlspecialchars($pl['company_name']); ?></td>
                                        <td class="text-end text-success fw-bold">₹<?php echo number_format($pl['package_lpa'], 2); ?> LPA</td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($pl['batch'] ?? ($pl['placement_year'] . ' Batch')); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="placements.php?toggle_pl_status=1&id=<?php echo $pl['id']; ?>&tab=students" class="text-decoration-none" title="Click to toggle active status">
                                                <?php echo statusBadge($pl['is_active'] ? 'active' : 'inactive'); ?>
                                            </a>
                                        </td>
                                        <td class="text-end pe-4 text-nowrap">
                                            <a href="placements.php?action=edit&id=<?php echo $pl['id']; ?>&tab=students" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                            </a>
                                            <a href="placements.php?action=delete&id=<?php echo $pl['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 btn-delete-confirm font-semibold" onclick="return confirm('Delete this placement record?');">
                                                <i class="fa-solid fa-trash-can me-1"></i> Delete
                                            </a>
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
        <!-- Add / Edit Student Placement Form -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h4 class="fw-bold text-dark font-display mb-4">
                        <i class="fa-solid fa-briefcase text-warning me-2"></i><?php echo $edit_pl ? 'Edit Student Placement Record' : 'Log Student Placement'; ?>
                    </h4>
                    <form method="POST" action="placements.php" enctype="multipart/form-data">
                        <?php echo Security::csrfField(); ?>
                        <?php if ($edit_pl): ?>
                            <input type="hidden" name="pl_id" value="<?php echo $edit_pl['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Student Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="student_name" class="form-control rounded-3" required value="<?php echo $edit_pl ? htmlspecialchars($edit_pl['student_name']) : ''; ?>" placeholder="e.g. Vishal Gavhad">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Job Role / Designation <span class="text-danger">*</span></label>
                                <input type="text" name="job_role" class="form-control rounded-3" required value="<?php echo $edit_pl ? htmlspecialchars($edit_pl['job_role'] ?? 'Graduate Trainee') : 'Associate Software Engineer'; ?>" placeholder="e.g. Associate Software Engineer / Embedded Developer">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Recruiting Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control rounded-3" required value="<?php echo $edit_pl ? htmlspecialchars($edit_pl['company_name']) : ''; ?>" placeholder="e.g. TCS / Amazon / Infosys">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select rounded-3" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($depts as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo ($edit_pl && $edit_pl['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Program / Course <span class="text-danger">*</span></label>
                                <select name="course_id" class="form-select rounded-3" required>
                                    <option value="">Select Program</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo ($edit_pl && $edit_pl['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Package (LPA ₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="package_lpa" class="form-control rounded-3" required value="<?php echo $edit_pl ? $edit_pl['package_lpa'] : ''; ?>" placeholder="e.g. 5.50">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Placement Year <span class="text-danger">*</span></label>
                                <input type="number" name="placement_year" class="form-control rounded-3" required value="<?php echo $edit_pl ? $edit_pl['placement_year'] : date('Y'); ?>" placeholder="e.g. 2026">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Batch Label</label>
                                <input type="text" name="batch" class="form-control rounded-3" value="<?php echo $edit_pl ? htmlspecialchars($edit_pl['batch'] ?? '') : '2026 Batch'; ?>" placeholder="e.g. 2026 Batch">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Display Sort Order</label>
                                <input type="number" name="sort_order" class="form-control rounded-3" value="<?php echo $edit_pl ? (int)($edit_pl['sort_order'] ?? 0) : 0; ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label font-semibold text-dark small">Student Photograph</label>
                                <input type="file" name="student_image" class="form-control rounded-3 mb-2" accept="image/*">
                                <?php if ($edit_pl && !empty($edit_pl['student_image'])): ?>
                                    <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3 border">
                                        <img src="<?php echo uploadUrl('placements', $edit_pl['student_image']); ?>" class="rounded-circle shadow-xs" style="width: 36px; height: 36px; object-fit: cover;">
                                        <small class="text-muted">Current photo attached</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" <?php echo (!$edit_pl || $edit_pl['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-semibold" for="isActiveCheck">Mark as Active (Show on Website Placements Page)</label>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4 pt-2 border-top d-flex justify-content-end gap-2">
                                <a href="placements.php?tab=students" class="btn btn-secondary rounded-pill px-4">Cancel</a>
                                <button type="submit" name="save_placement" class="btn btn-primary rounded-pill px-4 font-semibold shadow-sm hover-lift">
                                    <i class="fa-solid fa-floppy-disk me-1.5"></i> <?php echo $edit_pl ? 'Update Placement' : 'Log Placement'; ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php elseif ($tab === 'recruiters'): ?>
    <?php if ($action === 'list'): ?>
        <!-- Prime Recruiters Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-building-shield text-primary me-2"></i>Recruiter Partners (<?php echo count($recruiters); ?>)</h5>
                <input type="text" id="recSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search company name, category..." style="width: 260px;">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="recruitersTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 12%;">Logo / Icon</th>
                                <th>Company / Partner Name</th>
                                <th>Category</th>
                                <th>Sort Order</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recruiters)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">No recruiter partners registered yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recruiters as $rec): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 p-2 shadow-xs" style="width: 44px; height: 44px; background: <?php echo htmlspecialchars($rec['bg_color'] ?? '#f1f5f9'); ?>; color: <?php echo htmlspecialchars($rec['color'] ?? '#2563eb'); ?>;">
                                                <?php if (!empty($rec['logo_image'])): ?>
                                                    <img src="<?php echo uploadUrl('placements', $rec['logo_image']); ?>" alt="<?php echo htmlspecialchars($rec['name']); ?>" style="max-width: 32px; max-height: 32px; object-fit: contain;">
                                                <?php else: ?>
                                                    <i class="<?php echo htmlspecialchars($rec['icon'] ?? 'fa-solid fa-briefcase'); ?> fs-5"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="font-display fw-bold text-dark">
                                            <?php echo htmlspecialchars($rec['name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo htmlspecialchars($rec['category']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?php echo (int)$rec['sort_order']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="placements.php?toggle_rec_status=1&id=<?php echo $rec['id']; ?>&tab=recruiters" class="text-decoration-none" title="Toggle active status">
                                                <?php echo statusBadge($rec['is_active'] ? 'active' : 'inactive'); ?>
                                            </a>
                                        </td>
                                        <td class="text-end pe-4 text-nowrap">
                                            <a href="placements.php?action=edit_recruiter&id=<?php echo $rec['id']; ?>&tab=recruiters" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                            </a>
                                            <a href="placements.php?action=delete_recruiter&id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 btn-delete-confirm font-semibold" onclick="return confirm('Delete this recruiter partner?');">
                                                <i class="fa-solid fa-trash-can me-1"></i> Delete
                                            </a>
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
        <!-- Add / Edit Recruiter Form -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h4 class="fw-bold text-dark font-display mb-4">
                        <i class="fa-solid fa-building text-primary me-2"></i><?php echo $edit_recruiter ? 'Edit Recruiter Partner' : 'Register New Recruiter Partner'; ?>
                    </h4>
                    <form method="POST" action="placements.php" enctype="multipart/form-data">
                        <?php echo Security::csrfField(); ?>
                        <?php if ($edit_recruiter): ?>
                            <input type="hidden" name="rec_id" value="<?php echo $edit_recruiter['id']; ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label font-semibold text-dark small">Recruiting Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="rec_name" class="form-control rounded-3" required value="<?php echo $edit_recruiter ? htmlspecialchars($edit_recruiter['name']) : ''; ?>" placeholder="e.g. Tata Consultancy Services (TCS)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Category</label>
                                <input type="text" name="rec_category" class="form-control rounded-3" value="<?php echo $edit_recruiter ? htmlspecialchars($edit_recruiter['category']) : 'Prime Recruiter'; ?>" placeholder="e.g. Prime Recruiter / Tech Partner">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">FontAwesome Icon</label>
                                <input type="text" name="rec_icon" class="form-control rounded-3" value="<?php echo $edit_recruiter ? htmlspecialchars($edit_recruiter['icon']) : 'fa-solid fa-briefcase'; ?>" placeholder="fa-solid fa-building">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Icon Color (Hex / CSS)</label>
                                <input type="text" name="rec_color" class="form-control rounded-3" value="<?php echo $edit_recruiter ? htmlspecialchars($edit_recruiter['color']) : '#2563eb'; ?>" placeholder="#2563eb">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Icon Background Color</label>
                                <input type="text" name="rec_bg_color" class="form-control rounded-3" value="<?php echo $edit_recruiter ? htmlspecialchars($edit_recruiter['bg_color']) : 'rgba(37, 99, 235, 0.08)'; ?>" placeholder="rgba(37, 99, 235, 0.08)">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Display Sort Order</label>
                                <input type="number" name="rec_sort_order" class="form-control rounded-3" value="<?php echo $edit_recruiter ? (int)$edit_recruiter['sort_order'] : 0; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Company Logo (Optional)</label>
                                <input type="file" name="rec_logo" class="form-control rounded-3 mb-2" accept="image/*">
                                <?php if ($edit_recruiter && !empty($edit_recruiter['logo_image'])): ?>
                                    <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3 border">
                                        <img src="<?php echo uploadUrl('placements', $edit_recruiter['logo_image']); ?>" style="max-height: 28px;">
                                        <small class="text-muted">Current logo attached</small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="rec_is_active" id="recIsActive" <?php echo (!$edit_recruiter || $edit_recruiter['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-semibold" for="recIsActive">Active &amp; Display in Recruiters Grid</label>
                                </div>
                            </div>

                            <div class="col-12 mt-4 pt-2 border-top d-flex justify-content-end gap-2">
                                <a href="placements.php?tab=recruiters" class="btn btn-secondary rounded-pill px-4">Cancel</a>
                                <button type="submit" name="save_recruiter" class="btn btn-primary rounded-pill px-4 font-semibold shadow-sm hover-lift">
                                    <i class="fa-solid fa-floppy-disk me-1.5"></i> <?php echo $edit_recruiter ? 'Update Recruiter' : 'Register Recruiter'; ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php elseif ($tab === 'stats'): ?>
    <!-- Placement Statistics Editor -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                    <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0 font-display">Placement Statistics Counters</h4>
                        <small class="text-muted">Update the 4 headline statistic cards shown on the placements portal</small>
                    </div>
                </div>

                <form method="POST" action="placements.php">
                    <?php echo Security::csrfField(); ?>
                    <div class="row g-3">
                        <!-- Stat 1 -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                <label class="form-label font-semibold text-dark small">Stat Counter 1 (Value) <span class="text-danger">*</span></label>
                                <input type="text" name="placements_stat1_val" class="form-control rounded-3 mb-2" required value="<?php echo htmlspecialchars($statsSettings['placements_stat1_val'] ?? '12+'); ?>">
                                <label class="form-label font-semibold text-muted small">Counter 1 Label</label>
                                <input type="text" name="placements_stat1_label" class="form-control form-control-sm rounded-3" required value="<?php echo htmlspecialchars($statsSettings['placements_stat1_label'] ?? '2026 Batch Placed'); ?>">
                            </div>
                        </div>

                        <!-- Stat 2 -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                <label class="form-label font-semibold text-dark small">Stat Counter 2 (Value) <span class="text-danger">*</span></label>
                                <input type="text" name="placements_stat2_val" class="form-control rounded-3 mb-2" required value="<?php echo htmlspecialchars($statsSettings['placements_stat2_val'] ?? '7+'); ?>">
                                <label class="form-label font-semibold text-muted small">Counter 2 Label</label>
                                <input type="text" name="placements_stat2_label" class="form-control form-control-sm rounded-3" required value="<?php echo htmlspecialchars($statsSettings['placements_stat2_label'] ?? 'Active Recruiters (2026)'); ?>">
                            </div>
                        </div>

                        <!-- Stat 3 -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                <label class="form-label font-semibold text-dark small">Stat Counter 3 (Value) <span class="text-danger">*</span></label>
                                <input type="text" name="placements_stat3_val" class="form-control rounded-3 mb-2" required value="<?php echo htmlspecialchars($statsSettings['placements_stat3_val'] ?? '15+'); ?>">
                                <label class="form-label font-semibold text-muted small">Counter 3 Label</label>
                                <input type="text" name="placements_stat3_label" class="form-control form-control-sm rounded-3" required value="<?php echo htmlspecialchars($statsSettings['placements_stat3_label'] ?? 'Industry MoUs & Partners'); ?>">
                            </div>
                        </div>

                        <!-- Stat 4 -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                <label class="form-label font-semibold text-dark small">Stat Counter 4 (Value) <span class="text-danger">*</span></label>
                                <input type="text" name="placements_stat4_val" class="form-control rounded-3 mb-2" required value="<?php echo htmlspecialchars($statsSettings['placements_stat4_val'] ?? '100%'); ?>">
                                <label class="form-label font-semibold text-muted small">Counter 4 Label</label>
                                <input type="text" name="placements_stat4_label" class="form-control form-control-sm rounded-3" required value="<?php echo htmlspecialchars($statsSettings['placements_stat4_label'] ?? 'Verified Placement Track'); ?>">
                            </div>
                        </div>

                        <div class="col-12 mt-4 pt-2 border-top d-flex justify-content-end">
                            <button type="submit" name="save_placement_stats" class="btn btn-primary rounded-pill px-4 font-semibold shadow-sm hover-lift">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Placement Statistics
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const plSearch = document.getElementById('plSearchInput');
    if (plSearch) {
        plSearch.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#placementsTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    const recSearch = document.getElementById('recSearchInput');
    if (recSearch) {
        recSearch.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#recruitersTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
