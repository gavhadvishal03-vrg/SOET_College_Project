<?php
/**
 * Public Admissions Portal Controller & View
 *
 * Handles prospective student admission registrations and status tracking:
 * - Real-time seat matrix & intake availability verification
 * - Dynamic eligibility guidelines (NEP 2020, CET scores, SSC/HSC percentage criteria)
 * - Secure application registration with CSRF validation, IP rate limiting, and document uploads
 * - Online application tracking system via unique application reference numbers (SOETYYYYXXXXXX)
 * - Official admission brochure and syllabus PDF downloads
 */

$page_title = "Admissions Portal & Guidelines";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$courses = $cms->getCourses(null, true);
$seatMetrics = $cms->getCourseSeatMetrics();
$seatMetricsById = [];
foreach ($seatMetrics as $m) {
    $seatMetricsById[$m['course_id']] = $m;
}

$instructions = $cms->getSetting('admission_instructions', 'B.Tech (4 Years): HSC Passed (min 45% Open, min 40% Reserved) + Non-zero score in JEE(Mains)/MHT-CET/PERA-CET/MGMU-CET. Lateral Entry: Diploma / B.Sc. / D.Voc. | B.Tech Integrated (6 Years): SSC Passed (min 50% Open, min 45% Reserved) + MGMU-CET.');

