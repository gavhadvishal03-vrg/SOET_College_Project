<?php
/**
 * Academic Tuition & Fee Structures Manager Module
 *
 * Comprehensive tuition fee matrix control center:
 * - Year-wise fee schedule creation for all degrees (Year 1 to Year 6)
 * - Tuition fee, development fee, exam fee, and other charges breakdown
 * - One-click Academic Year cloning to replicate fee structures for new academic sessions
 * - Active Academic Year configuration for public website display
 * - Filtering by academic session, program type, and course
 *
 * Required Permission: 'manage_fees'
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_fees');

$db = Database::getInstance();
$cms = new ContentManager();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_fee = null;

// Available Courses & Academic Years
$courses = $db->fetchAll("SELECT c.*, d.name as department_name FROM courses c JOIN departments d ON c.department_id = d.id WHERE c.is_active = 1 ORDER BY c.sort_order ASC, c.name ASC");
$coursesById = [];
foreach ($courses as $c) {
    $coursesById[$c['id']] = $c;
}

$distinctAYs = $cms->getAllDistinctAcademicYears();
$activeAY = $cms->getActiveAcademicYear();

// Filter values from query string
$filterAY = isset($_GET['filter_ay']) ? trim($_GET['filter_ay']) : $activeAY;
$filterType = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : 'all';
$filterCourse = isset($_GET['filter_course']) ? (int)$_GET['filter_course'] : 0;

// Handle Set Default Active Academic Year
if ($action === 'set_active_ay' && isset($_POST['active_academic_year'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (Security::validateCSRF($csrf)) {
        $newActiveAY = trim($_POST['active_academic_year']);
        if (!empty($newActiveAY)) {
            $cms->setSetting('active_academic_year', $newActiveAY);
            setFlash('success', "Active Academic Year updated to {$newActiveAY} for public website display.");
        }
    }
    redirect('fees.php?filter_ay=' . urlencode($newActiveAY ?? $filterAY));
}

// Handle Clone / Duplicate Entire Academic Year
if ($action === 'clone_academic_year' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $sourceAY = trim($_POST['source_ay'] ?? '');
    $targetAY = trim($_POST['target_ay'] ?? '');

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($sourceAY) || empty($targetAY)) {
        setFlash('danger', 'Please specify both Source and Target Academic Years.');
    } elseif ($sourceAY === $targetAY) {
        setFlash('danger', 'Source and Target Academic Years must be different.');
    } else {
        $sourceRecords = $db->fetchAll("SELECT * FROM fees WHERE academic_year = ?", [$sourceAY]);
        if (empty($sourceRecords)) {
            setFlash('danger', "No existing fee records found for Academic Year: {$sourceAY}");
        } else {
            $clonedCount = 0;
            foreach ($sourceRecords as $r) {
                // Check if already exists in target AY
                $exists = $db->fetchOne(
                    "SELECT id FROM fees WHERE course_id = ? AND academic_year = ? AND study_year = ? AND category = ?",
                    [$r['course_id'], $targetAY, $r['study_year'], $r['category']]
                );
                if (!$exists) {
                    $db->insert('fees', [
                        'course_id' => $r['course_id'],
                        'study_year' => $r['study_year'],
                        'year_name' => $r['year_name'],
                        'category' => $r['category'],
                        'tuition_fee' => $r['tuition_fee'],
                        'development_fee' => $r['development_fee'],
                        'exam_fee' => $r['exam_fee'],
                        'other_fee' => $r['other_fee'],
                        'total_fee' => $r['total_fee'],
                        'semesters_included' => $r['semesters_included'],
                        'fee_breakdown_json' => $r['fee_breakdown_json'],
                        'semester' => $r['semester'],
                        'amount' => $r['total_fee'],
                        'academic_year' => $targetAY,
                        'status' => 'active',
                        'is_active' => 1,
                        'remarks' => "Cloned from AY {$sourceAY} schedule.",
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $clonedCount++;
                }
            }
            setFlash('success', "Successfully cloned {$clonedCount} fee schedules to Academic Year {$targetAY}!");
            redirect('fees.php?filter_ay=' . urlencode($targetAY));
        }
    }
}

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $cms->getFeeStructures($filterCourse ?: null, ($filterAY !== 'all') ? $filterAY : null, false, ($filterType !== 'all') ? $filterType : null);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_YearWise_Fee_Structures_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Program Name',
        'Course Code',
        'Department',
        'Program Type',
        'Academic Year',
        'Study Year',
        'Year Title',
        'Category',
        'Semesters Included',
        'Tuition Fee (INR)',
        'Development Fee (INR)',
        'Examination Fee (INR)',
        'Other Charges (INR)',
        'Total Annual Fee (INR)',
        'Status'
    ]);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['course_name'],
            $r['course_code'],
            $r['department_name'],
            ucfirst($r['program_type']),
            $r['academic_year'],
            'Year ' . $r['study_year'],
            $r['year_name'],
            $r['category'],
            $r['semesters_included'],
            $r['tuition_fee'],
            $r['development_fee'],
            $r['exam_fee'],
            $r['other_fee'],
            $r['total_fee'],
            $r['is_active'] ? 'Active' : 'Inactive'
        ]);
    }
    fclose($output);
    exit;
}

// Handle Status Toggle
if ($action === 'toggle_status' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $cur = $db->fetchOne("SELECT is_active FROM fees WHERE id = ?", [$target_id]);
    if ($cur) {
        $newStatus = $cur['is_active'] ? 0 : 1;
        $db->update('fees', ['is_active' => $newStatus, 'status' => $newStatus ? 'active' : 'archived'], 'id = ?', [$target_id]);
        setFlash('success', 'Fee structure status updated.');
    }
    redirect('fees.php?filter_ay=' . urlencode($filterAY));
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('fees', 'id = ?', [$target_id]);
    setFlash('success', 'Fee configuration schedule deleted successfully.');
    redirect('fees.php?filter_ay=' . urlencode($filterAY));
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fee'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $fee_id = isset($_POST['fee_id']) ? (int)$_POST['fee_id'] : null;
    $course_id = (int)($_POST['course_id'] ?? 0);
    $academic_year = trim($_POST['academic_year'] ?? '');
    $study_year = (int)($_POST['study_year'] ?? 1);
    $year_name = trim($_POST['year_name'] ?? "{$study_year}st Year");
    $category = trim($_POST['category'] ?? 'General / Open');
    $semesters_included = trim($_POST['semesters_included'] ?? "Semester " . (($study_year * 2) - 1) . " & Semester " . ($study_year * 2));
    
    $tuition_fee = (float)($_POST['tuition_fee'] ?? 0);
    $development_fee = (float)($_POST['development_fee'] ?? 0);
    $exam_fee = (float)($_POST['exam_fee'] ?? 0);
    $other_fee = (float)($_POST['other_fee'] ?? 0);
    
    // Calculate total automatically
    $total_fee = (float)($_POST['total_fee'] ?? 0);
    if ($total_fee <= 0 || isset($_POST['auto_recalculate'])) {
        $total_fee = $tuition_fee + $development_fee + $exam_fee + $other_fee;
    }
    
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $status = $is_active ? 'active' : 'archived';
    $remarks = trim($_POST['remarks'] ?? '');

    $breakdownJson = json_encode([
        ['title' => 'Tuition Fee', 'amount' => $tuition_fee],
        ['title' => 'Development Fee', 'amount' => $development_fee],
        ['title' => 'Semester Examination Fee', 'amount' => $exam_fee],
        ['title' => 'Student Welfare, Gymkhana & Library Fund', 'amount' => $other_fee],
    ]);

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($course_id) || empty($academic_year) || empty($study_year) || $total_fee <= 0) {
        setFlash('danger', 'Please enter all required fields with valid fee amounts.');
    } else {
        if ($fee_id) {
            // Edit existing
            $db->update('fees', [
                'course_id' => $course_id,
                'study_year' => $study_year,
                'year_name' => $year_name,
                'category' => $category,
                'tuition_fee' => $tuition_fee,
                'development_fee' => $development_fee,
                'exam_fee' => $exam_fee,
                'other_fee' => $other_fee,
                'total_fee' => $total_fee,
                'semesters_included' => $semesters_included,
                'fee_breakdown_json' => $breakdownJson,
                'semester' => $study_year * 2,
                'amount' => $total_fee,
                'academic_year' => $academic_year,
                'status' => $status,
                'is_active' => $is_active,
                'remarks' => $remarks
            ], 'id = ?', [$fee_id]);
            setFlash('success', "Fee structure for {$year_name} ({$academic_year}) modified successfully.");
            redirect('fees.php?filter_ay=' . urlencode($academic_year));
        } else {
            // Add new
            $exists = $db->fetchOne(
                "SELECT id FROM fees WHERE course_id = ? AND academic_year = ? AND study_year = ? AND category = ?",
                [$course_id, $academic_year, $study_year, $category]
            );
            if ($exists) {
                setFlash('danger', "A fee structure for this course, Academic Year ({$academic_year}), Study Year ({$year_name}), and Category ({$category}) already exists. Please edit the existing entry.");
            } else {
                $db->insert('fees', [
                    'course_id' => $course_id,
                    'study_year' => $study_year,
                    'year_name' => $year_name,
                    'category' => $category,
                    'tuition_fee' => $tuition_fee,
                    'development_fee' => $development_fee,
                    'exam_fee' => $exam_fee,
                    'other_fee' => $other_fee,
                    'total_fee' => $total_fee,
                    'semesters_included' => $semesters_included,
                    'fee_breakdown_json' => $breakdownJson,
                    'semester' => $study_year * 2,
                    'amount' => $total_fee,
                    'academic_year' => $academic_year,
                    'status' => $status,
                    'is_active' => $is_active,
                    'remarks' => $remarks,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                setFlash('success', "Year-wise fee structure for {$year_name} ({$academic_year}) configured successfully.");
                redirect('fees.php?filter_ay=' . urlencode($academic_year));
            }
        }
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_fee = $db->fetchOne("SELECT * FROM fees WHERE id = ?", [(int)$_GET['id']]);
    if (!$edit_fee) {
        setFlash('danger', 'Fee record not found.');
        redirect('fees.php');
    }
}

// Fetch filtered fees
$fees = $cms->getFeeStructures($filterCourse ?: null, ($filterAY !== 'all') ? $filterAY : null, false, ($filterType !== 'all') ? $filterType : null);

// Compute Key Metrics
$totalSchedules = count($fees);
$distinctProgramsCount = count(array_unique(array_column($fees, 'course_id')));
$avgAnnualFee = $totalSchedules > 0 ? round(array_sum(array_column($fees, 'total_fee')) / $totalSchedules, 2) : 0;
$maxAnnualFee = $totalSchedules > 0 ? max(array_column($fees, 'total_fee')) : 0;

$page_title = "Year-Wise Fee Structures Manager";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">
                <i class="fa-solid fa-indian-rupee-sign me-1"></i> Financials &amp; Tuition
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">
                <i class="fa-solid fa-calendar-check me-1"></i> Active Website AY: <?php echo htmlspecialchars($activeAY); ?>
            </span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Year-Wise Fee Structures Manager</h2>
        <p class="text-muted small mb-0">Manage program-wise, academic-year-wise, and study-year tuition, development, examination, and applicable fees.</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 font-semibold shadow-xs" data-bs-toggle="modal" data-bs-target="#activeAYModal">
                <i class="fa-solid fa-gear me-1"></i> Change Active AY
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 font-semibold shadow-xs" data-bs-toggle="modal" data-bs-target="#cloneAYModal">
                <i class="fa-solid fa-clone me-1"></i> Clone AY Schedule
            </button>
            <a href="fees.php?action=export_csv&filter_ay=<?php echo urlencode($filterAY); ?>&filter_type=<?php echo urlencode($filterType); ?>&filter_course=<?php echo $filterCourse; ?>" class="btn btn-sm btn-success rounded-pill px-3 font-semibold shadow-xs">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="fees.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3.5 font-semibold shadow-xs">
                <i class="fa-solid fa-plus me-1 text-warning"></i> Add Fee Structure
            </a>
        </div>
    <?php else: ?>
        <a href="fees.php?filter_ay=<?php echo urlencode($filterAY); ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Schedule List
        </a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-semibold d-block">Configured Schedules</small>
                        <h3 class="fw-bold font-display text-dark mb-0"><?php echo $totalSchedules; ?></h3>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2.5 text-primary">
                        <i class="fa-solid fa-receipt fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small">Across <?php echo $distinctProgramsCount; ?> Programs</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-semibold d-block">Active Academic Year</small>
                        <h3 class="fw-bold font-display text-success mb-0"><?php echo htmlspecialchars($activeAY); ?></h3>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-2.5 text-success">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small">Live on Website Portal</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-info border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-semibold d-block">Average Annual Fee</small>
                        <h3 class="fw-bold font-display text-info mb-0">₹<?php echo number_format($avgAnnualFee); ?></h3>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 p-2.5 text-info">
                        <i class="fa-solid fa-calculator fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small">Per Year / Student</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-semibold d-block">Highest Annual Fee</small>
                        <h3 class="fw-bold font-display text-warning mb-0">₹<?php echo number_format($maxAnnualFee); ?></h3>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-2.5 text-warning">
                        <i class="fa-solid fa-award fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small">CSE IoT &amp; Cyber Security</div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="fees.php" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="small text-muted font-semibold mb-1 d-block"><i class="fa-solid fa-calendar me-1"></i>Academic Year</label>
                    <select name="filter_ay" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                        <option value="all" <?php echo $filterAY === 'all' ? 'selected' : ''; ?>>All Academic Years</option>
                        <?php foreach ($distinctAYs as $ay): ?>
                            <option value="<?php echo htmlspecialchars($ay); ?>" <?php echo $filterAY === $ay ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ay); ?> <?php echo ($ay === $activeAY) ? '(Active Default)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted font-semibold mb-1 d-block"><i class="fa-solid fa-layer-group me-1"></i>Program Type</label>
                    <select name="filter_type" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                        <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Program Types</option>
                        <option value="regular" <?php echo $filterType === 'regular' ? 'selected' : ''; ?>>Regular 4-Year B.Tech</option>
                        <option value="integrated" <?php echo $filterType === 'integrated' ? 'selected' : ''; ?>>Integrated 6-Year B.Tech</option>
                        <option value="lateral_entry" <?php echo $filterType === 'lateral_entry' ? 'selected' : ''; ?>>Direct 2nd Year (Lateral)</option>
                        <option value="diploma" <?php echo $filterType === 'diploma' ? 'selected' : ''; ?>>Diploma Programs</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small text-muted font-semibold mb-1 d-block"><i class="fa-solid fa-graduation-cap me-1"></i>Filter by Course</label>
                    <select name="filter_course" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                        <option value="0">All Degree Programs (<?php echo count($courses); ?>)</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $filterCourse == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="fees.php" class="btn btn-outline-secondary btn-sm rounded-pill w-100 mt-md-4">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Fee Schedules Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <div>
                <h5 class="font-display fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-table-list text-warning me-2"></i>Configured Year-Wise Schedules (<?php echo count($fees); ?>)
                </h5>
                <small class="text-muted">Displaying tuition, development, and total annual fees organized by study year.</small>
            </div>
            <input type="text" id="feeSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Quick search program, code, year..." style="width: 260px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="feesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Degree Program / Department</th>
                            <th class="text-center">Academic Year</th>
                            <th class="text-center">Study Year</th>
                            <th>Fee Components Breakdown</th>
                            <th class="text-end">Total Annual Fee</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fees)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted p-5">
                                    <i class="fa-solid fa-folder-open text-muted fs-2 d-block mb-2"></i>
                                    No fee structures match the selected filter criteria.
                                    <div class="mt-2">
                                        <a href="fees.php?action=add" class="btn btn-sm btn-primary rounded-pill px-3">Add Fee Structure</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fees as $fee): 
                                $pTypeBadge = match(strtolower($fee['program_type'])) {
                                    'integrated' => 'bg-info-subtle text-info border-info-subtle',
                                    'lateral_entry' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                    'diploma' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                    default => 'bg-primary-subtle text-primary border-primary-subtle'
                                };
                                $pTypeLabel = match(strtolower($fee['program_type'])) {
                                    'integrated' => '6-Yr Integrated',
                                    'lateral_entry' => 'Direct 2nd Yr (Lateral)',
                                    'diploma' => 'Diploma',
                                    default => 'Regular 4-Yr B.Tech'
                                };
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold font-display text-dark" style="font-size: 14.5px;">
                                            <?php echo htmlspecialchars($fee['course_name']); ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                                            <span class="badge <?php echo $pTypeBadge; ?> border rounded-pill" style="font-size: 14px;">
                                                <?php echo $pTypeLabel; ?>
                                            </span>
                                            <span class="badge bg-light text-dark border rounded-pill" style="font-size: 14px;">
                                                <?php echo htmlspecialchars($fee['course_code']); ?>
                                            </span>
                                            <span class="text-muted small" style="font-size: 14px;">
                                                <i class="fa-solid fa-building-columns me-1"></i><?php echo htmlspecialchars($fee['department_name']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill font-semibold">
                                            <i class="fa-solid fa-calendar-days text-primary me-1"></i><?php echo htmlspecialchars($fee['academic_year']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-1 font-display fw-bold" style="font-size: 14px;">
                                            <?php echo htmlspecialchars($fee['year_name']); ?>
                                        </span>
                                        <small class="d-block text-muted mt-0.5" style="font-size: 14px;"><?php echo htmlspecialchars($fee['semesters_included']); ?></small>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="d-flex justify-content-between text-muted mb-0.5" style="max-width: 280px;">
                                                <span>• Tuition Fee:</span>
                                                <span class="fw-semibold text-dark">₹<?php echo number_format($fee['tuition_fee'], 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between text-muted mb-0.5" style="max-width: 280px;">
                                                <span>• Development Fee:</span>
                                                <span class="fw-semibold text-dark">₹<?php echo number_format($fee['development_fee'], 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between text-muted mb-0.5" style="max-width: 280px;">
                                                <span>• Exam &amp; Other Charges:</span>
                                                <span class="fw-semibold text-dark">₹<?php echo number_format($fee['exam_fee'] + $fee['other_fee'], 2); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-extrabold text-success font-display fs-6">
                                            ₹<?php echo number_format($fee['total_fee'], 2); ?>
                                        </div>
                                        <small class="text-muted d-block" style="font-size: 14px;">per annum</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="fees.php?action=toggle_status&id=<?php echo $fee['id']; ?>" class="text-decoration-none" title="Click to toggle status">
                                            <?php if ($fee['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                    <i class="fa-solid fa-circle-check me-1"></i>Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1">
                                                    <i class="fa-solid fa-box-archive me-1"></i>Archived
                                                </span>
                                            <?php endif; ?>
                                        </a>
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        <a href="fees.php?action=edit&id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1" title="Edit">
                                            <i class="fa-solid fa-pen me-1"></i> Edit
                                        </a>
                                        <a href="fees.php?action=delete&id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 font-semibold" title="Delete" onclick="return confirm('Delete this year-wise fee schedule?');">
                                            <i class="fa-solid fa-trash me-1"></i> Delete
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

    <!-- Modal: Change Active Academic Year -->
    <div class="modal fade" id="activeAYModal" tabindex="-1" aria-labelledby="activeAYModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="fees.php?action=set_active_ay">
                    <?php echo Security::csrfField(); ?>
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title font-display fw-bold" id="activeAYModalLabel">
                            <i class="fa-solid fa-calendar-check text-primary me-2"></i>Set Active Academic Year
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">The Active Academic Year determines which fee schedules are displayed to prospective students on the public website and admissions portal.</p>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Active Academic Year *</label>
                            <input type="text" name="active_academic_year" class="form-control" value="<?php echo htmlspecialchars($activeAY); ?>" placeholder="e.g. 2026-2027" required>
                            <small class="text-muted">Currently active: <strong><?php echo htmlspecialchars($activeAY); ?></strong></small>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2.5">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 font-semibold">Save Active AY</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Clone / Duplicate Academic Year Schedules -->
    <div class="modal fade" id="cloneAYModal" tabindex="-1" aria-labelledby="cloneAYModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="fees.php?action=clone_academic_year">
                    <?php echo Security::csrfField(); ?>
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title font-display fw-bold" id="cloneAYModalLabel">
                            <i class="fa-solid fa-clone text-success me-2"></i>Clone Academic Year Fee Schedule
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">Copy all existing program fee schedules from a source academic year to create a new academic year schedule without re-typing every program.</p>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Source Academic Year *</label>
                            <select name="source_ay" class="form-select" required>
                                <?php foreach ($distinctAYs as $ay): ?>
                                    <option value="<?php echo htmlspecialchars($ay); ?>" <?php echo $ay === $activeAY ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ay); ?> (<?php echo $ay === $activeAY ? 'Active' : 'Historical'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Target Academic Year *</label>
                            <input type="text" name="target_ay" class="form-control" placeholder="e.g. 2027-2028" required>
                            <small class="text-muted">Enter the new target academic year format (e.g. 2027-2028).</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2.5">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 font-semibold">Clone Schedules</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('feeSearchInput');
        const table = document.getElementById('feesTable');
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

    <!-- Add / Edit Year-Wise Fee Structure Form -->
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <div>
                        <h4 class="font-display fw-bold text-dark mb-1">
                            <i class="fa-solid fa-receipt text-warning me-2"></i><?php echo $edit_fee ? 'Edit Year-Wise Fee Structure' : 'Configure Year-Wise Fee Schedule'; ?>
                        </h4>
                        <p class="text-muted small mb-0">Associate tuition, development, and semester examination fee breakdown for a specific academic study year.</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 font-semibold">
                        <?php echo $edit_fee ? 'ID #' . $edit_fee['id'] : 'New Fee Configuration'; ?>
                    </span>
                </div>

                <form method="POST" action="fees.php" id="feeForm">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_fee): ?>
                        <input type="hidden" name="fee_id" value="<?php echo $edit_fee['id']; ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <!-- Degree Program -->
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Degree Program *</label>
                            <select name="course_id" id="courseSelect" class="form-select" required onchange="handleCourseChange()">
                                <option value="">Select Degree Program...</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" 
                                            data-type="<?php echo htmlspecialchars($c['program_type']); ?>" 
                                            data-duration="<?php echo $c['duration_years']; ?>"
                                            data-fee1="<?php echo $c['fee_year_1']; ?>"
                                            <?php echo ($edit_fee && $edit_fee['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['code']); ?>) — <?php echo $c['duration_years']; ?> Yrs
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Academic Year -->
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Academic Year *</label>
                            <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($edit_fee['academic_year'] ?? $filterAY); ?>" placeholder="e.g. 2026-2027" required>
                        </div>

                        <!-- Category -->
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Admission Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="General / Open" <?php echo ($edit_fee && $edit_fee['category'] === 'General / Open') ? 'selected' : ''; ?>>General / Open</option>
                                <option value="OBC / EWS" <?php echo ($edit_fee && $edit_fee['category'] === 'OBC / EWS') ? 'selected' : ''; ?>>OBC / EWS</option>
                                <option value="SC / ST / VJNT" <?php echo ($edit_fee && $edit_fee['category'] === 'SC / ST / VJNT') ? 'selected' : ''; ?>>SC / ST / VJNT</option>
                                <option value="TFWS / Tuition Fee Waiver" <?php echo ($edit_fee && $edit_fee['category'] === 'TFWS / Tuition Fee Waiver') ? 'selected' : ''; ?>>TFWS / Tuition Fee Waiver</option>
                                <option value="Institutional / NRI" <?php echo ($edit_fee && $edit_fee['category'] === 'Institutional / NRI') ? 'selected' : ''; ?>>Institutional / NRI</option>
                            </select>
                        </div>

                        <!-- Study Year -->
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Study Year Level *</label>
                            <select name="study_year" id="studyYearSelect" class="form-select" required onchange="updateYearTitleAndSemesters()">
                                <option value="1" <?php echo ($edit_fee && $edit_fee['study_year'] == 1) ? 'selected' : ''; ?>>1st Year (Sem 1 &amp; 2)</option>
                                <option value="2" <?php echo ($edit_fee && $edit_fee['study_year'] == 2) ? 'selected' : ''; ?>>2nd Year (Sem 3 &amp; 4)</option>
                                <option value="3" <?php echo ($edit_fee && $edit_fee['study_year'] == 3) ? 'selected' : ''; ?>>3rd Year (Sem 5 &amp; 6)</option>
                                <option value="4" <?php echo ($edit_fee && $edit_fee['study_year'] == 4) ? 'selected' : ''; ?>>4th Year (Sem 7 &amp; 8)</option>
                                <option value="5" <?php echo ($edit_fee && $edit_fee['study_year'] == 5) ? 'selected' : ''; ?>>5th Year (Sem 9 &amp; 10 - Integrated)</option>
                                <option value="6" <?php echo ($edit_fee && $edit_fee['study_year'] == 6) ? 'selected' : ''; ?>>6th Year (Sem 11 &amp; 12 - Integrated)</option>
                            </select>
                        </div>

                        <!-- Year Title Label -->
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Year Title Display *</label>
                            <input type="text" name="year_name" id="yearNameInput" class="form-control" value="<?php echo htmlspecialchars($edit_fee['year_name'] ?? '1st Year'); ?>" placeholder="e.g. 1st Year or 2nd Year (Direct Entry)" required>
                        </div>

                        <!-- Semesters Included -->
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Semesters Covered *</label>
                            <input type="text" name="semesters_included" id="semestersIncludedInput" class="form-control" value="<?php echo htmlspecialchars($edit_fee['semesters_included'] ?? 'Semester 1 & Semester 2'); ?>" placeholder="e.g. Semester 1 & Semester 2" required>
                        </div>

                        <!-- Component Breakdown Section Header -->
                        <div class="col-12 mt-4">
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="font-display fw-bold text-dark mb-1"><i class="fa-solid fa-calculator text-primary me-2"></i>Yearly Fee Components Breakdown (INR ₹)</h6>
                                <p class="text-muted small mb-0">Enter individual breakdown items. The Annual Total will calculate automatically in real-time.</p>
                            </div>
                        </div>

                        <!-- Tuition Fee -->
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Tuition Fee (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="tuition_fee" id="tuitionFee" class="form-control fee-input fw-bold" value="<?php echo $edit_fee ? $edit_fee['tuition_fee'] : '144000.00'; ?>" required oninput="calculateTotal()">
                            </div>
                        </div>

                        <!-- Development Fee -->
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Development Fee (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="development_fee" id="developmentFee" class="form-control fee-input fw-bold" value="<?php echo $edit_fee ? $edit_fee['development_fee'] : '14400.00'; ?>" required oninput="calculateTotal()">
                            </div>
                        </div>

                        <!-- Examination Fee -->
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Semester Examination Fee (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="exam_fee" id="examFee" class="form-control fee-input fw-bold" value="<?php echo $edit_fee ? $edit_fee['exam_fee'] : '5000.00'; ?>" required oninput="calculateTotal()">
                            </div>
                            <small class="text-muted">Standard MGM University exam fee: ₹2,500 x 2 Semesters = ₹5,000</small>
                        </div>

                        <!-- Other Applicable Fees -->
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Student Activities, Gymkhana, Library &amp; Welfare Fund (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="other_fee" id="otherFee" class="form-control fee-input fw-bold" value="<?php echo $edit_fee ? $edit_fee['other_fee'] : '2200.00'; ?>" required oninput="calculateTotal()">
                            </div>
                        </div>

                        <!-- Total Computed Fee -->
                        <div class="col-12 mt-3">
                            <div class="p-3.5 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-success text-white rounded-pill px-2.5 py-1 mb-1">Auto Calculated</span>
                                    <h5 class="font-display fw-bold text-dark mb-0">Total Annual Fee for this Study Year:</h5>
                                </div>
                                <div class="text-end">
                                    <input type="hidden" name="total_fee" id="totalFeeHidden" value="<?php echo $edit_fee ? $edit_fee['total_fee'] : '165600.00'; ?>">
                                    <span class="fs-3 fw-extrabold text-success font-display" id="totalFeeDisplay">₹<?php echo number_format($edit_fee ? $edit_fee['total_fee'] : 165600, 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks & Status -->
                        <div class="col-md-8">
                            <label class="form-label font-semibold">Administrative Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" value="<?php echo htmlspecialchars($edit_fee['remarks'] ?? 'Official SOET MGM University approved schedule.'); ?>" placeholder="e.g. Official fee revision circular ref...">
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check form-switch mt-md-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" <?php echo (!$edit_fee || $edit_fee['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActiveSwitch">Publish &amp; Make Active</label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                            <a href="fees.php?filter_ay=<?php echo urlencode($filterAY); ?>" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                            <button type="submit" name="save_fee" class="btn btn-primary rounded-pill px-5 font-semibold shadow-xs">
                                <i class="fa-solid fa-save me-1"></i> <?php echo $edit_fee ? 'Update Fee Schedule' : 'Save Fee Schedule'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function calculateTotal() {
        const tuition = parseFloat(document.getElementById('tuitionFee').value) || 0;
        const dev = parseFloat(document.getElementById('developmentFee').value) || 0;
        const exam = parseFloat(document.getElementById('examFee').value) || 0;
        const other = parseFloat(document.getElementById('otherFee').value) || 0;
        
        const total = tuition + dev + exam + other;
        document.getElementById('totalFeeHidden').value = total.toFixed(2);
        document.getElementById('totalFeeDisplay').innerText = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function updateYearTitleAndSemesters() {
        const year = parseInt(document.getElementById('studyYearSelect').value) || 1;
        const courseSelect = document.getElementById('courseSelect');
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        const pType = selectedOption ? selectedOption.getAttribute('data-type') : '';

        const yearLabels = {
            1: '1st Year',
            2: (pType === 'lateral_entry') ? '2nd Year (Direct Entry)' : '2nd Year',
            3: '3rd Year',
            4: '4th Year',
            5: '5th Year',
            6: '6th Year'
        };

        const sem1 = (year * 2) - 1;
        const sem2 = year * 2;

        document.getElementById('yearNameInput').value = yearLabels[year] || (year + 'th Year');
        document.getElementById('semestersIncludedInput').value = `Semester ${sem1} & Semester ${sem2}`;
    }

    function handleCourseChange() {
        const courseSelect = document.getElementById('courseSelect');
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const duration = parseInt(selectedOption.getAttribute('data-duration')) || 4;
        const pType = selectedOption.getAttribute('data-type') || '';
        const fee1 = parseFloat(selectedOption.getAttribute('data-fee1')) || 115600;

        // Auto calculate reasonable standard components
        const tuition = Math.round(fee1 * 0.8695);
        const dev = Math.round(fee1 * 0.087);
        const exam = 5000;
        const other = Math.round(fee1 - (tuition + dev + exam));

        document.getElementById('tuitionFee').value = tuition.toFixed(2);
        document.getElementById('developmentFee').value = dev.toFixed(2);
        document.getElementById('examFee').value = exam.toFixed(2);
        document.getElementById('otherFee').value = other.toFixed(2);

        updateYearTitleAndSemesters();
        calculateTotal();
    }
    </script>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
