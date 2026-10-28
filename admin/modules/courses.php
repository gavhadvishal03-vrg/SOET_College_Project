<?php
/**
 * Academic Programs & Degree Courses Management Module
 *
 * Full CRUD administrative interface for academic offerings:
 * - Course creation, updating, sort ordering, and activation status
 * - Program type categorization (UG 4-Year, Integrated 6-Year, DSE, Diploma)
 * - Intake capacities, duration years, semester counts, and 4-year tuition fee breakdowns
 * - Syllabus PDF and curriculum booklet file attachments
 * - Real-time filled seats calculation and program CSV roster export
 *
 * Required Permission: 'manage_courses'
 */
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

    $existing_course = $course_id ? $db->fetchOne("SELECT * FROM courses WHERE id = ?", [$course_id]) : null;
    $image_path = $existing_course ? $existing_course['image_path'] : null;
    $syl_pdf_1 = $existing_course ? ($existing_course['syllabus_pdf_year_1'] ?? null) : null;
    $syl_pdf_2 = $existing_course ? ($existing_course['syllabus_pdf_year_2'] ?? null) : null;
    $syl_pdf_3 = $existing_course ? ($existing_course['syllabus_pdf_year_3'] ?? null) : null;
    $syl_pdf_4 = $existing_course ? ($existing_course['syllabus_pdf_year_4'] ?? null) : null;

    $upload_errors = [];

    // Upload Course Cover Image
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['course_image']['error'] === UPLOAD_ERR_OK) {
            $file_errors = Security::validateUpload($_FILES['course_image'], ALLOWED_IMAGE_TYPES);
            if (empty($file_errors)) {
                $uploaded = Security::uploadFile($_FILES['course_image'], 'courses', 'course_');
                if ($uploaded) {
                    $image_path = $uploaded;
                } else {
                    $upload_errors[] = 'Failed to save course cover image.';
                }
            } else {
                $upload_errors[] = 'Course Image: ' . implode(' ', $file_errors);
            }
        } else {
            $upload_errors[] = 'Course Image upload error (code ' . (int)$_FILES['course_image']['error'] . ').';
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
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] !== UPLOAD_ERR_NO_FILE) {
            $yrNum = substr($inputName, -1);
            if ($_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $pdf_errors = Security::validateUpload($_FILES[$inputName], ALLOWED_DOC_TYPES);
                if (empty($pdf_errors)) {
                    $uploadedPdf = Security::uploadFile($_FILES[$inputName], 'courses', "syl_{$inputName}_");
                    if ($uploadedPdf) {
                        $varRef = $uploadedPdf;
                    } else {
                        $upload_errors[] = "Failed to save Year {$yrNum} Syllabus PDF.";
                    }
                } else {
                    $upload_errors[] = "Year {$yrNum} Syllabus PDF: " . implode(' ', $pdf_errors);
                }
            } else {
                $upload_errors[] = "Year {$yrNum} Syllabus PDF upload failed (file may exceed maximum upload size).";
            }
        }
    }
    unset($varRef);

    $program_type = trim($_POST['program_type'] ?? 'Regular');
    $brochure_doc = trim($_POST['brochure_document'] ?? ($existing_course['brochure_document'] ?? ''));
    $curriculum_overview = trim($_POST['curriculum_overview'] ?? '');
    $core_courses = trim($_POST['core_courses'] ?? ($existing_course['core_courses'] ?? ''));
    $career_opportunities = trim($_POST['career_opportunities'] ?? ($existing_course['career_opportunities'] ?? ''));
    $eligibility = trim($_POST['eligibility'] ?? ($existing_course['eligibility'] ?? ''));
    $admission_info = trim($_POST['admission_info'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // Upload Course Booklet Document
    if (isset($_FILES['brochure_file']) && $_FILES['brochure_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['brochure_file']['error'] === UPLOAD_ERR_OK) {
            $doc_errors = Security::validateUpload($_FILES['brochure_file'], ALLOWED_DOC_TYPES);
            if (empty($doc_errors)) {
                $uploadedDoc = Security::uploadFile($_FILES['brochure_file'], 'documents', 'booklet_');
                if ($uploadedDoc) {
                    $brochure_doc = 'uploads/documents/' . $uploadedDoc;
                } else {
                    $upload_errors[] = 'Failed to save Official Curriculum Booklet PDF.';
                }
            } else {
                $upload_errors[] = 'Curriculum Booklet PDF: ' . implode(' ', $doc_errors);
            }
        } else {
            $upload_errors[] = 'Curriculum Booklet PDF upload failed (file may exceed maximum upload size).';
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
            'program_type' => $program_type,
            'description' => $description,
            'curriculum_overview' => $curriculum_overview,
            'core_courses' => $core_courses,
            'career_opportunities' => $career_opportunities,
            'eligibility' => $eligibility,
            'admission_info' => $admission_info,
            'duration_years' => $duration,
            'semester_count' => $semesters,
            'intake_capacity' => $capacity,
            'fee_year_1' => $fee_year_1,
            'fee_year_2' => $fee_year_2,
            'fee_year_3' => $fee_year_3,
            'fee_year_4' => $fee_year_4,
            'image_path' => $image_path,
            'brochure_document' => $brochure_doc,
            'syllabus_pdf_year_1' => $syl_pdf_1,
            'syllabus_pdf_year_2' => $syl_pdf_2,
            'syllabus_pdf_year_3' => $syl_pdf_3,
            'syllabus_pdf_year_4' => $syl_pdf_4,
            'sort_order' => $sort_order,
            'is_active' => $is_active,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($course_id) {
            $db->update('courses', $data, 'id = ?', [$course_id]);
            if (!empty($upload_errors)) {
                setFlash('danger', 'Course details saved, but some file uploads failed: ' . implode(' | ', $upload_errors));
                redirect("courses.php?action=edit&id={$course_id}");
            }
            setFlash('success', 'Course details, year-wise fees, and syllabus PDFs updated successfully.');
        } else {
            $new_id = $db->insert('courses', $data);
            if (!empty($upload_errors)) {
                setFlash('danger', 'Course created, but some file uploads failed: ' . implode(' | ', $upload_errors));
                redirect("courses.php?action=edit&id={$new_id}");
            }
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['delete_syllabus_pdf']) || isset($_POST['year']))) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $course_id = (int)($_POST['course_id'] ?? 0);
    $year = (int)($_POST['year'] ?? 0);
    $field = "syllabus_pdf_year_" . $year;

    if ($course_id > 0 && $year >= 1 && $year <= 4) {
        if (!Security::validateCSRF($csrf)) {
            setFlash('danger', 'CSRF validation failed.');
        } else {
            $course = $db->fetchOne("SELECT * FROM courses WHERE id = ?", [$course_id]);
            if ($course && !empty($course[$field])) {
                $filePath = UPLOAD_PATH . 'courses/' . $course[$field];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                } elseif (file_exists(__DIR__ . '/../../assets/uploads/courses/' . $course[$field])) {
                    @unlink(__DIR__ . '/../../assets/uploads/courses/' . $course[$field]);
                }
            }
            $db->update('courses', [$field => null], 'id = ?', [$course_id]);
            setFlash('success', "Year {$year} Syllabus PDF file unlinked and deleted successfully.");
            redirect("courses.php?action=edit&id={$course_id}");
        }
    }
}

