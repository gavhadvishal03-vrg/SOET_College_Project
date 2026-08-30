<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_courses');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_course = null;
$depts = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT c.name, c.code, d.name as department_name, c.duration_years, c.semester_count, 
                c.intake_capacity, c.fee_year_1, c.fee_year_2, c.fee_year_3, c.fee_year_4, c.is_active 
         FROM courses c JOIN departments d ON c.department_id = d.id 
         ORDER BY d.name, c.name"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Courses_Fees_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Course Name', 'Code', 'Department', 'Duration (Yrs)', 'Semesters', 'Intake Capacity', 'Fee Year 1 (INR)', 'Fee Year 2 (INR)', 'Fee Year 3 (INR)', 'Fee Year 4 (INR)', 'Total Fee (INR)', 'Status']);
    foreach ($rows as $r) {
        $total = $r['fee_year_1'] + $r['fee_year_2'] + $r['fee_year_3'] + $r['fee_year_4'];
        fputcsv($output, [
            $r['name'],
            $r['code'],
            $r['department_name'],
            $r['duration_years'],
            $r['semester_count'],
            $r['intake_capacity'],
            $r['fee_year_1'],
            $r['fee_year_2'],
            $r['fee_year_3'],
            $r['fee_year_4'],
            $total,
            $r['is_active'] ? 'Active' : 'Inactive'
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Courses & Fees Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_course = $db->fetchOne("SELECT * FROM courses WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : null;
    $dept_id = (int)$_POST['department_id'];
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));
    $description = trim($_POST['description']);
    $duration = (int)$_POST['duration_years'];
    $semesters = (int)$_POST['semester_count'];
    $capacity = (int)$_POST['intake_capacity'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Year-Wise Fees
    $fee_year_1 = (float)($_POST['fee_year_1'] ?? 150000.00);
    $fee_year_2 = (float)($_POST['fee_year_2'] ?? 150000.00);
    $fee_year_3 = (float)($_POST['fee_year_3'] ?? 150000.00);
    $fee_year_4 = (float)($_POST['fee_year_4'] ?? 150000.00);

    $image_path = $edit_course ? $edit_course['image_path'] : null;
    $syl_pdf_1 = $edit_course ? ($edit_course['syllabus_pdf_year_1'] ?? null) : null;
    $syl_pdf_2 = $edit_course ? ($edit_course['syllabus_pdf_year_2'] ?? null) : null;
    $syl_pdf_3 = $edit_course ? ($edit_course['syllabus_pdf_year_3'] ?? null) : null;
    $syl_pdf_4 = $edit_course ? ($edit_course['syllabus_pdf_year_4'] ?? null) : null;

    // Upload Course Cover Image
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === UPLOAD_ERR_OK) {
        $file_errors = Security::validateUpload($_FILES['course_image'], ALLOWED_IMAGE_TYPES);
        if (empty($file_errors)) {
            $uploaded = Security::uploadFile($_FILES['course_image'], 'courses', 'course_');
            if ($uploaded) {
                $image_path = $uploaded;
            }
        } else {
            setFlash('danger', implode(' ', $file_errors));
        }
    }

    // Upload Year-Wise Syllabus PDFs
    $pdfFields = [
        'syllabus_pdf_year_1' => &$syl_pdf_1,
        'syllabus_pdf_year_2' => &$syl_pdf_2,
        'syllabus_pdf_year_3' => &$syl_pdf_3,
        'syllabus_pdf_year_4' => &$syl_pdf_4
    ];

    foreach ($pdfFields as $inputName => &$varRef) {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
            $pdf_errors = Security::validateUpload($_FILES[$inputName], ALLOWED_DOC_TYPES);
            if (empty($pdf_errors)) {
                $uploadedPdf = Security::uploadFile($_FILES[$inputName], 'courses', "syl_{$inputName}_");
                if ($uploadedPdf) {
                    $varRef = $uploadedPdf;
                }
            } else {
                setFlash('danger', implode(' ', $pdf_errors));
            }
        }
    }

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($name) || empty($code) || empty($dept_id) || empty($duration)) {
        setFlash('danger', 'Please enter all mandatory fields.');
    } else {
        $data = [
            'department_id' => $dept_id,
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'duration_years' => $duration,
            'semester_count' => $semesters,
            'intake_capacity' => $capacity,
            'fee_year_1' => $fee_year_1,
            'fee_year_2' => $fee_year_2,
            'fee_year_3' => $fee_year_3,
            'fee_year_4' => $fee_year_4,
            'image_path' => $image_path,
            'syllabus_pdf_year_1' => $syl_pdf_1,
            'syllabus_pdf_year_2' => $syl_pdf_2,
            'syllabus_pdf_year_3' => $syl_pdf_3,
            'syllabus_pdf_year_4' => $syl_pdf_4,
            'is_active' => $is_active
        ];

        if ($course_id) {
            $db->update('courses', $data, 'id = ?', [$course_id]);
            setFlash('success', 'Course details, year-wise fees, and syllabus PDFs updated successfully.');
        } else {
            $db->insert('courses', $data);
            setFlash('success', 'New course registered with complete curriculum and fees structure.');
        }
        redirect('courses.php');
    }
}

