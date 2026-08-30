<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_admissions');

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $db = Database::getInstance();
    $rows = $db->fetchAll(
        "SELECT a.application_number, a.student_name, a.email, a.phone, a.date_of_birth, a.gender, 
                c.name as course_name, c.code as course_code, a.percentage_10th, a.percentage_12th, 
                a.status, a.remarks, a.created_at 
         FROM admissions a JOIN courses c ON a.course_id = c.id 
         ORDER BY a.created_at DESC"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Admissions_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Application No', 'Student Name', 'Email', 'Phone', 'DOB', 'Gender', 'Course Name', 'Course Code', '10th %', '12th %', 'Status', 'Remarks', 'Submitted At']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['application_number'],
            $r['student_name'],
            $r['email'],
            $r['phone'],
            $r['date_of_birth'],
            $r['gender'],
            $r['course_name'],
            $r['course_code'],
            $r['percentage_10th'],
            $r['percentage_12th'],
            ucfirst($r['status']),
            $r['remarks'],
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Admissions & Real-Time Seat Availability Manager";
include_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$cms = new ContentManager();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$app = null;

if (in_array($action, ['view']) && isset($_GET['id'])) {
    $app_id = (int)$_GET['id'];
    $app = $db->fetchOne(
        "SELECT a.*, c.name as course_name, c.code as course_code 
         FROM admissions a JOIN courses c ON a.course_id = c.id 
         WHERE a.id = ?",
        [$app_id]
    );
}

// Handle Course Intake Capacity & Admission Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_seat_settings'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $course_id = (int)$_POST['course_id'];
    $intake = max(1, (int)$_POST['intake_capacity']);
    $adm_status = in_array($_POST['admission_status'], ['OPEN', 'CLOSED', 'PAUSED']) ? $_POST['admission_status'] : 'OPEN';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $db->update('courses', [
            'intake_capacity' => $intake,
            'admission_status' => $adm_status,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$course_id]);
        
        // Re-sync filled seats
        $cms->syncCourseSeats($course_id);

        setFlash('success', 'Course intake capacity & admission status updated successfully. Real-time seat metrics synchronized.');
        redirect('admissions.php');
    }
}

// Handle Admission Verification / Confirmation / Cancellation Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $app_id = (int)$_POST['app_id'];
    $status = $_POST['status']; // confirmed, approved, cancelled, verified, rejected
    $remarks = trim($_POST['remarks']);

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($status)) {
        setFlash('danger', 'Please choose a status.');
    } else {
        // Execute transactional status update with capacity check & seat sync
        $result = $cms->updateAdmissionStatusWithSeats($app_id, $status, $remarks);

        if ($result['success']) {
            // Send Email Notification to Applicant
            if (!empty($result['email'])) {
                Mailer::sendReviewNotification(
                    $result['email'],
                    $result['student_name'] ?? 'Applicant',
                    'Admission Application',
                    $result['course_name'] ?? 'Degree Program',
                    $status,
                    $remarks
                );
            }
            setFlash('success', $result['message']);
            redirect('admissions.php');
        } else {
            setFlash('danger', $result['message']);
            redirect('admissions.php?action=view&id=' . $app_id);
        }
    }
}

// Fetch all applications
$applications = $db->fetchAll(
    "SELECT a.*, c.code as course_code, c.name as course_name 
     FROM admissions a JOIN courses c ON a.course_id = c.id 
     ORDER BY a.created_at DESC"
);

