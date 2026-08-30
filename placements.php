<?php
$page_title = "Placements Cell";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$placements_bg = !empty($settings['placements_hero_image']) ? uploadUrl('settings', $settings['placements_hero_image']) : null;
$placements = $cms->getPlacements();
?>

<!-- Breadcrumb Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $placements_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$placements_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">Training & Placements Cell</h1>
        <p class="lead opacity-75 mb-3">Stellar recruitment records, industry recruiters, and student achievements</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Placements</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Placements Stats -->
<div class="container mb-5">
    <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <i class="fa-solid fa-money-bill-trend-up fa-3x text-warning mb-3"></i>
                <h3 class="fw-bold text-primary-color">18.0 LPA</h3>
                <small class="text-muted text-uppercase fw-semibold">Highest Package</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <i class="fa-solid fa-chart-line fa-3x text-warning mb-3"></i>
                <h3 class="fw-bold text-primary-color">5.5 LPA</h3>
                <small class="text-muted text-uppercase fw-semibold">Average Package</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <i class="fa-solid fa-user-graduate fa-3x text-warning mb-3"></i>
                <h3 class="fw-bold text-primary-color">85%+</h3>
                <small class="text-muted text-uppercase fw-semibold">Students Placed</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <i class="fa-solid fa-building-columns fa-3x text-warning mb-3"></i>
                <h3 class="fw-bold text-primary-color">50+</h3>
                <small class="text-muted text-uppercase fw-semibold">Recruiting Companies</small>
            </div>
        </div>
    </div>
</div>

<!-- Placements cells overview and recruiters -->
<div class="bg-light py-5 mb-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold text-primary-color mb-3">Welcome to Training & Placement Cell</h2>
                <p class="text-muted">The Training and Placements Cell at SOET plays a crucial role in shaping the careers of our students. We offer complete support to graduating students by organizing guest lectures, soft skills training, mock interviews, and regular campus recruitment drives.</p>
                <p class="text-muted">We maintain long-term partnerships with leading companies in the industry. Through our industry collaboration programs, students get opportunities to work on live projects, internships, and dynamic technical challenges that make them standout candidates.</p>
            </div>
            
            <div class="col-lg-6">
                <h4 class="fw-bold text-primary-color mb-4">Our Top Recruiting Partners</h4>
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <div class="p-3 bg-white border rounded shadow-xs">
                            <h6 class="fw-bold mb-0 text-muted">TCS</h6>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white border rounded shadow-xs">
                            <h6 class="fw-bold mb-0 text-muted">Wipro</h6>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white border rounded shadow-xs">
                            <h6 class="fw-bold mb-0 text-muted">Infosys</h6>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white border rounded shadow-xs">
                            <h6 class="fw-bold mb-0 text-muted">Cognizant</h6>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white border rounded shadow-xs">
                            <h6 class="fw-bold mb-0 text-muted">Capgemini</h6>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white border rounded shadow-xs">
                            <h6 class="fw-bold mb-0 text-muted">Amazon</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Placed Students list -->
<div class="container mb-5">
    <div class="section-header">
        <h2>Our Placed Students</h2>
        <p>Meet some of our successful graduates placed in top global brands.</p>
    </div>
    
    <div class="row g-4">
        <?php if (empty($placements)): ?>
            <!-- Default mock records when database contains no manual entries -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 text-center">
                    <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto my-3" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-user-graduate fa-2x text-secondary"></i>
                    </div>
                    <h5 class="fw-bold text-primary-color mb-1">Rohan Gupta</h5>
                    <span class="placement-badge">B.Tech CSE - 2026</span>
                    <p class="mb-0 text-muted small">Placed at <strong>Amazon</strong></p>
                    <p class="fw-bold text-success small mb-0">Package: 12.5 LPA</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 text-center">
                    <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto my-3" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-user-graduate fa-2x text-secondary"></i>
                    </div>
                    <h5 class="fw-bold text-primary-color mb-1">Priya Sharma</h5>
                    <span class="placement-badge">B.Tech CSE - 2026</span>
                    <p class="mb-0 text-muted small">Placed at <strong>Infosys</strong></p>
                    <p class="fw-bold text-success small mb-0">Package: 5.5 LPA</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 text-center">
                    <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto my-3" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-user-graduate fa-2x text-secondary"></i>
                    </div>
                    <h5 class="fw-bold text-primary-color mb-1">Amit Sen</h5>
                    <span class="placement-badge">B.Tech ECE - 2026</span>
                    <p class="mb-0 text-muted small">Placed at <strong>Wipro</strong></p>
                    <p class="fw-bold text-success small mb-0">Package: 4.8 LPA</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($placements as $pl): ?>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 text-center h-100">
                        <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto my-3" style="width: 80px; height: 80px; overflow: hidden;">
                            <i class="fa-solid fa-user-graduate fa-2x text-secondary"></i>
                        </div>
                        <h5 class="fw-bold text-primary-color mb-1"><?php echo htmlspecialchars($pl['student_name']); ?></h5>
                        <span class="placement-badge">Batch: <?php echo $pl['placement_year']; ?></span>
                        <p class="mb-0 text-muted small">Placed at <strong><?php echo htmlspecialchars($pl['company_name']); ?></strong></p>
                        <p class="fw-bold text-success small mb-0">Package: <?php echo $pl['package_lpa']; ?> LPA</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
