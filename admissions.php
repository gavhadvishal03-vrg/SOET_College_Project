<?php
$page_title = "Admissions Portal & Live Seat Tracker";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$courses = $cms->getCourses(null, true);
$seatMetrics = $cms->getCourseSeatMetrics();
$seatMetricsById = [];
foreach ($seatMetrics as $m) {
    $seatMetricsById[$m['course_id']] = $m;
}

$instructions = $cms->getSetting('admission_instructions', 'Eligible candidates must have passed 10+2 with Physics, Mathematics, and Chemistry with minimum 60% aggregate.');

// Handle status tracking query
$searched_application = null;
if (isset($_GET['track_app_no'])) {
    $app_no = Security::sanitize($_GET['track_app_no']);
    if (!empty($app_no)) {
        $searched_application = $cms->db->fetchOne(
            "SELECT a.*, c.name as course_name, c.code as course_code FROM admissions a 
             JOIN courses c ON a.course_id = c.id 
             WHERE a.application_number = ?",
            [$app_no]
        );
        if (!$searched_application) {
            setFlash('danger', 'No admission application found with Application Number: ' . htmlspecialchars($app_no));
        }
    }
}

// Handle new admission registration
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['submit_admission'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $name = Security::sanitize($_POST['student_name']);
    $email = Security::sanitizeEmail($_POST['email']);
    $phone = Security::sanitize($_POST['phone']);
    $dob = Security::sanitize($_POST['date_of_birth']);
    $gender = Security::sanitize($_POST['gender']);
    $address = Security::sanitize($_POST['address']);
    $course_id = (int)$_POST['course_id'];
    $marks_10 = (float)$_POST['percentage_10th'];
    $marks_12 = (float)$_POST['percentage_12th'];

    $chosenMetric = $cms->getCourseSeatMetrics($course_id);

    // Check rate limit (max 5 submissions per 10 mins per IP)
    if (!Security::checkRateLimit('admission_form', 5, 600)) {
        setFlash('danger', 'Too many requests. Please wait a few minutes before submitting another application.');
    } elseif (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'Invalid security token. Please try again.');
    } elseif (empty($name) || empty($email) || empty($phone) || empty($dob) || empty($gender) || empty($address) || empty($course_id) || empty($marks_10) || empty($marks_12)) {
        setFlash('danger', 'All fields marked with an asterisk (*) are mandatory.');
    } elseif (!$chosenMetric || !$chosenMetric['is_open']) {
        setFlash('danger', 'Admissions for the selected degree program are currently CLOSED or seats are full. Please choose another program.');
    } elseif (!Security::validateEmail($email)) {
        setFlash('danger', 'Invalid email address format.');
    } elseif (!isset($_FILES['marksheet']) || $_FILES['marksheet']['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', 'Please upload a valid 12th Standard Marksheet / document.');
    } else {
        // Validate file upload
        $file_errors = Security::validateUpload($_FILES['marksheet'], ALLOWED_DOC_TYPES);
        if (!empty($file_errors)) {
            setFlash('danger', implode(' ', $file_errors));
        } else {
            // Upload document
            $uploaded_filename = Security::uploadFile($_FILES['marksheet'], 'admissions', 'marksheet_');
            if (!$uploaded_filename) {
                setFlash('danger', 'Unable to upload marksheet file. Please try again.');
            } else {
                // Generate application number
                $app_number = generateApplicationNumber();

                // Save to Database
                $inserted_id = $cms->db->insert('admissions', [
                    'application_number' => $app_number,
                    'student_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'date_of_birth' => $dob,
                    'gender' => $gender,
                    'address' => $address,
                    'course_id' => $course_id,
                    'percentage_10th' => $marks_10,
                    'percentage_12th' => $marks_12,
                    'document_path' => $uploaded_filename,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                if ($inserted_id) {
                    setFlash('success', 'Application submitted successfully! Your Application Number is: <strong>' . $app_number . '</strong>. Copy this number to track your status below.');
                    redirect('admissions.php?track_app_no=' . urlencode($app_number));
                } else {
                    setFlash('danger', 'Database error: unable to register application. Please contact college support.');
                }
            }
        }
    }
}
$settings = $cms->getSiteSettings();
$admissions_bg = !empty($settings['admissions_hero_image']) ? uploadUrl('settings', $settings['admissions_hero_image']) : null;
?>

<!-- Breadcrumb Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $admissions_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$admissions_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">Admissions Portal & Seat Tracker</h1>
        <p class="lead opacity-75 mb-3">Apply online for B.Tech programs, check real-time seat metrics, and track your application</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Admissions</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Real-Time Seat Availability Section -->
<div class="container mb-5">
    <div class="card border-0 shadow-sm p-4 bg-white mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold text-primary-color mb-0">
                    <i class="fa-solid fa-chair text-warning me-2"></i>Real-Time Seat Availability Tracker
                </h4>
                <small class="text-muted">Live seat quota & admission status across engineering branches</small>
            </div>
            <span class="badge bg-success py-2 px-3"><i class="fa-solid fa-bolt me-1"></i> Live Database Synced</span>
        </div>

        <div class="row g-3">
            <?php foreach ($seatMetrics as $metric): 
                $percent = ($metric['total_intake'] > 0) ? round(($metric['filled_seats'] / $metric['total_intake']) * 100) : 0;
                $statusBadgeClass = ($metric['admission_status'] === 'OPEN' && $metric['vacant_seats'] > 0) ? 'bg-success' : 'bg-danger';
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border p-3 bg-light rounded-3 shadow-xs">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($metric['course_code']); ?></span>
                            <span class="badge <?php echo $statusBadgeClass; ?>"><?php echo $metric['admission_status']; ?></span>
                        </div>
                        <h6 class="fw-bold text-primary-color mb-2"><?php echo htmlspecialchars($metric['course_name']); ?></h6>
                        
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Filled: <strong><?php echo $metric['filled_seats']; ?> / <?php echo $metric['total_intake']; ?></strong></span>
                            <span class="text-success font-semibold"><?php echo $metric['vacant_seats']; ?> Vacant</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar <?php echo $percent > 85 ? 'bg-danger' : ($percent > 60 ? 'bg-warning' : 'bg-primary'); ?>" role="progressbar" style="width: <?php echo $percent; ?>%;" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar instructions & Status Tracking -->
        <div class="col-lg-4">
            <!-- Track Status Form -->
            <div class="card border-0 shadow-sm p-4 bg-light mb-4">
                <h5 class="fw-bold text-primary-color mb-3"><i class="fa-solid fa-magnifying-glass text-warning me-2"></i>Track Status</h5>
                <p class="small text-muted mb-3">Enter your unique Application Number (e.g. SOET2026xxxxxx) to view the verification timeline.</p>
                <form method="GET" action="admissions.php">
                    <div class="input-group mb-2">
                        <input type="text" name="track_app_no" class="form-control py-2 font-semibold" placeholder="SOET2026XXXXXX" required value="<?php echo isset($_GET['track_app_no']) ? htmlspecialchars($_GET['track_app_no']) : ''; ?>">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </form>

                <?php if ($searched_application): 
                    $st = $searched_application['status'];
                    $step1_done = true;
                    $step2_done = in_array($st, ['verified', 'approved', 'confirmed']);
                    $step3_done = in_array($st, ['approved', 'confirmed']);
                    $step4_done = ($st === 'confirmed');
                ?>
                    <div class="card border-0 bg-white shadow-xs p-3 mt-3">
                        <small class="text-muted d-block mb-1">Application Record</small>
                        <h6 class="fw-bold text-primary-color mb-0"><?php echo htmlspecialchars($searched_application['student_name']); ?></h6>
                        <small class="text-secondary d-block mb-3"><?php echo htmlspecialchars($searched_application['course_name']); ?> (<?php echo htmlspecialchars($searched_application['course_code']); ?>)</small>
                        
                        <!-- Visual Timeline Tracker -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa-solid fa-circle-check text-success me-2"></i>
                                <span class="small font-semibold">1. Application Submitted</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa-solid <?php echo $step2_done ? 'fa-circle-check text-success' : 'fa-circle-dot text-muted'; ?> me-2"></i>
                                <span class="small <?php echo $step2_done ? 'font-semibold text-dark' : 'text-muted'; ?>">2. Document Verification</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa-solid <?php echo $step3_done ? 'fa-circle-check text-success' : 'fa-circle-dot text-muted'; ?> me-2"></i>
                                <span class="small <?php echo $step3_done ? 'font-semibold text-dark' : 'text-muted'; ?>">3. Merit Review & Eligibility</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa-solid <?php echo $step4_done ? 'fa-circle-check text-success' : ($st === 'rejected' || $st === 'cancelled' ? 'fa-circle-xmark text-danger' : 'fa-circle-dot text-muted'); ?> me-2"></i>
                                <span class="small <?php echo $step4_done ? 'font-bold text-success' : ($st === 'rejected' ? 'text-danger font-semibold' : 'text-muted'); ?>">4. Admission Seat Allotted</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-light">
                            <span class="small font-semibold">Current State:</span>
                            <?php echo statusBadge($searched_application['status']); ?>
                        </div>

                        <?php if ($searched_application['remarks']): ?>
                            <div class="bg-light p-2 rounded small mt-2">
                                <strong>Admin Remarks:</strong> <?php echo htmlspecialchars($searched_application['remarks']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-3 pt-2">
                            <a href="<?php echo uploadUrl('admissions', $searched_application['document_path']); ?>" target="_blank" class="btn btn-xs btn-outline-secondary w-100" style="font-size: 11px;">
                                <i class="fa-solid fa-file-arrow-down me-1"></i> Download Uploaded Marksheet
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <h5 class="fw-bold text-warning mb-3"><i class="fa-solid fa-circle-info me-2"></i>Eligibility & Rules</h5>
                <p class="small text-white-50"><?php echo nl2br(htmlspecialchars($instructions)); ?></p>
                <hr class="bg-secondary">
                <p class="small text-white-50 mb-0"><strong>Admissions Cell Contacts:</strong><br>Phone: +91 9371714253<br>Email: admissionsoet@mgmu.ac.in</p>
            </div>
        </div>

        <!-- Admission Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-user-plus text-warning me-2"></i>Online Admission Application</h4>
                
                <form method="POST" action="admissions.php" enctype="multipart/form-data" id="admissionForm" class="needs-validation" novalidate>
                    <?php echo Security::csrfField(); ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Full Name of Student *</label>
                            <input type="text" name="student_name" class="form-control" placeholder="Enter student full name" required>
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Email Address *</label>
                            <input type="email" name="email" class="form-control" placeholder="student@example.com" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Mobile Number * (10 Digits)</label>
                            <input type="tel" pattern="[0-9]{10}" name="phone" class="form-control" placeholder="9876543210" required>
                            <div class="invalid-feedback">Please provide a 10-digit mobile number.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Date of Birth *</label>
                            <input type="date" name="date_of_birth" class="form-control" required>
                            <div class="invalid-feedback">Required field.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-semibold">Gender *</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">Required field.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold">Permanent Residential Address *</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Full postal address with pincode" required></textarea>
                            <div class="invalid-feedback">Please enter your complete address.</div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label font-semibold">Degree Program Preference *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select a B.Tech / M.Tech Branch (Real-Time Seats)</option>
                                <?php foreach ($courses as $c): 
                                    $sm = $seatMetricsById[$c['id']] ?? null;
                                    $vacant = $sm ? $sm['vacant_seats'] : 60;
                                    $statusStr = ($sm && $sm['is_open']) ? "{$vacant} Vacant Seats — OPEN" : "CLOSED / Seats Full";
                                    $disabled = ($sm && !$sm['is_open']) ? 'disabled' : '';
                                ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $disabled; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['code']); ?>) — <?php echo $statusStr; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a program with open seats.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label font-semibold">10th Standard Aggregate % *</label>
                            <input type="number" step="0.01" min="0" max="100" name="percentage_10th" class="form-control" placeholder="e.g. 85.50" required>
                            <div class="invalid-feedback">Enter valid percentage (0 - 100).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">12th Standard Aggregate % *</label>
                            <input type="number" step="0.01" min="0" max="100" name="percentage_12th" class="form-control" placeholder="e.g. 82.30" required>
                            <div class="invalid-feedback">Enter valid percentage (0 - 100).</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Upload 12th Marksheet / Supporting Doc * (PDF/JPG/PNG up to 5MB)</label>
                            <input type="file" name="marksheet" class="form-control" required accept=".pdf,image/jpeg,image/png">
                            <div class="invalid-feedback">Please attach your marksheet document.</div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" name="submit_admission" class="btn btn-primary px-5 py-2">
                                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Submit Admission Application
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap interactive validation
(function () {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