// Fetch all courses
$courses = $db->fetchAll(
    "SELECT c.*, d.name as department_name 
     FROM courses c JOIN departments d ON c.department_id = d.id 
     ORDER BY d.name, c.name"
);
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Academics & Curricula</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Programs, Fees & Syllabus Manager</h2>
        <p class="text-muted small mb-0">Manage degree tracks, four-year tuition structures, intake quotas, and official curriculum documents</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="courses.php?action=export_csv" class="btn btn-sm btn-success rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="courses.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold"><i class="fa-solid fa-plus me-1 text-warning"></i> Register New Program</a>
        </div>
    <?php else: ?>
        <a href="courses.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-book-open text-warning me-2"></i>Registered Degree Programs (<?php echo count($courses); ?>)</h5>
            <input type="text" id="courseSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search course name, code..." style="width: 240px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="coursesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Cover</th>
                            <th>Course Name &amp; Code</th>
                            <th>Category</th>
                            <th>Department</th>
                            <th>Year-Wise Fees Breakdown</th>
                            <th>Syllabus &amp; Booklet</th>
                            <th class="text-center">Capacity</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <?php $totalFee = $c['fee_year_1'] + $c['fee_year_2'] + $c['fee_year_3'] + $c['fee_year_4']; ?>
                            <tr>
                                <td class="ps-4">
                                    <?php if (!empty($c['image_path'])): ?>
                                        <img src="<?php echo uploadUrl('courses', $c['image_path']); ?>" alt="Cover" class="rounded-3 shadow-xs" style="width: 48px; height: 48px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded-3 text-muted d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px; font-size: 14px;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-display fw-bold text-dark mb-0.5"><?php echo htmlspecialchars($c['name']); ?></div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill"><?php echo htmlspecialchars($c['code']); ?></span>
                                    <small class="text-muted opacity-75 ms-1"><?php echo $c['duration_years']; ?> Years (<?php echo $c['semester_count']; ?> Semesters)</small>
                                </td>
                                <td>
                                    <?php 
                                    $pt = $c['program_type'] ?? 'Regular';
                                    $pClass = 'bg-primary-subtle text-primary';
                                    if ($pt === 'Integrated') $pClass = 'bg-success-subtle text-success';
                                    elseif ($pt === 'Direct Second Year') $pClass = 'bg-warning-subtle text-dark';
                                    elseif ($pt === 'Diploma') $pClass = 'bg-purple-subtle text-purple';
                                    ?>
                                    <span class="badge <?php echo $pClass; ?> rounded-pill px-2.5 py-1 small"><?php echo htmlspecialchars($pt); ?></span>
                                </td>
                                <td><span class="text-muted small font-semibold"><?php echo htmlspecialchars($c['department_name']); ?></span></td>
                                <td>
                                    <div class="small text-nowrap">
                                        <span class="d-block text-muted">Y1: ₹<?php echo number_format($c['fee_year_1']); ?> | Y2: ₹<?php echo number_format($c['fee_year_2']); ?></span>
                                        <span class="d-block text-muted">Y3: ₹<?php echo number_format($c['fee_year_3']); ?> | Y4: ₹<?php echo number_format($c['fee_year_4']); ?></span>
                                        <strong class="text-success">Total: ₹<?php echo number_format($totalFee); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php $hasAnyPdf = false; ?>
                                        <?php if (!empty($c['brochure_document'])): $hasAnyPdf = true; ?>
                                            <a href="<?php echo APP_URL . '/' . ltrim($c['brochure_document'], '/'); ?>" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2 rounded-pill font-semibold" title="Official Booklet" style="font-size: 14px;">
                                                <i class="fa-solid fa-file-pdf me-1"></i>Booklet
                                            </a>
                                        <?php endif; ?>
                                        <?php for ($y = 1; $y <= 4; $y++): ?>
                                            <?php $pdfKey = "syllabus_pdf_year_{$y}"; ?>
                                            <?php if (!empty($c[$pdfKey])): $hasAnyPdf = true; ?>
                                                <a href="<?php echo uploadUrl('courses', $c[$pdfKey]); ?>" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2 rounded-pill font-semibold" title="Year <?php echo $y; ?> Syllabus PDF" style="font-size: 14px;">
                                                    <i class="fa-solid fa-file-lines me-1"></i>Y<?php echo $y; ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <?php if (!$hasAnyPdf): ?>
                                            <span class="badge bg-light text-muted border rounded-pill" style="font-size: 14px;">No PDFs</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center fw-bold fs-6"><?php echo $c['intake_capacity']; ?></td>
                                <td class="text-center"><?php echo statusBadge($c['is_active'] ? 'active' : 'inactive'); ?></td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="courses.php?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1"><i class="fa-solid fa-pen-to-square me-1"></i> Edit</a>
                                    <a href="courses.php?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 btn-delete-confirm font-semibold" onclick="return confirm('Delete this course?');"><i class="fa-solid fa-trash-can me-1"></i> Delete</a>
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

                        <div class="col-md-3">
                            <label class="form-label font-semibold">Program Category *</label>
                            <select name="program_type" class="form-select" required>
                                <?php 
                                $currType = $edit_course['program_type'] ?? 'Regular';
                                $types = ['Regular', 'Integrated', 'Direct Second Year', 'Diploma'];
                                foreach ($types as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo ($currType === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Course Code *</label>
                            <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($edit_course['code'] ?? ''); ?>" placeholder="BTECH-AIDS">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Duration (Years) *</label>
                            <input type="number" name="duration_years" class="form-control" required value="<?php echo htmlspecialchars($edit_course['duration_years'] ?? '4'); ?>" min="1" max="6">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Semester Count *</label>
                            <input type="number" name="semester_count" class="form-control" required value="<?php echo htmlspecialchars($edit_course['semester_count'] ?? '8'); ?>" min="1" max="12">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold">Approved Intake Capacity (Seats) *</label>
                            <input type="number" name="intake_capacity" class="form-control" required value="<?php echo htmlspecialchars($edit_course['intake_capacity'] ?? '60'); ?>" min="1">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold">Upload Course Cover Image</label>
                            <input type="file" name="course_image" class="form-control" accept="image/*">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold">Official Curriculum Booklet PDF</label>
                            <input type="file" name="brochure_file" class="form-control" accept=".pdf,.doc,.docx">
                            <?php if (!empty($edit_course['brochure_document'])): ?>
                                <small class="text-muted d-block mt-1">Current: <a href="<?php echo APP_URL . '/' . ltrim($edit_course['brochure_document'], '/'); ?>" target="_blank" class="text-primary"><?php echo htmlspecialchars(basename($edit_course['brochure_document'])); ?></a></small>
                                <input type="hidden" name="brochure_document" value="<?php echo htmlspecialchars($edit_course['brochure_document']); ?>">
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Course Description &amp; Highlights</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief outline of curriculum focus, lab infrastructure, and career prospects..."><?php echo htmlspecialchars($edit_course['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Curriculum &amp; Laboratory Overview (Syllabus Summary)</label>
                            <textarea name="curriculum_overview" class="form-control" rows="3" placeholder="NEP 2020 structure, core subjects, specializations, practical labs..."><?php echo htmlspecialchars($edit_course['curriculum_overview'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Admission Eligibility &amp; Selection Criteria</label>
                            <textarea name="admission_info" class="form-control" rows="3" placeholder="10+2 / HSC PCM min 45% (40% reserved) + JEE/MHT-CET score..."><?php echo htmlspecialchars($edit_course['admission_info'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Core Courses &amp; Subjects (Syllabus Topics — One per line)</label>
                            <textarea name="core_courses" class="form-control" rows="4" placeholder="Data Structures & Algorithms&#10;Operating Systems&#10;Machine Learning & Deep Learning..."><?php echo htmlspecialchars($edit_course['core_courses'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Career Opportunities &amp; Roles (One per line)</label>
                            <textarea name="career_opportunities" class="form-control" rows="4" placeholder="AI / Machine Learning Engineer&#10;Cyber Security Analyst&#10;Embedded Systems Architect..."><?php echo htmlspecialchars($edit_course['career_opportunities'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Eligibility Requirements</label>
                            <input type="text" name="eligibility" class="form-control" value="<?php echo htmlspecialchars($edit_course['eligibility'] ?? ''); ?>" placeholder="10+2 (HSC) PCM min 45% + Valid MHT-CET / JEE Main score">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Display Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?php echo $edit_course ? (int)($edit_course['sort_order'] ?? 0) : 0; ?>">
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
                                                        <button type="submit" name="delete_syllabus_pdf" value="1" form="deletePdfForm<?php echo $y; ?>" class="btn btn-outline-danger btn-xs py-0.5 px-2 font-semibold">
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
                        <form id="deletePdfForm<?php echo $y; ?>" method="POST" action="courses.php?action=edit&id=<?php echo $edit_course['id']; ?>" onsubmit="return confirm('Delete this Year <?php echo $y; ?> Syllabus file?');">
                            <?php echo Security::csrfField(); ?>
                            <input type="hidden" name="delete_syllabus_pdf" value="1">
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