// Handle Delete Course
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    try {
        $db->delete('courses', 'id = ?', [$target_id]);
        setFlash('success', 'Course deleted successfully.');
    } catch (PDOException $e) {
        setFlash('danger', 'Cannot delete course: it is linked to admissions or placements.');
    }
    redirect('courses.php');
}

// Handle Delete Specific Syllabus PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_syllabus_pdf'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $course_id = (int)$_POST['course_id'];
    $year = (int)$_POST['year'];
    $field = "syllabus_pdf_year_" . $year;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $db->update('courses', [$field => null], 'id = ?', [$course_id]);
        setFlash('success', "Year {$year} Syllabus PDF file unlinked and deleted successfully.");
        redirect("courses.php?action=edit&id={$course_id}");
    }
}

// Fetch all courses
$courses = $db->fetchAll(
    "SELECT c.*, d.name as department_name 
     FROM courses c JOIN departments d ON c.department_id = d.id 
     ORDER BY d.name, c.name"
);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Courses, Year-Wise Fees &amp; Syllabus Manager</h1>
        <small class="text-muted">Manage degree tracks, four-year tuition structure, intake quotas, and academic syllabus documents</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="courses.php?action=export_csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="courses.php?action=add" class="btn btn-primary btn-sm font-semibold"><i class="fa-solid fa-plus me-1"></i> Register New Course</a>
        </div>
    <?php else: ?>
        <a href="courses.php" class="btn btn-outline-secondary btn-sm font-semibold"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-book-open text-warning me-2"></i>Registered Degree Programs (<?php echo count($courses); ?>)</h5>
            <input type="text" id="courseSearchInput" class="form-control form-control-sm" placeholder="Search course name, code..." style="width: 240px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="coursesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Cover</th>
                            <th>Course Name &amp; Code</th>
                            <th>Department</th>
                            <th>Year-Wise Fees Breakdown</th>
                            <th>Syllabus PDFs</th>
                            <th class="text-center">Capacity</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <?php $totalFee = $c['fee_year_1'] + $c['fee_year_2'] + $c['fee_year_3'] + $c['fee_year_4']; ?>
                            <tr>
                                <td>
                                    <?php if (!empty($c['image_path'])): ?>
                                        <img src="<?php echo uploadUrl('courses', $c['image_path']); ?>" alt="Cover" class="rounded img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded text-muted d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px; font-size: 10px;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary-color"><?php echo htmlspecialchars($c['name']); ?></div>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($c['code']); ?></span>
                                    <small class="text-muted opacity-75 ms-1"><?php echo $c['duration_years']; ?> Years (<?php echo $c['semester_count']; ?> Semesters)</small>
                                </td>
                                <td><span class="text-muted small fw-semibold"><?php echo htmlspecialchars($c['department_name']); ?></span></td>
                                <td>
                                    <div class="small text-nowrap">
                                        <span class="d-block">Y1: ₹<?php echo number_format($c['fee_year_1']); ?> | Y2: ₹<?php echo number_format($c['fee_year_2']); ?></span>
                                        <span class="d-block">Y3: ₹<?php echo number_format($c['fee_year_3']); ?> | Y4: ₹<?php echo number_format($c['fee_year_4']); ?></span>
                                        <strong class="text-success">Total: ₹<?php echo number_format($totalFee); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php for ($y = 1; $y <= 4; $y++): ?>
                                            <?php $pdfKey = "syllabus_pdf_year_{$y}"; ?>
                                            <?php if (!empty($c[$pdfKey])): ?>
                                                <a href="<?php echo uploadUrl('courses', $c[$pdfKey]); ?>" target="_blank" class="btn btn-outline-danger btn-xs py-0 px-1 font-semibold" title="Year <?php echo $y; ?> Syllabus PDF">
                                                    <i class="fa-solid fa-file-pdf me-1"></i>Y<?php echo $y; ?> PDF
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border" style="font-size: 10px;">Y<?php echo $y; ?>: None</span>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td class="text-center fw-bold"><?php echo $c['intake_capacity']; ?> Seats</td>
                                <td class="text-center"><?php echo statusBadge($c['is_active'] ? 'active' : 'inactive'); ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="courses.php?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary font-semibold me-1"><i class="fa-solid fa-pen-to-square me-1"></i> Edit</a>
                                    <a href="courses.php?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm font-semibold" onclick="return confirm('Delete this course?');"><i class="fa-solid fa-trash-can me-1"></i> Delete</a>
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
        const searchInput = document.getElementById('courseSearchInput');
        const table = document.getElementById('coursesTable');
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
    <!-- Add / Edit Course Form -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-graduation-cap text-warning me-2"></i><?php echo $edit_course ? 'Edit Course, Year-Wise Fees & Syllabus' : 'Register New Course & Year-Wise Structure'; ?></h4>
                <form method="POST" action="courses.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_course): ?>
                        <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Academic Department *</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department...</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ($edit_course && $edit_course['department_id'] == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Course Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_course['name'] ?? ''); ?>" placeholder="B.Tech in Artificial Intelligence & Data Science">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold">Course Code *</label>
                            <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($edit_course['code'] ?? ''); ?>" placeholder="BTECH-AIDS">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Duration (Years) *</label>
                            <input type="number" name="duration_years" class="form-control" required value="<?php echo htmlspecialchars($edit_course['duration_years'] ?? '4'); ?>" min="1" max="6">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Semester Count *</label>
                            <input type="number" name="semester_count" class="form-control" required value="<?php echo htmlspecialchars($edit_course['semester_count'] ?? '8'); ?>" min="1" max="12">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold">Approved Intake Capacity (Seats) *</label>
                            <input type="number" name="intake_capacity" class="form-control" required value="<?php echo htmlspecialchars($edit_course['intake_capacity'] ?? '60'); ?>" min="1">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label font-semibold">Upload Course Cover Image</label>
                            <input type="file" name="course_image" class="form-control" accept="image/*">
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Course Description &amp; Highlights</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief outline of curriculum focus, lab infrastructure, and career prospects..."><?php echo htmlspecialchars($edit_course['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Year-Wise Fees Section -->
                        <div class="col-12 mt-4">
                            <div class="p-3 bg-light rounded border">
                                <h5 class="fw-bold text-primary-color mb-3"><i class="fa-solid fa-money-bill-wave text-warning me-2"></i>Year-Wise Tuition &amp; Academic Fees (INR)</h5>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label font-semibold small">Year 1 Fee (₹)</label>
                                        <input type="number" step="0.01" name="fee_year_1" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($edit_course['fee_year_1'] ?? '150000.00'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label font-semibold small">Year 2 Fee (₹)</label>
                                        <input type="number" step="0.01" name="fee_year_2" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($edit_course['fee_year_2'] ?? '150000.00'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label font-semibold small">Year 3 Fee (₹)</label>
                                        <input type="number" step="0.01" name="fee_year_3" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($edit_course['fee_year_3'] ?? '150000.00'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label font-semibold small">Year 4 Fee (₹)</label>
                                        <input type="number" step="0.01" name="fee_year_4" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($edit_course['fee_year_4'] ?? '150000.00'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Year-Wise Syllabus PDFs Section -->
                        <div class="col-12 mt-4">
                            <div class="p-3 bg-light rounded border">
                                <h5 class="fw-bold text-primary-color mb-3"><i class="fa-solid fa-file-pdf text-danger me-2"></i>Year-Wise Syllabus Curriculum PDFs</h5>
                                <div class="row g-3">
                                    <?php for ($y = 1; $y <= 4; $y++): ?>
                                        <?php 
                                            $pdfField = "syllabus_pdf_year_{$y}"; 
                                            $currentPdf = $edit_course ? ($edit_course[$pdfField] ?? null) : null;
                                        ?>
                                        <div class="col-md-6">
                                            <div class="border p-2.5 rounded bg-white shadow-xs">
                                                <label class="form-label font-semibold small mb-1">Year <?php echo $y; ?> Syllabus Document</label>
                                                <?php if (!empty($currentPdf)): ?>
                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                        <a href="<?php echo uploadUrl('courses', $currentPdf); ?>" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2">
                                                            <i class="fa-solid fa-file-pdf me-1"></i> View PDF
                                                        </a>
                                                        <button type="submit" form="deletePdfForm<?php echo $y; ?>" class="btn btn-outline-danger btn-xs py-0.5 px-2 font-semibold">
                                                            <i class="fa-solid fa-trash me-1"></i> Delete
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                                <input type="file" name="<?php echo $pdfField; ?>" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_course || $edit_course['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActive">Course Active for Admissions &amp; Public Display</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="courses.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_course" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Course &amp; Curriculum</button>
                        </div>
                    </div>
                </form>

                <!-- Hidden Delete PDF Forms -->
                <?php if ($edit_course): ?>
                    <?php for ($y = 1; $y <= 4; $y++): ?>
                        <form id="deletePdfForm<?php echo $y; ?>" method="POST" action="courses.php" onsubmit="return confirm('Delete this Year <?php echo $y; ?> Syllabus file?');">
                            <?php echo Security::csrfField(); ?>
                            <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                            <input type="hidden" name="year" value="<?php echo $y; ?>">
                        </form>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('courseSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            var rows = document.querySelectorAll('#coursesTable tbody tr');
            rows.forEach(function(row) {
                var text = row.innerText.toLowerCase();
                if (text.includes(q)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
