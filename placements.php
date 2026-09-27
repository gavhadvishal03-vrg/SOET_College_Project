<?php
$page_title = "Placements Cell";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$placements_bg = !empty($settings['placements_hero_image']) ? uploadUrl('settings', $settings['placements_hero_image']) : null;
$placements = $cms->getPlacements();
?>

<!-- Page Hero Banner -->
<div class="page-hero mb-5">
    <div class="container position-relative z-1">
        <h1 class="font-display fw-extrabold mb-2 text-white">Training &amp; Placements Cell</h1>
        <p class="lead opacity-80 mb-3" style="font-size: 1.1rem; max-width: 650px;">Stellar recruitment records, top corporate hiring partners, and high-impact student placements.</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Placements</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Placements Stats -->
<div class="container mb-5">
    <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-warning bg-opacity-15 text-warning mx-auto mb-3" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-money-bill-trend-up fa-xl"></i>
                </div>
                <h3 class="fw-extrabold text-primary-color mb-0 font-display">18.0 LPA</h3>
                <small class="text-muted text-uppercase font-semibold" style="font-size: 11px;">Highest Package</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-chart-line fa-xl"></i>
                </div>
                <h3 class="fw-extrabold text-primary-color mb-0 font-display">5.5 LPA</h3>
                <small class="text-muted text-uppercase font-semibold" style="font-size: 11px;">Average Package</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-success bg-opacity-15 text-success mx-auto mb-3" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-user-graduate fa-xl"></i>
                </div>
                <h3 class="fw-extrabold text-success mb-0 font-display">85%+</h3>
                <small class="text-muted text-uppercase font-semibold" style="font-size: 11px;">Students Placed</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-info bg-opacity-15 text-info mx-auto mb-3" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-building-columns fa-xl"></i>
                </div>
                <h3 class="fw-extrabold text-primary-color mb-0 font-display">50+</h3>
                <small class="text-muted text-uppercase font-semibold" style="font-size: 11px;">Recruiting Partners</small>
            </div>
        </div>
    </div>
</div>

<!-- Placements cells overview and recruiters -->
<div class="bg-light py-5 mb-5 border-top border-bottom border-light">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-25 px-3 py-1.5 rounded-pill mb-3 font-semibold" style="font-size: 12px;">
                    <i class="fa-solid fa-briefcase text-warning me-1.5"></i> Career &amp; Corporate Excellence
                </div>
                <h2 class="fw-bold text-primary-color mb-3 font-display" style="font-size: 2.2rem;">Training &amp; Corporate Relations</h2>
                <p class="text-muted" style="line-height: 1.75;">The Training and Placements Cell at SOET plays a crucial role in shaping the careers of our students. We offer comprehensive preparation by organizing expert guest lectures, soft skills bootcamps, mock coding interviews, and nationwide campus hiring drives.</p>
                <p class="text-muted" style="line-height: 1.75;">We maintain long-term partnerships with leading tech enterprises. Through our industry collaboration programs, students work on live industry projects, international hackathons, and sponsored internships.</p>
            </div>
            
            <div class="col-lg-6">
                <h4 class="fw-bold text-primary-color mb-4 font-display"><i class="fa-solid fa-handshake text-warning me-2"></i>Key Corporate Recruiters</h4>
                <div class="row g-3 text-center">
                    <?php 
                    $recruiters = ['TCS', 'Wipro', 'Infosys', 'Cognizant', 'Capgemini', 'Amazon', 'L&T Infotech', 'Tech Mahindra', 'Accenture'];
                    foreach ($recruiters as $rec):
                    ?>
                        <div class="col-4">
                            <div class="p-3 bg-white border rounded-3 shadow-xs hover-lift">
                                <h6 class="fw-bold mb-0 text-dark font-display" style="font-size: 14px;"><?php echo $rec; ?></h6>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Placed Students list -->
<div class="container mb-5">
    <div class="section-header">
        <h2>Our Placed Achievers</h2>
        <p>Meet some of our successful graduates placed in top global brands.</p>
    </div>
    
    <div class="row g-4">
        <?php if (empty($placements)): ?>
            <!-- Default mock records when database contains no manual entries -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center bg-white hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                    <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto my-3 border" style="width: 76px; height: 76px;">
                        <i class="fa-solid fa-user-graduate fa-2x text-warning"></i>
                    </div>
                    <h5 class="fw-bold text-primary-color mb-1 font-display">Rohan Gupta</h5>
                    <span class="placement-badge">B.Tech CSE &bull; Batch 2026</span>
                    <p class="mb-1 text-muted small">Placed at <strong class="text-dark">Amazon</strong></p>
                    <span class="badge bg-success bg-opacity-15 text-success rounded-pill px-3 py-1 font-semibold">Package: 12.5 LPA</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center bg-white hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                    <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto my-3 border" style="width: 76px; height: 76px;">
                        <i class="fa-solid fa-user-graduate fa-2x text-warning"></i>
                    </div>
                    <h5 class="fw-bold text-primary-color mb-1 font-display">Priya Sharma</h5>
                    <span class="placement-badge">B.Tech CSE &bull; Batch 2026</span>
                    <p class="mb-1 text-muted small">Placed at <strong class="text-dark">Infosys</strong></p>
                    <span class="badge bg-success bg-opacity-15 text-success rounded-pill px-3 py-1 font-semibold">Package: 5.5 LPA</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center bg-white hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                    <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto my-3 border" style="width: 76px; height: 76px;">
                        <i class="fa-solid fa-user-graduate fa-2x text-warning"></i>
                    </div>
                    <h5 class="fw-bold text-primary-color mb-1 font-display">Amit Sen</h5>
                    <span class="placement-badge">B.Tech ECE &bull; Batch 2026</span>
                    <p class="mb-1 text-muted small">Placed at <strong class="text-dark">Wipro</strong></p>
                    <span class="badge bg-success bg-opacity-15 text-success rounded-pill px-3 py-1 font-semibold">Package: 4.8 LPA</span>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($placements as $pl): ?>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3.5 text-center h-100 bg-white hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                        <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto my-3 border" style="width: 72px; height: 72px; overflow: hidden;">
                            <?php if (!empty($pl['student_image'])): ?>
                                <img src="<?php echo uploadUrl('placements', $pl['student_image']); ?>" alt="<?php echo htmlspecialchars($pl['student_name']); ?>" class="w-100 h-100" style="object-fit: cover;">
                            <?php else: ?>
                                <i class="fa-solid fa-user-graduate fa-2x text-warning"></i>
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold text-primary-color mb-1 font-display"><?php echo htmlspecialchars($pl['student_name']); ?></h5>
                        <span class="placement-badge">Batch: <?php echo $pl['placement_year']; ?></span>
                        <p class="mb-1 text-muted small">Placed at <strong class="text-dark"><?php echo htmlspecialchars($pl['company_name']); ?></strong></p>
                        <span class="badge bg-success bg-opacity-15 text-success rounded-pill px-3 py-1 font-semibold">Package: <?php echo $pl['package_lpa']; ?> LPA</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
