<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_fees');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_fee = null;
$courses = $db->fetchAll("SELECT * FROM courses WHERE is_active = 1 ORDER BY name");

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT c.name as course_name, c.code as course_code, f.semester, f.academic_year, f.amount 
         FROM fees f JOIN courses c ON f.course_id = c.id 
         ORDER BY c.name, f.semester"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Fee_Structures_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Course Name', 'Course Code', 'Semester', 'Academic Year', 'Amount (INR)']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['course_name'],
            $r['course_code'],
            'Semester ' . $r['semester'],
            $r['academic_year'],
            $r['amount']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Fee Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_fee = $db->fetchOne("SELECT * FROM fees WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fee'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $fee_id = isset($_POST['fee_id']) ? (int)$_POST['fee_id'] : null;
    $course_id = (int)$_POST['course_id'];
    $semester = (int)$_POST['semester'];
    $amount = (float)$_POST['amount'];
    $academic_year = trim($_POST['academic_year']);

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($course_id) || empty($semester) || empty($amount) || empty($academic_year)) {
        setFlash('danger', 'Please enter all fields.');
    } else {
        if ($fee_id) {
            // Edit
            $db->update('fees', [
                'course_id' => $course_id,
                'semester' => $semester,
                'amount' => $amount,
                'academic_year' => $academic_year
            ], 'id = ?', [$fee_id]);
            setFlash('success', 'Fee structure details modified.');
            redirect('fees.php');
        } else {
            // Add
            // Check if record already exists for the course/semester/year
            $exists = $db->fetchOne(
                "SELECT id FROM fees WHERE course_id = ? AND semester = ? AND academic_year = ?", 
                [$course_id, $semester, $academic_year]
            );
            if ($exists) {
                setFlash('danger', 'Fee structure for this course, semester, and academic year already exists.');
            } else {
                $db->insert('fees', [
                    'course_id' => $course_id,
                    'semester' => $semester,
                    'amount' => $amount,
                    'academic_year' => $academic_year
                ]);
                setFlash('success', 'Fee structure configured successfully.');
                redirect('fees.php');
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('fees', 'id = ?', [$target_id]);
    setFlash('success', 'Fee configuration deleted.');
    redirect('fees.php');
}

// Fetch all fees
$fees = $db->fetchAll(
    "SELECT f.*, c.name as course_name, c.code as course_code 
     FROM fees f JOIN courses c ON f.course_id = c.id 
     ORDER BY c.name, f.semester"
);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-indian-rupee-sign text-warning me-2"></i>Fee Structures Manager</h1>
        <small class="text-muted">Manage semester-wise and academic year tuition fees across degree tracks</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="fees.php?action=export_csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="fees.php?action=add" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Configure Fees</a>
        </div>
    <?php else: ?>
        <a href="fees.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-receipt text-warning me-2"></i>Configured Fee Schedules (<?php echo count($fees); ?>)</h5>
            <input type="text" id="feeSearchInput" class="form-control form-control-sm" placeholder="Search course, code, year..." style="width: 250px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="feesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Course / Program</th>
                            <th class="text-center">Semester</th>
                            <th>Academic Year</th>
                            <th class="text-end">Amount (INR)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fees)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">No fee structures configured.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fees as $fee): ?>
                                <tr>
                                    <td class="fw-bold text-primary-color"><?php echo htmlspecialchars($fee['course_name']); ?> (<?php echo htmlspecialchars($fee['course_code']); ?>)</td>
                                    <td class="text-center"><span class="badge bg-secondary">Semester <?php echo $fee['semester']; ?></span></td>
                                    <td><code><?php echo htmlspecialchars($fee['academic_year']); ?></code></td>
                                    <td class="text-end fw-bold text-success">₹<?php echo number_format($fee['amount'], 2); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="fees.php?action=edit&id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <a href="fees.php?action=delete&id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete" onclick="return confirm('Delete this fee configuration?');"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-indian-rupee-sign text-warning me-2"></i><?php echo $edit_fee ? 'Edit Fee Details' : 'Configure Course Fees'; ?></h4>
                <form method="POST" action="fees.php">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_fee): ?>
                        <input type="hidden" name="fee_id" value="<?php echo $edit_fee['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Course / Degree Program *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select Course...</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($edit_fee && $edit_fee['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Semester Number (1 to 8) *</label>
                            <select name="semester" class="form-select" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($edit_fee && $edit_fee['semester'] == $i) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Fee Amount (INR) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="amount" class="form-control" required value="<?php echo $edit_fee ? htmlspecialchars($edit_fee['amount']) : ''; ?>" placeholder="75000.00">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Academic Year Session *</label>
                            <input type="text" name="academic_year" class="form-control" required value="<?php echo $edit_fee ? htmlspecialchars($edit_fee['academic_year']) : date('Y') . '-' . (date('Y') + 1); ?>" placeholder="2026-2027">
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="fees.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_fee" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Fee Structure</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