// Fetch real-time seat metrics for all courses
$seatMetrics = $cms->getCourseSeatMetrics();
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Admissions & Real-Time Seat Manager</h1>
        <small class="text-muted">Live synchronization between Admission Portal $\rightarrow$ Database $\rightarrow$ 🤖 CampusAI Chatbot API</small>
    </div>
    <?php if ($action !== 'list'): ?>
        <a href="admissions.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to Queue</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <!-- 1. Real-Time Seat Availability Dashboard -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-white"><i class="fa-solid fa-chart-pie text-warning me-2"></i>Course-Wise Seat Availability & Admission Status</h5>
            <span class="badge bg-warning text-dark"><i class="fa-solid fa-rotate me-1"></i> Real-Time Database Sync</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th>Course Name / Code</th>
                            <th class="text-center">Total Intake</th>
                            <th class="text-center">Filled Seats</th>
                            <th class="text-center">Vacant Seats</th>
                            <th class="text-center">Pending Apps</th>
                            <th class="text-center">Cancelled Apps</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Last Updated</th>
                            <th class="text-end">Manage Intake</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seatMetrics as $m): ?>
                            <tr>
                                <td>
                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($m['course_name']); ?></strong>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($m['course_code']); ?></span>
                                </td>
                                <td class="text-center fw-bold fs-6"><?php echo $m['total_intake']; ?></td>
                                <td class="text-center fw-bold text-success fs-6"><?php echo $m['filled_seats']; ?></td>
                                <td class="text-center fw-bold <?php echo $m['vacant_seats'] > 0 ? 'text-primary' : 'text-danger'; ?> fs-6">
                                    <?php echo $m['vacant_seats']; ?>
                                </td>
                                <td class="text-center"><span class="badge bg-warning text-dark"><?php echo $m['pending_applications']; ?></span></td>
                                <td class="text-center"><span class="badge bg-light text-muted border"><?php echo $m['cancelled_applications']; ?></span></td>
                                <td class="text-center">
                                    <?php if ($m['admission_status'] === 'OPEN' && $m['vacant_seats'] > 0): ?>
                                        <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-door-open me-1"></i> OPEN</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-door-closed me-1"></i> CLOSED</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center small text-muted"><?php echo $m['last_updated']; ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalSeat<?php echo $m['course_id']; ?>">
                                        <i class="fa-solid fa-sliders me-1"></i> Edit
                                    </button>

                                    <!-- Edit Seat Modal -->
                                    <div class="modal fade" id="modalSeat<?php echo $m['course_id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content">
                                                <form method="POST" action="admissions.php">
                                                    <?php echo Security::csrfField(); ?>
                                                    <input type="hidden" name="course_id" value="<?php echo $m['course_id']; ?>">
                                                    <div class="modal-header bg-dark text-white">
                                                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-sliders text-warning me-2"></i>Update <?php echo htmlspecialchars($m['course_code']); ?> Seat Capacity</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label font-semibold">Total Approved Intake Capacity *</label>
                                                            <input type="number" name="intake_capacity" class="form-control" value="<?php echo $m['total_intake']; ?>" min="1" required>
                                                            <small class="text-muted">Currently Filled Seats: <strong><?php echo $m['filled_seats']; ?></strong></small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label font-semibold">Admission Status *</label>
                                                            <select name="admission_status" class="form-select" required>
                                                                <option value="OPEN" <?php echo ($m['admission_status'] === 'OPEN') ? 'selected' : ''; ?>>OPEN (Accepting Applications)</option>
                                                                <option value="CLOSED" <?php echo ($m['admission_status'] === 'CLOSED') ? 'selected' : ''; ?>>CLOSED (Admissions Closed)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_seat_settings" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. Admission Applications Queue Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-list-check text-warning me-2"></i>Student Admission Applications Queue</h5>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="admQueueSearchInput" class="form-control form-control-sm" placeholder="Search applicant, app #..." style="width: 200px;">
                <select id="admStatusFilter" class="form-select form-select-sm" style="width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="verified">Verified</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="rejected">Rejected</option>
                </select>
                <a href="admissions.php?action=export_csv" class="btn btn-sm btn-success">
                    <i class="fa-solid fa-file-excel me-1"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="admissionsQueueTable">
                    <thead class="table-dark">
                        <tr>
                            <th>App No</th>
                            <th>Student Name</th>
                            <th>Email/Phone</th>
                            <th>Course</th>
                            <th class="text-center">10th %</th>
                            <th class="text-center">12th %</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">No admission applications received yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $a): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($a['application_number']); ?></td>
                                    <td class="fw-bold text-primary-color"><?php echo htmlspecialchars($a['student_name']); ?></td>
                                    <td>
                                        <small class="d-block text-secondary"><?php echo htmlspecialchars($a['email']); ?></small>
                                        <small class="d-block text-muted"><?php echo htmlspecialchars($a['phone']); ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($a['course_code']); ?></span></td>
                                    <td class="text-center"><?php echo $a['percentage_10th']; ?>%</td>
                                    <td class="text-center"><?php echo $a['percentage_12th']; ?>%</td>
                                    <td class="text-center">
                                        <?php if ($a['status'] === 'confirmed' || $a['status'] === 'approved'): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-user-check me-1"></i> Confirmed (Seat Assigned)</span>
                                        <?php elseif ($a['status'] === 'cancelled'): ?>
                                            <span class="badge bg-secondary"><i class="fa-solid fa-user-minus me-1"></i> Cancelled (Seat Freed)</span>
                                        <?php else: ?>
                                            <?php echo statusBadge($a['status']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="admissions.php?action=view&id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-user-pen me-1"></i> Review / Action</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($action === 'view' && $app): ?>
    <!-- Application Review screen -->
    <div class="row">
        <!-- Candidate profile details -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-circle-info text-warning me-2"></i>Candidate Profile</h5>
                
                <div class="row g-3 small text-secondary">
                    <div class="col-md-6">
                        <strong class="text-dark">Application Number:</strong><br>
                        <code><?php echo htmlspecialchars($app['application_number']); ?></code>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-dark">Applicant Name:</strong><br>
                        <?php echo htmlspecialchars($app['student_name']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-dark">Email Address:</strong><br>
                        <?php echo htmlspecialchars($app['email']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-dark">Phone Number:</strong><br>
                        <?php echo htmlspecialchars($app['phone']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-dark">Date of Birth:</strong><br>
                        <?php echo formatDate($app['date_of_birth']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-dark">Gender:</strong><br>
                        <?php echo htmlspecialchars($app['gender']); ?>
                    </div>
                    <div class="col-md-12">
                        <strong class="text-dark">Branch / Course Choice:</strong><br>
                        <?php echo htmlspecialchars($app['course_name']); ?> (<?php echo htmlspecialchars($app['course_code']); ?>)
                    </div>
                    <div class="col-md-6">
                        <strong class="text-dark">10th Class Percentage:</strong><br>
                        <?php echo $app['percentage_10th']; ?>%
                    </div>
                    <div class="col-md-6">
                        <strong class="text-dark">12th Class Percentage:</strong><br>
                        <?php echo $app['percentage_12th']; ?>%
                    </div>
                    <div class="col-12">
                        <strong class="text-dark">Residential Address:</strong><br>
                        <?php echo nl2br(htmlspecialchars($app['address'])); ?>
                    </div>
                </div>

                <hr class="bg-light">
                
                <div class="text-center mt-3">
                    <a href="<?php echo uploadUrl('admissions', $app['document_path']); ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-arrow-down me-1"></i> Open/Download Marksheet Document</a>
                </div>
            </div>
        </div>

        <!-- Evaluation actions -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-user-shield text-warning me-2"></i>Review Evaluation & Seat Allocation</h5>
                
                <div class="bg-light p-2.5 rounded small mb-3">
                    <strong>Current Status:</strong> 
                    <?php if ($app['status'] === 'confirmed' || $app['status'] === 'approved'): ?>
                        <span class="badge bg-success"><i class="fa-solid fa-user-check me-1"></i> Confirmed (Seat Assigned)</span>
                    <?php elseif ($app['status'] === 'cancelled'): ?>
                        <span class="badge bg-secondary"><i class="fa-solid fa-user-minus me-1"></i> Cancelled (Seat Freed)</span>
                    <?php else: ?>
                        <?php echo statusBadge($app['status']); ?>
                    <?php endif; ?>
                </div>

                <form method="POST" action="admissions.php">
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label font-semibold">Action Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="">Choose evaluation action...</option>
                            <option value="confirmed" <?php echo ($app['status'] === 'confirmed' || $app['status'] === 'approved') ? 'selected' : ''; ?>>Confirm Admission (Increases Filled Seats & Assigns Seat)</option>
                            <option value="cancelled" <?php echo ($app['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancel Admission (Decreases Filled Seats & Frees Up Vacant Seat)</option>
                            <option value="verified" <?php echo ($app['status'] === 'verified') ? 'selected' : ''; ?>>Verify Documents (Keep Pending Admission)</option>
                            <option value="rejected" <?php echo ($app['status'] === 'rejected') ? 'selected' : ''; ?>>Reject Application</option>
                        </select>
                        <small class="text-muted d-block mt-1"><i class="fa-solid fa-shield-halved text-success me-1"></i> Concurrency Protected: Prevents over-admission capacity.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold">Evaluation Remarks / Feedback</label>
                        <textarea name="remarks" class="form-control" rows="4" placeholder="Mention remarks regarding document verification or seat assignment..."><?php echo htmlspecialchars($app['remarks'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" name="update_status" class="btn btn-primary w-100 py-2.5 fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Execute Evaluation & Sync Seats</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('admQueueSearchInput');
    const statusFilter = document.getElementById('admStatusFilter');
    const table = document.getElementById('admissionsQueueTable');
    
    if (searchInput && table) {
        function filterRows() {
            const query = searchInput.value.toLowerCase().trim();
            const selectedStatus = statusFilter ? statusFilter.value.toLowerCase().trim() : '';
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const matchesQuery = !query || text.includes(query);
                const matchesStatus = !selectedStatus || text.includes(selectedStatus);

                if (matchesQuery && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterRows);
        if (statusFilter) {
            statusFilter.addEventListener('change', filterRows);
        }
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