// Handle status tracking query by application number
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
    $marks_12 = (float)($_POST['percentage_12th'] ?? 0);

    $chosenMetric = $cms->getCourseSeatMetrics($course_id);

    // Check rate limit (max 5 submissions per 10 mins per IP)
    if (!Security::checkRateLimit('admission_form', 5, 600)) {
        setFlash('danger', 'Too many requests. Please wait a few minutes before submitting another application.');
    } elseif (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'Invalid security token. Please try again.');
    } elseif (empty($name) || empty($email) || empty($phone) || empty($dob) || empty($gender) || empty($address) || empty($course_id) || empty($marks_10)) {
        setFlash('danger', 'All fields marked with an asterisk (*) are mandatory.');
    } elseif (!$chosenMetric || !$chosenMetric['is_open']) {
        setFlash('danger', 'Admissions for the selected degree program are currently CLOSED or seats are full. Please choose another program.');
    } elseif (!Security::validateEmail($email)) {
        setFlash('danger', 'Invalid email address format.');
    } elseif (!isset($_FILES['marksheet']) || $_FILES['marksheet']['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', 'Please upload a valid Marksheet / document.');
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
$admissions_bg = !empty($settings['admissions_hero_image']) ? uploadUrl('settings', $settings['admissions_hero_image']) : uploadUrl('gallery', 'soet_campus_aerial.jpg');
?>

<?php
// Fetch official brochures from documents table
$officialDocs = $cms->getOfficialDocuments('brochure', true);
$officialLeaflets = $cms->getOfficialDocuments('leaflet', true);
$brochureLink = !empty($officialDocs) ? $officialDocs[0]['file_path'] : APP_URL . '/assets/documents/SOET_Admission_Brochure_2026-27.pdf';
$leafletLink = !empty($officialLeaflets) ? $officialLeaflets[0]['file_path'] : APP_URL . '/assets/documents/SOET_Official_Leaflet_2026-27.pdf';
$totalIntake = $settings['total_intake_ay2026_27'] ?? '380';
$ayYear = $settings['admission_year'] ?? '2026-27';
$admissionsUrl = $settings['admission_website'] ?? 'https://admissions.mgmu.ac.in';
?>
<!-- Page Hero Banner -->
<div class="page-hero mb-5" style="<?php echo $admissions_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{$admissions_bg}') center/cover no-repeat;" : ""; ?>">
    <div class="container position-relative z-1">
        <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill mb-2 fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">
            <i class="fa-solid fa-graduation-cap me-1"></i> Admissions Academic Session <?php echo htmlspecialchars($ayYear); ?>
        </span>
        <h1 class="font-display fw-extrabold mb-2 text-white">Admissions Portal &amp; AY <?php echo htmlspecialchars($ayYear); ?> Intake</h1>
        <p class="lead opacity-80 mb-3" style="font-size: 1.1rem; max-width: 750px;">Official intake capacity, approved seats for AY <?php echo htmlspecialchars($ayYear); ?>, eligibility guidelines, 6-step admission procedure, and live online tracking.</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Admissions</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">

    <!-- Official Document Download Banner -->
    <div class="card border-0 shadow-sm p-4 text-white mb-5 rounded-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-top: 4px solid var(--accent) !important;">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill mb-2 font-bold" style="font-size: 14px;">OFFICIAL PUBLICATIONS</span>
                <h4 class="fw-bold mb-1 font-display text-white">Download Official Brochure &amp; Leaflet (AY <?php echo htmlspecialchars($ayYear); ?>)</h4>
                <p class="small text-white-50 mb-0">Access complete official information regarding programs, eligibility criteria, department facilities, top recruiting companies, and campus life.</p>
            </div>
            <div class="col-lg-4 text-lg-end d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="<?php echo htmlspecialchars($brochureLink); ?>" target="_blank" class="btn btn-warning text-dark font-bold px-3.5 py-2 rounded-pill shadow-xs" style="font-size: 14px;">
                    <i class="fa-solid fa-file-pdf me-1.5"></i> Admission Brochure
                </a>
                <a href="<?php echo htmlspecialchars($leafletLink); ?>" target="_blank" class="btn btn-outline-light font-semibold px-3.5 py-2 rounded-pill" style="font-size: 14px;">
                    <i class="fa-solid fa-file-lines me-1.5"></i> Information Leaflet
                </a>
            </div>
        </div>
    </div>
    
    <!-- Official 6-Step Admission Procedure (from Brochure Page 7) -->
    <div class="card border-0 shadow-sm p-4 bg-white mb-5" style="border-radius: 18px; border: 1px solid #e2e8f0 !important;">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-1 rounded-pill mb-1 font-semibold" style="font-size: 14px;">
                    <i class="fa-solid fa-route me-1 text-warning"></i> Step-by-Step Flow
                </span>
                <h3 class="fw-bold text-primary-color mb-0 font-display">Admission Procedure</h3>
            </div>
            <a href="https://admissions.mgmu.ac.in" target="_blank" rel="noopener noreferrer" class="btn btn-warning text-dark font-bold px-4 py-2 rounded-pill shadow-xs">
                <i class="fa-solid fa-arrow-up-right-from-square me-1.5"></i> Apply at admissions.mgmu.ac.in
            </a>
        </div>

        <div class="row g-3 text-center">
            <!-- Step 1 -->
            <div class="col-md-4 col-lg-2">
                <div class="p-3 bg-light rounded-3 border h-100 hover-lift position-relative">
                    <span class="badge bg-primary text-white position-absolute top-0 start-50 translate-middle rounded-circle shadow-xs" style="width: 32px; height: 32px; line-height: 24px; font-size: 14px; font-weight: 800;">01</span>
                    <div class="my-3 text-primary" style="font-size: 28px;">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>
                    <h6 class="fw-bold text-dark font-display mb-1" style="font-size: 14px;">Fill Application</h6>
                    <small class="text-muted d-block" style="font-size: 14px; line-height: 1.4;">Fill online form on <strong>admissions.mgmu.ac.in</strong></small>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="col-md-4 col-lg-2">
                <div class="p-3 bg-light rounded-3 border h-100 hover-lift position-relative">
                    <span class="badge bg-primary text-white position-absolute top-0 start-50 translate-middle rounded-circle shadow-xs" style="width: 32px; height: 32px; line-height: 24px; font-size: 14px; font-weight: 800;">02</span>
                    <div class="my-3 text-primary" style="font-size: 28px;">
                        <i class="fa-solid fa-file-pen"></i>
                    </div>
                    <h6 class="fw-bold text-dark font-display mb-1" style="font-size: 14px;">Appear for CET</h6>
                    <small class="text-muted d-block" style="font-size: 14px; line-height: 1.4;">Appear for <strong>MGMU CET</strong> entrance test</small>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="col-md-4 col-lg-2">
                <div class="p-3 bg-light rounded-3 border h-100 hover-lift position-relative">
                    <span class="badge bg-primary text-white position-absolute top-0 start-50 translate-middle rounded-circle shadow-xs" style="width: 32px; height: 32px; line-height: 24px; font-size: 14px; font-weight: 800;">03</span>
                    <div class="my-3 text-primary" style="font-size: 28px;">
                        <i class="fa-solid fa-chart-column"></i>
                    </div>
                    <h6 class="fw-bold text-dark font-display mb-1" style="font-size: 14px;">Check Merit List</h6>
                    <small class="text-muted d-block" style="font-size: 14px; line-height: 1.4;">Check merit list displayed on candidate dashboard</small>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="col-md-4 col-lg-2">
                <div class="p-3 bg-light rounded-3 border h-100 hover-lift position-relative">
                    <span class="badge bg-primary text-white position-absolute top-0 start-50 translate-middle rounded-circle shadow-xs" style="width: 32px; height: 32px; line-height: 24px; font-size: 14px; font-weight: 800;">04</span>
                    <div class="my-3 text-primary" style="font-size: 28px;">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <h6 class="fw-bold text-dark font-display mb-1" style="font-size: 14px;">Document Verification</h6>
                    <small class="text-muted d-block" style="font-size: 14px; line-height: 1.4;">Verification &amp; physical submission of original documents</small>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="col-md-4 col-lg-2">
                <div class="p-3 bg-light rounded-3 border h-100 hover-lift position-relative">
                    <span class="badge bg-primary text-white position-absolute top-0 start-50 translate-middle rounded-circle shadow-xs" style="width: 32px; height: 32px; line-height: 24px; font-size: 14px; font-weight: 800;">05</span>
                    <div class="my-3 text-primary" style="font-size: 28px;">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>
                    <h6 class="fw-bold text-dark font-display mb-1" style="font-size: 14px;">Fee Processing</h6>
                    <small class="text-muted d-block" style="font-size: 14px; line-height: 1.4;">Payment and official processing of academic tuition fees</small>
                </div>
            </div>

            <!-- Step 6 -->
            <div class="col-md-4 col-lg-2">
                <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 h-100 hover-lift position-relative">
                    <span class="badge bg-success text-white position-absolute top-0 start-50 translate-middle rounded-circle shadow-xs" style="width: 32px; height: 32px; line-height: 24px; font-size: 14px; font-weight: 800;">06</span>
                    <div class="my-3 text-success" style="font-size: 28px;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h6 class="fw-bold text-success font-display mb-1" style="font-size: 14px;">Admission Processed</h6>
                    <small class="text-muted d-block" style="font-size: 14px; line-height: 1.4;">Admission successfully processed &amp; seat confirmed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Authoritative AY Admission Intake Section -->
    <div class="card border-0 shadow-sm p-4 bg-white mb-5" style="border-radius: 18px; border: 1px solid #e2e8f0 !important;">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill mb-1 font-bold" style="font-size: 14px;">
                    <i class="fa-solid fa-clipboard-list me-1"></i> AUTHORITATIVE SOURCE OF TRUTH
                </span>
                <h3 class="fw-bold text-primary-color mb-0 font-display">Academic Year <?php echo htmlspecialchars($ayYear); ?> Admission Intake</h3>
                <small class="text-muted">School of Engineering and Technology &bull; Approved Seat Matrix</small>
            </div>
            <div class="text-end">
                <span class="badge bg-primary px-3 py-2 rounded-pill font-bold" style="font-size: 14px;">
                    <i class="fa-solid fa-users me-1"></i> Total First Year Intake: <?php echo htmlspecialchars($totalIntake); ?> Seats
                </span>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <!-- Table 1: Intake (First Year) -->
            <div class="col-lg-7">
                <div class="card border rounded-3 overflow-hidden shadow-xs h-100">
                    <div class="card-header bg-primary text-white py-2.5 px-3 d-flex justify-content-between align-items-center">
                        <strong class="font-display small text-uppercase"><i class="fa-solid fa-list-ol me-1.5 text-warning"></i> Table 1: Intake (First Year) AY <?php echo htmlspecialchars($ayYear); ?></strong>
                        <span class="badge bg-warning text-dark font-bold">11 Programs</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%;">Sr. No</th>
                                    <th>Name of Program</th>
                                    <th class="text-center" style="width: 25%;">Category</th>
                                    <th class="text-end" style="width: 18%;">Intake</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold text-center">1</td>
                                    <td>B. Tech (I) CSE (ICBT)</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark font-semibold">Integrated (6-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">30</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">2</td>
                                    <td>B. Tech (I) CSE (Data Science)</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark font-semibold">Integrated (6-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">60</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">3</td>
                                    <td>B. Tech (I) Robotics &amp; AI</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark font-semibold">Integrated (6-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">60</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">4</td>
                                    <td>B. Tech (I) Civil Engineering</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark font-semibold">Integrated (6-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">30</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">5</td>
                                    <td>B. Tech (R) CSE (ICBT)</td>
                                    <td class="text-center"><span class="badge bg-primary text-white font-semibold">Regular (4-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">60</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">6</td>
                                    <td>B. Tech (R) CSE (Cyber Security)</td>
                                    <td class="text-center"><span class="badge bg-primary text-white font-semibold">Regular (4-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">30</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">7</td>
                                    <td>B. Tech (R) Electronics Engineering (VLSI)</td>
                                    <td class="text-center"><span class="badge bg-primary text-white font-semibold">Regular (4-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">30</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">8</td>
                                    <td>B. Tech (R) Electrical Engineering</td>
                                    <td class="text-center"><span class="badge bg-primary text-white font-semibold">Regular (4-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">30</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">9</td>
                                    <td>B. Tech (R) Robotics and Automation</td>
                                    <td class="text-center"><span class="badge bg-primary text-white font-semibold">Regular (4-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">30</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">10</td>
                                    <td>Advance Diploma in Industrial Safety (ADIS)</td>
                                    <td class="text-center"><span class="badge bg-secondary text-white font-semibold">Diploma (1-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">10</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">11</td>
                                    <td>Diploma in Fire Service Engineering</td>
                                    <td class="text-center"><span class="badge bg-secondary text-white font-semibold">Diploma (2-Yr)</span></td>
                                    <td class="text-end fw-bold text-primary-color">10</td>
                                </tr>
                                <tr class="table-primary fw-bold">
                                    <td colspan="3" class="text-end text-uppercase">Total Approved First Year Intake (AY 2026-27):</td>
                                    <td class="text-end text-dark fs-6 font-display">380</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Table 2: Intake (Direct Second Year) -->
            <div class="col-lg-5">
                <div class="card border rounded-3 overflow-hidden shadow-xs h-100">
                    <div class="card-header bg-dark text-white py-2.5 px-3 d-flex justify-content-between align-items-center">
                        <strong class="font-display small text-uppercase"><i class="fa-solid fa-angles-right me-1.5 text-warning"></i> Table 2: Intake (Direct Second Year)</strong>
                        <span class="badge bg-success text-white font-bold">Lateral Entry</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">Sr. No</th>
                                    <th>Name of Program</th>
                                    <th class="text-center">Program Track</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold text-center">1</td>
                                    <td>B. Tech (I) CSE (Data Science)</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark">Integrated (After HSC)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">2</td>
                                    <td>B. Tech (I) Robotics &amp; AI</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark">Integrated (After HSC)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">3</td>
                                    <td>B. Tech (I) CSE (ICBT)</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark">Integrated (After HSC)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">4</td>
                                    <td>B. Tech (I) Civil Engineering</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark">Integrated (After HSC)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">5</td>
                                    <td>B. Tech (R) CSE (ICBT)</td>
                                    <td class="text-center"><span class="badge bg-primary text-white">Regular (After Diploma/B.Sc.)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">6</td>
                                    <td>B. Tech (R) CSE (Cyber Security)</td>
                                    <td class="text-center"><span class="badge bg-primary text-white">Regular (After Diploma/B.Sc.)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">7</td>
                                    <td>B. Tech (R) Electronics Engineering (VLSI)</td>
                                    <td class="text-center"><span class="badge bg-primary text-white">Regular (After Diploma/B.Sc.)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">8</td>
                                    <td>B. Tech (R) Electrical Engineering</td>
                                    <td class="text-center"><span class="badge bg-primary text-white">Regular (After Diploma/B.Sc.)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">9</td>
                                    <td>B. Tech (R) Robotics and Automation</td>
                                    <td class="text-center"><span class="badge bg-primary text-white">Regular (After Diploma/B.Sc.)</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Eligibility Criteria Cards (from Brochure Page 7) -->
    <div class="row g-4 mb-5">
        <!-- B.Tech (4-Year after HSC) Eligibility -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius: 18px; border: 1px solid #e2e8f0 !important; border-top: 4px solid #2563eb !important;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px; background: rgba(37, 99, 235, 0.12); color: #2563eb; font-size: 20px;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-primary-color mb-0 font-display">B.Tech (4 Years)</h4>
                        <small class="text-muted font-semibold">Undergraduate Degree after Class 12 (HSC)</small>
                    </div>
                </div>
                
                <h6 class="fw-bold text-dark mt-2 mb-2 font-display"><i class="fa-solid fa-check-circle text-primary me-1.5"></i>First Year Eligibility:</h6>
                <ul class="list-unstyled mb-3 d-flex flex-column gap-2 small text-muted">
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-arrow-right text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                        <span><strong>HSC (12th) Passed</strong> with Physics and Mathematics as compulsory subjects.</span>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-arrow-right text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                        <span>Minimum <strong>45% marks for Open</strong> and minimum <strong>40% marks for Reserved Category</strong> candidates.</span>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-arrow-right text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                        <span>Candidate should have appeared in <strong>JEE (Mains) / MHT-CET / PERA-CET / MGMU-CET</strong> (with non-zero score).</span>
                    </li>
                </ul>

                <h6 class="fw-bold text-dark mt-2 mb-2 font-display"><i class="fa-solid fa-angles-right text-primary me-1.5"></i>Lateral Entry (Direct 2nd Year):</h6>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small text-muted">
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-arrow-right text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                        <span>Direct Second Year admission after <strong>Diploma Course in Engineering and Technology / B.Sc. Degree / D. Voc.</strong></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- B.Tech Integrated (6-Year after SSC) Eligibility -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius: 18px; border: 1px solid #e2e8f0 !important; border-top: 4px solid #d97706 !important;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px; background: rgba(245, 158, 11, 0.15); color: #d97706; font-size: 20px;">
                        <i class="fa-solid fa-school"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-primary-color mb-0 font-display">B.Tech (Integrated - 6 Years)</h4>
                        <small class="text-muted font-semibold">Dual Diploma + Degree Program after Class 10 (SSC)</small>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mt-2 mb-2 font-display"><i class="fa-solid fa-check-circle text-warning me-1.5"></i>First Year Eligibility:</h6>
                <ul class="list-unstyled mb-3 d-flex flex-column gap-2 small text-muted">
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-arrow-right text-warning mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                        <span><strong>SSC (10th) Passed</strong> from a recognized state/central educational board.</span>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-arrow-right text-warning mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                        <span>Minimum <strong>50% marks for Open</strong> and minimum <strong>45% marks for Reserved Category</strong> candidates.</span>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-arrow-right text-warning mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                        <span>Valid score in <strong>MGMU-CET</strong> entrance test.</span>
                    </li>
                </ul>

                <h6 class="fw-bold text-dark mt-2 mb-2 font-display"><i class="fa-solid fa-angles-right text-warning me-1.5"></i>Integrated Lateral Entry (Direct 2nd Year):</h6>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small text-muted">
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-arrow-right text-warning mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                        <span>Direct Second Year admission after <strong>HSC Passed</strong>.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Real-Time Seat Availability Tracker -->
    <div class="card border-0 shadow-sm p-4 bg-white mb-5" style="border-radius: 18px; border: 1px solid #e2e8f0 !important;">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold text-primary-color mb-1 font-display">
                    <i class="fa-solid fa-chair text-warning me-2"></i>Real-Time Seat Quota &amp; Intake Tracker
                </h4>
                <small class="text-muted">Live seat quota &amp; admission availability across engineering branches for AY 2026-27</small>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-2 px-3 rounded-pill font-semibold">
                <i class="fa-solid fa-bolt me-1 text-success"></i> Live Database Synced
            </span>
        </div>

        <div class="row g-3">
            <?php foreach ($seatMetrics as $metric): 
                $percent = ($metric['total_intake'] > 0) ? round(($metric['filled_seats'] / $metric['total_intake']) * 100) : 0;
                $statusBadgeClass = ($metric['admission_status'] === 'OPEN' && $metric['vacant_seats'] > 0) ? 'bg-success' : 'bg-danger';
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border p-3.5 bg-light rounded-3 shadow-xs hover-lift">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($metric['course_code']); ?></span>
                            <span class="badge <?php echo $statusBadgeClass; ?> rounded-pill"><?php echo $metric['admission_status']; ?></span>
                        </div>
                        <h6 class="fw-bold text-primary-color mb-2 font-display" style="font-size: 14px; line-height: 1.35;"><?php echo htmlspecialchars($metric['course_name']); ?></h6>
                        
                        <div class="d-flex justify-content-between small text-muted mb-1.5" style="font-size: 14px;">
                            <span>Approved: <strong><?php echo $metric['total_intake']; ?> Seats</strong></span>
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

    <!-- Tracking & Application Form Area -->
    <div class="row g-4">
        <!-- Sidebar instructions & Status Tracking -->
        <div class="col-lg-4">
            <!-- Track Status Form -->
            <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                <h5 class="fw-bold text-primary-color mb-2 font-display"><i class="fa-solid fa-magnifying-glass text-warning me-2"></i>Track Status</h5>
                <p class="small text-muted mb-3">Enter your unique Application Number (e.g. SOET2026xxxxxx) to view the live verification timeline.</p>
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
                    <div class="card border bg-light shadow-xs p-3.5 mt-3 rounded-3">
                        <small class="text-muted d-block mb-1 font-semibold" style="font-size: 14px;">APPLICATION RECORD</small>
                        <h6 class="fw-bold text-primary-color mb-0 font-display"><?php echo htmlspecialchars($searched_application['student_name']); ?></h6>
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
                                <span class="small <?php echo $step3_done ? 'font-semibold text-dark' : 'text-muted'; ?>">3. Merit Review &amp; Eligibility</span>
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
                            <div class="bg-white p-2.5 rounded small mt-2 border">
                                <strong>Admin Remarks:</strong> <?php echo htmlspecialchars($searched_application['remarks']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-3 pt-2">
                            <a href="<?php echo uploadUrl('admissions', $searched_application['document_path']); ?>" target="_blank" class="btn btn-xs btn-outline-secondary w-100 rounded-pill" style="font-size: 14px;">
                                <i class="fa-solid fa-file-arrow-down me-1"></i> View Uploaded Document
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Helpdesk Contact Card -->
            <div class="card border-0 shadow-sm p-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-radius: 16px; border-top: 3px solid var(--accent) !important;">
                <h5 class="fw-bold text-warning mb-2 font-display"><i class="fa-solid fa-headset me-2"></i>Admissions Counseling Cell</h5>
                <p class="small text-white-50 mb-3" style="line-height: 1.6;">For merit queries, CET guidelines, document verification, and hostel inquiries, get in touch with our counseling officers.</p>
                <hr class="border-secondary border-opacity-50 my-3">
                <p class="small text-white mb-2">
                    <i class="fa-solid fa-phone text-warning me-2"></i> <strong>Helpline:</strong> +91 9371714253
                </p>
                <p class="small text-white mb-2">
                    <i class="fa-solid fa-envelope text-warning me-2"></i> <strong>Email:</strong> soet@mgmu.ac.in
                </p>
                <p class="small text-white mb-0">
                    <i class="fa-solid fa-globe text-warning me-2"></i> <strong>Portal:</strong> <a href="https://admissions.mgmu.ac.in" target="_blank" class="text-warning text-decoration-none font-semibold">admissions.mgmu.ac.in</a>
                </p>
            </div>
        </div>

        <!-- Admission Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 18px; border: 1px solid #e2e8f0 !important;">
                <h4 class="fw-bold text-primary-color mb-2 font-display"><i class="fa-solid fa-user-plus text-warning me-2"></i>Online Admission Application</h4>
                <p class="text-muted small mb-4">Complete the application form below. Ensure all 10th &amp; 12th percentage details and marksheet attachments are authentic.</p>
                
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
                            <label class="form-label font-semibold">Degree / Diploma Program Preference *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select an Approved Program (AY 2026-27)</option>
                                <?php foreach ($courses as $c): 
                                    $sm = $seatMetricsById[$c['id']] ?? null;
                                    $vacant = $sm ? $sm['vacant_seats'] : $c['intake_capacity'];
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
                            <label class="form-label font-semibold">12th / Diploma / CET Aggregate %</label>
                            <input type="number" step="0.01" min="0" max="100" name="percentage_12th" class="form-control" placeholder="e.g. 82.30">
                            <div class="invalid-feedback">Enter valid percentage (0 - 100).</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Upload Marksheet / Supporting Certificate * (PDF/JPG/PNG up to 5MB)</label>
                            <input type="file" name="marksheet" class="form-control" required accept=".pdf,image/jpeg,image/png">
                            <div class="invalid-feedback">Please attach your marksheet document.</div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" name="submit_admission" class="btn btn-primary px-5 py-2.5 rounded-pill shadow-primary font-semibold">
                                <i class="fa-solid fa-cloud-arrow-up me-1.5"></i> Submit Admission Application
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
