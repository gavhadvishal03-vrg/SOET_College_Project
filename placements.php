<?php
/**
 * Public Training & Placements Controller & View
 *
 * Showcases university career outcomes, corporate partnerships, and training:
 * - Placement milestones (placed student count, top packages, active hiring partners)
 * - Prominent corporate recruiters marquee / gallery (IT, Core, Automotive, Electronics)
 * - Student placement hall of fame / placed student cards
 * - Industrial plant visits (e.g. Liebherr, manufacturing hubs) & internships
 */
$page_title = "Placements & Corporate Collaborations";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$placements_bg = !empty($settings['placements_hero_image']) ? uploadUrl('settings', $settings['placements_hero_image']) : uploadUrl('gallery', 'visit_liebherr_industry.jpg');
$placements = $cms->getPlacements();
$departments = $cms->getDepartments();
?>

<!-- Page Hero Banner -->
<div class="page-hero mb-5" style="<?php echo $placements_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{$placements_bg}') center/cover no-repeat;" : ""; ?>">
    <div class="container position-relative z-1">
        <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill mb-2 fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">
            <i class="fa-solid fa-briefcase me-1"></i> Industry Collaborations &amp; Careers
        </span>
        <h1 class="font-display fw-extrabold mb-2 text-white">Training, Placements &amp; Corporate Partnerships</h1>
        <p class="lead opacity-80 mb-3" style="font-size: 1.1rem; max-width: 700px;">Strong industry tie-ups, prestigious global recruiting partners, hands-on industrial plant visits, and career-launching campus placements.</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Placements &amp; Collaborations</li>
            </ol>
        </nav>
    </div>
</div>

<!-- 1. PLACEMENT STATISTICS (Accurate & Verified) -->
<div class="container mb-5">
    <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white hover-lift rounded-4" style="border: 1px solid #e2e8f0 !important;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-3 shadow-xs" 
                     style="width: 56px; height: 56px; background: rgba(245, 158, 11, 0.15); color: #d97706; font-size: 22px; border: 1px solid rgba(245, 158, 11, 0.3);">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <h3 class="fw-extrabold text-primary-color mb-0 font-display"><?php echo htmlspecialchars($settings['placements_stat1_val'] ?? '12+'); ?></h3>
                <small class="text-muted text-uppercase font-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($settings['placements_stat1_label'] ?? '2026 Batch Placed'); ?></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white hover-lift rounded-4" style="border: 1px solid #e2e8f0 !important;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-3 shadow-xs" 
                     style="width: 56px; height: 56px; background: rgba(59, 130, 246, 0.15); color: #2563eb; font-size: 22px; border: 1px solid rgba(59, 130, 246, 0.3);">
                    <i class="fa-solid fa-building-circle-check"></i>
                </div>
                <h3 class="fw-extrabold text-primary-color mb-0 font-display"><?php echo htmlspecialchars($settings['placements_stat2_val'] ?? '7+'); ?></h3>
                <small class="text-muted text-uppercase font-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($settings['placements_stat2_label'] ?? 'Active Recruiters (2026)'); ?></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white hover-lift rounded-4" style="border: 1px solid #e2e8f0 !important;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-3 shadow-xs" 
                     style="width: 56px; height: 56px; background: rgba(16, 185, 129, 0.15); color: #059669; font-size: 22px; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <h3 class="fw-extrabold text-success mb-0 font-display"><?php echo htmlspecialchars($settings['placements_stat3_val'] ?? '15+'); ?></h3>
                <small class="text-muted text-uppercase font-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($settings['placements_stat3_label'] ?? 'Industry MoUs & Partners'); ?></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white hover-lift rounded-4" style="border: 1px solid #e2e8f0 !important;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-3 shadow-xs" 
                     style="width: 56px; height: 56px; background: rgba(14, 165, 233, 0.15); color: #0284c7; font-size: 22px; border: 1px solid rgba(14, 165, 233, 0.3);">
                    <i class="fa-solid fa-medal"></i>
                </div>
                <h3 class="fw-extrabold text-primary-color mb-0 font-display"><?php echo htmlspecialchars($settings['placements_stat4_val'] ?? '100%'); ?></h3>
                <small class="text-muted text-uppercase font-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($settings['placements_stat4_label'] ?? 'Verified Placement Track'); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- 2. PRIME RECRUITERS & CORPORATE HIRERS (from Database & Official Brochure) -->
<div class="container mb-5">
    <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4" style="border: 1px solid #e2e8f0 !important;">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-1.5 rounded-pill mb-2 font-semibold" style="font-size: 14px;">
                <i class="fa-solid fa-briefcase me-1"></i> Top Corporate Hirers
            </span>
            <h2 class="font-display fw-bold text-primary-color">Campus Placement Prime Recruiters</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">Our students are recruited by global Fortune 500 multinationals and premier technology companies featured in the official SOET brochure and placement leaflets.</p>
        </div>

        <?php 
        $primeRecruiters = $cms->getPlacementRecruiters(true);
        if (empty($primeRecruiters)) {
            $primeRecruiters = [
                ['name' => 'TATA Consultancy Services (TCS)', 'category' => 'IT Multinational', 'logo_image' => 'tcs.png', 'icon' => 'fa-solid fa-laptop-code'],
                ['name' => 'Infosys', 'category' => 'IT & Software', 'logo_image' => 'infosys.png', 'icon' => 'fa-solid fa-network-wired'],
                ['name' => 'Deloitte', 'category' => 'Consulting', 'logo_image' => 'deloitte.png', 'icon' => 'fa-solid fa-chart-pie'],
                ['name' => 'IBM', 'category' => 'Cloud & AI', 'logo_image' => 'ibm.png', 'icon' => 'fa-solid fa-server'],
                ['name' => 'Tech Mahindra', 'category' => 'Telecom & IT', 'logo_image' => 'tech_mahindra.png', 'icon' => 'fa-solid fa-microchip'],
                ['name' => 'Cognizant', 'category' => 'IT Services', 'logo_image' => 'cognizant.png', 'icon' => 'fa-solid fa-brain'],
                ['name' => 'LIEBHERR', 'category' => 'Heavy Engineering', 'logo_image' => 'liebherr.png', 'icon' => 'fa-solid fa-truck-monster'],
                ['name' => 'Škoda Auto Volkswagen', 'category' => 'Automotive OEM', 'logo_image' => 'skoda.png', 'icon' => 'fa-solid fa-car-side'],
                ['name' => 'Adobe', 'category' => 'Product Software', 'logo_image' => 'adobe.png', 'icon' => 'fa-solid fa-cube'],
                ['name' => 'BOSCH', 'category' => 'Automotive & IoT', 'logo_image' => 'bosch.png', 'icon' => 'fa-solid fa-gears'],
                ['name' => 'Atlas Copco', 'category' => 'Industrial Machinery', 'logo_image' => 'atlas_copco.png', 'icon' => 'fa-solid fa-screwdriver-wrench'],
                ['name' => 'Wipro', 'category' => 'IT & Cloud', 'logo_image' => 'wipro.png', 'icon' => 'fa-solid fa-cloud-arrow-up'],
                ['name' => 'Reliance Jio', 'category' => 'Telecom & 5G', 'logo_image' => 'reliance_jio.png', 'icon' => 'fa-solid fa-tower-broadcast'],
                ['name' => 'IFB Industries', 'category' => 'Manufacturing', 'logo_image' => 'ifb.png', 'icon' => 'fa-solid fa-bolt'],
                ['name' => 'Endress+Hauser', 'category' => 'Process Automation', 'logo_image' => 'endress_hauser.png', 'icon' => 'fa-solid fa-industry'],
                ['name' => 'Goodyear', 'category' => 'Tyres & Mobility', 'logo_image' => 'goodyear.png', 'icon' => 'fa-solid fa-circle-notch'],
                ['name' => 'Allied Blenders', 'category' => 'Manufacturing', 'logo_image' => 'allied_blenders.png', 'icon' => 'fa-solid fa-building'],
                ['name' => 'Mahindra', 'category' => 'Automotive & Tech', 'logo_image' => 'mahindra.png', 'icon' => 'fa-solid fa-car'],
                ['name' => 'Atos Syntel', 'category' => 'IT Services', 'logo_image' => 'atos_syntel.png', 'icon' => 'fa-solid fa-network-wired'],
                ['name' => 'Toyota Tsusho Pvt Ltd', 'category' => 'Automotive & Supply Chain', 'logo_image' => 'toyota_tsusho.svg', 'icon' => 'fa-solid fa-car'],
                ['name' => 'Expert Global Solutions', 'category' => 'Engineering Tech', 'logo_image' => 'expert_global.svg', 'icon' => 'fa-solid fa-gears'],
                ['name' => 'Quess Corp Limited', 'category' => 'Enterprise Services', 'logo_image' => 'quess_corp.svg', 'icon' => 'fa-solid fa-briefcase'],
                ['name' => 'Persevex', 'category' => 'Digital Solutions', 'logo_image' => 'persevex.svg', 'icon' => 'fa-solid fa-code'],
                ['name' => 'LearnFlu', 'category' => 'EdTech Solutions', 'logo_image' => 'learnflu.svg', 'icon' => 'fa-solid fa-graduation-cap']
            ];
        }
        ?>

        <div class="row g-3 g-md-4 justify-content-center">
            <?php foreach ($primeRecruiters as $rec): ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="recruiter-logo-card">
                        <div class="recruiter-logo-stage">
                            <?php if (!empty($rec['logo_image'])): ?>
                                <img src="<?php echo uploadUrl('recruiters', $rec['logo_image']); ?>" alt="<?php echo htmlspecialchars($rec['name']); ?>" class="recruiter-logo-img" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 44px; height: 44px; background: rgba(30, 58, 138, 0.08); color: #1e3a8a; font-size: 18px;">
                                    <i class="<?php echo htmlspecialchars($rec['icon'] ?: 'fa-solid fa-briefcase'); ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="recruiter-brand-name"><?php echo htmlspecialchars($rec['name']); ?></div>
                        <?php if (!empty($rec['category'])): ?>
                            <span class="recruiter-category-tag"><?php echo htmlspecialchars($rec['category']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 3. PLACED STUDENTS DATA (Official Placements Record – 2026 Batch) -->
<div class="container mb-5">
    <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4" style="border: 1px solid #e2e8f0 !important;">
        <!-- Page Section Heading -->
        <div class="text-center mb-4">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-1.5 rounded-pill mb-2 font-semibold" style="font-size: 14px;">
                <i class="fa-solid fa-award me-1"></i> Campus Placements Track Record
            </span>
            <h2 class="font-display fw-bold text-primary-color mb-1">Official Placements Record</h2>
            <h4 class="font-display text-muted mb-2" style="font-size: 1.25rem; font-weight: 700;">Placed Student Data &bull; 2026 Batch</h4>
            <p class="text-muted mx-auto mb-0" style="max-width: 680px;">Verified career appointments and recruitment offers secured by SOET engineering graduates across premier global technology corporations and manufacturing leaders.</p>
        </div>

        <?php 
        // Placement Statistics Calculation
        $totalPlaced = count($placements);
        $highestPackage = 0.0;
        $totalPackageSum = 0.0;
        $distinctCompanies = [];
        $distinctDepartments = [];
        $distinctRoles = [];

        foreach ($placements as $p) {
            $pkg = (float)($p['package_lpa'] ?? 0);
            if ($pkg > $highestPackage) {
                $highestPackage = $pkg;
            }
            $totalPackageSum += $pkg;
            if (!empty($p['company_name'])) {
                $cName = trim($p['company_name']);
                $distinctCompanies[$cName] = ($distinctCompanies[$cName] ?? 0) + 1;
            }
            if (!empty($p['department_name'])) {
                $dName = trim($p['department_name']);
                $distinctDepartments[$dName] = ($distinctDepartments[$dName] ?? 0) + 1;
            }
            if (!empty($p['job_role'])) {
                $rName = trim($p['job_role']);
                $distinctRoles[$rName] = ($distinctRoles[$rName] ?? 0) + 1;
            }
        }
        $avgPackage = $totalPlaced > 0 ? ($totalPackageSum / $totalPlaced) : 0.0;
        $totalCompaniesCount = count($distinctCompanies);
        ?>

        <!-- Top Placement Statistics Bar -->
        <div class="row g-3 mb-5">
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-center p-3 border-0 shadow-sm hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-2 shadow-xs" style="width: 48px; height: 48px; background: rgba(37, 99, 235, 0.12); color: #2563eb; font-size: 20px;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <h3 class="fw-extrabold text-primary mb-0 font-display"><?php echo $totalPlaced; ?>+</h3>
                    <small class="text-muted font-semibold" style="font-size: 14px;">Total Placed Students</small>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-center p-3 border-0 shadow-sm hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-2 shadow-xs" style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.12); color: #d97706; font-size: 20px;">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <h3 class="fw-extrabold text-warning mb-0 font-display">₹<?php echo number_format($highestPackage, 2); ?> <span style="font-size: 15px; font-weight: 600;">LPA</span></h3>
                    <small class="text-muted font-semibold" style="font-size: 14px;">Highest Package</small>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-center p-3 border-0 shadow-sm hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-2 shadow-xs" style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.12); color: #059669; font-size: 20px;">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3 class="fw-extrabold text-success mb-0 font-display">₹<?php echo number_format($avgPackage, 2); ?> <span style="font-size: 15px; font-weight: 600;">LPA</span></h3>
                    <small class="text-muted font-semibold" style="font-size: 14px;">Average Package</small>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-center p-3 border-0 shadow-sm hover-lift" style="border-radius: 16px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-2 shadow-xs" style="width: 48px; height: 48px; background: rgba(124, 58, 237, 0.12); color: #7c3aed; font-size: 20px;">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <h3 class="fw-extrabold mb-0 font-display" style="color: #7c3aed;"><?php echo $totalCompaniesCount; ?>+</h3>
                    <small class="text-muted font-semibold" style="font-size: 14px;">Total Companies</small>
                </div>
            </div>
        </div>

        <!-- Filter & Live Search Bar (Matching Faculty Module Exact UI Pattern) -->
        <div class="row g-3 mb-3 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="input-group shadow-xs">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="placedSearchInput" class="form-control border-start-0 ps-0" placeholder="Search by student name, company, role, or department...">
                </div>
            </div>
            <div class="col-md-6 col-lg-7">
                <div class="card border-0 shadow-xs p-2 bg-white d-flex flex-row justify-content-md-end align-items-center gap-1.5 flex-wrap rounded-3" style="border: 1px solid #e2e8f0 !important;">
                    <span class="small font-semibold text-muted me-1"><i class="fa-solid fa-filter text-warning me-1"></i> Dept:</span>
                    <button type="button" class="btn btn-xs btn-primary dept-filter-pill rounded-pill px-3 py-1 font-semibold" data-dept="" style="font-size: 14px;">All (<?php echo $totalPlaced; ?>)</button>
                    <?php foreach ($departments as $dept): 
                        $deptMatches = 0;
                        foreach ($placements as $p) {
                            if (($p['department_id'] ?? 0) == $dept['id'] || (isset($p['department_name']) && stripos($p['department_name'], $dept['name']) !== false) || (isset($p['department_code']) && strcasecmp($p['department_code'], $dept['code']) === 0)) {
                                $deptMatches++;
                            }
                        }
                    ?>
                        <button type="button" class="btn btn-xs btn-outline-primary dept-filter-pill rounded-pill px-3 py-1 font-semibold" data-dept="<?php echo htmlspecialchars(strtolower($dept['name'])); ?>" data-code="<?php echo htmlspecialchars(strtolower($dept['code'])); ?>" style="font-size: 14px;">
                            <?php echo htmlspecialchars($dept['code']); ?> (<?php echo $deptMatches; ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Secondary Quick Filters (Company, Role, Package CTC, & Reset) -->
        <div class="row g-2 mb-4 align-items-center justify-content-between">
            <div class="col-12 col-md-auto d-flex align-items-center gap-2 flex-wrap">
                <span class="small font-semibold text-muted"><i class="fa-solid fa-sliders text-primary me-1"></i> Filter By:</span>
                <select id="filterCompanySelect" class="form-select form-select-sm rounded-pill shadow-xs" style="width: auto; min-width: 140px; font-size: 14px; font-family: 'Times New Roman', Times, serif;">
                    <option value="">All Companies</option>
                    <?php foreach (array_keys($distinctCompanies) as $compName): ?>
                        <option value="<?php echo htmlspecialchars($compName); ?>"><?php echo htmlspecialchars($compName); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterRoleSelect" class="form-select form-select-sm rounded-pill shadow-xs" style="width: auto; min-width: 140px; font-size: 14px; font-family: 'Times New Roman', Times, serif;">
                    <option value="">All Roles</option>
                    <?php foreach (array_keys($distinctRoles) as $roleName): ?>
                        <option value="<?php echo htmlspecialchars($roleName); ?>"><?php echo htmlspecialchars($roleName); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterPackageSelect" class="form-select form-select-sm rounded-pill shadow-xs" style="width: auto; min-width: 140px; font-size: 14px; font-family: 'Times New Roman', Times, serif;">
                    <option value="">All Packages</option>
                    <option value="above_5">Above ₹5.00 LPA</option>
                    <option value="4_to_5">₹4.00 - ₹5.00 LPA</option>
                    <option value="below_4">Below ₹4.00 LPA</option>
                </select>
                <button type="button" id="resetPlacementFilters" class="btn btn-sm btn-outline-secondary rounded-pill px-3 font-semibold" style="font-size: 14px;" title="Reset all filters">
                    <i class="fa-solid fa-rotate-right me-1"></i> Reset
                </button>
            </div>
            <div class="col-12 col-md-auto text-muted small" style="font-size: 14px;">
                <i class="fa-solid fa-id-card-clip text-primary me-1"></i>
                Showing <strong id="visibleStudentCount" class="text-dark"><?php echo $totalPlaced; ?></strong> of <strong class="text-dark"><?php echo $totalPlaced; ?></strong> placed students
            </div>
        </div>

        <!-- Student Placement Cards Grid (Single Source of Truth: Faculty Card UI/UX Reference) -->
        <div class="row g-4" id="placedCardsContainer">
            <?php if (empty($placements)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fa-solid fa-user-graduate fa-3x mb-3 text-secondary opacity-50"></i>
                    <p>No placement records available at this time.</p>
                </div>
            <?php else: ?>
                <?php foreach ($placements as $pl): 
                    $deptDisplay = !empty($pl['department_code']) ? $pl['department_code'] : ($pl['department_name'] ?? 'CSE');
                ?>
                    <div class="col-md-6 col-lg-3 placement-card-col"
                         data-name="<?php echo strtolower(htmlspecialchars($pl['student_name'])); ?>"
                         data-company="<?php echo strtolower(htmlspecialchars($pl['company_name'])); ?>"
                         data-role="<?php echo strtolower(htmlspecialchars($pl['job_role'] ?? 'software engineer')); ?>"
                         data-dept="<?php echo strtolower(htmlspecialchars($pl['department_name'] ?? '')); ?>"
                         data-code="<?php echo strtolower(htmlspecialchars($pl['department_code'] ?? '')); ?>"
                         data-package="<?php echo (float)$pl['package_lpa']; ?>"
                         data-batch="<?php echo strtolower(htmlspecialchars($pl['batch'] ?? '2026 Batch')); ?>">
                        <div class="card h-100 text-center p-3.5 border-0 shadow-sm hover-lift" 
                             style="border-radius: 16px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                            
                            <!-- Student Photo Placement (Exact Faculty Photo Dimensions: 110px x 110px) -->
                            <div class="text-center my-3">
                                <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle shadow-xs" 
                                     style="width: 110px; height: 110px; overflow: hidden; border: 3px solid #e2e8f0;">
                                    <?php if (!empty($pl['student_image'])): ?>
                                        <img src="<?php echo uploadUrl('placements', $pl['student_image']); ?>" alt="<?php echo htmlspecialchars($pl['student_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center w-100 h-100 bg-primary bg-opacity-10 text-primary fw-bold" style="font-size: 32px; font-family: 'Times New Roman', Times, serif;">
                                            <?php echo strtoupper(substr(trim($pl['student_name']), 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body p-1 d-flex flex-column">
                                <h5 class="fw-bold text-primary-color mb-1 font-display"><?php echo htmlspecialchars($pl['student_name']); ?></h5>
                                <div>
                                    <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill mb-2"><?php echo htmlspecialchars($pl['job_role'] ?? 'Software Engineer'); ?></span>
                                </div>
                                <span class="d-block text-secondary small font-semibold mb-3"><?php echo htmlspecialchars($pl['department_name'] ?? 'Computer Science & Engineering'); ?></span>
                                
                                <hr class="my-2 border-light">
                                
                                <div class="text-start text-muted small my-3 flex-grow-1" style="font-size: 14px; line-height: 1.6;">
                                    <p class="mb-1 d-flex justify-content-between align-items-center">
                                        <span><strong class="text-dark">Company:</strong></span>
                                        <span class="badge bg-light text-dark border rounded-pill font-semibold"><?php echo htmlspecialchars($pl['company_name']); ?></span>
                                    </p>
                                    <p class="mb-1 d-flex justify-content-between align-items-center">
                                        <span><strong class="text-dark">Role:</strong></span>
                                        <span class="text-dark font-semibold text-truncate ms-2" style="max-width: 130px;" title="<?php echo htmlspecialchars($pl['job_role'] ?? 'Software Engineer'); ?>"><?php echo htmlspecialchars($pl['job_role'] ?? 'Software Engineer'); ?></span>
                                    </p>
                                    <p class="mb-1 d-flex justify-content-between align-items-center">
                                        <span><strong class="text-dark">Package:</strong></span>
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 rounded-pill font-bold" style="font-size: 14px;">₹<?php echo number_format($pl['package_lpa'], 2); ?> LPA</span>
                                    </p>
                                    <p class="mb-1 d-flex justify-content-between align-items-center">
                                        <span><strong class="text-dark">Department:</strong></span>
                                        <span class="text-secondary font-semibold"><?php echo htmlspecialchars($deptDisplay); ?></span>
                                    </p>
                                </div>

                                <div class="pt-2 border-top border-light text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill font-bold px-3 py-1" style="font-size: 14px;">
                                        <i class="fa-solid fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($pl['batch'] ?? '2026 Batch'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- No Search Results Alert Box -->
            <div id="noPlacementMatch" class="col-12 text-center py-5 d-none">
                <i class="fa-solid fa-magnifying-glass fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="text-muted">No placed students found matching your search.</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 font-semibold rounded-pill px-3" onclick="document.getElementById('resetPlacementFilters').click();">Clear Search &amp; Filters</button>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Placement Live Search & Filter Handler -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('placedSearchInput');
    const deptPills = document.querySelectorAll('.dept-filter-pill');
    const compSelect = document.getElementById('filterCompanySelect');
    const roleSelect = document.getElementById('filterRoleSelect');
    const pkgSelect = document.getElementById('filterPackageSelect');
    const resetBtn = document.getElementById('resetPlacementFilters');
    const cards = document.querySelectorAll('.placement-card-col');
    const visibleCounter = document.getElementById('visibleStudentCount');
    const noMatchBox = document.getElementById('noPlacementMatch');

    let activeDept = '';

    deptPills.forEach(pill => {
        pill.addEventListener('click', function(e) {
            e.preventDefault();
            deptPills.forEach(p => {
                p.classList.remove('btn-primary');
                p.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
            activeDept = (this.getAttribute('data-dept') || '').toLowerCase().trim();
            applyFilters();
        });
    });

    function applyFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedComp = compSelect ? compSelect.value.toLowerCase().trim() : '';
        const selectedRole = roleSelect ? roleSelect.value.toLowerCase().trim() : '';
        const selectedPkg = pkgSelect ? pkgSelect.value : '';

        let matchCount = 0;

        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const comp = card.getAttribute('data-company') || '';
            const role = card.getAttribute('data-role') || '';
            const dept = card.getAttribute('data-dept') || '';
            const code = card.getAttribute('data-code') || '';
            const pkg = parseFloat(card.getAttribute('data-package') || '0');

            // 1. Text Search
            const matchesQuery = !query || name.includes(query) || comp.includes(query) || role.includes(query) || dept.includes(query) || code.includes(query);

            // 2. Department Pill
            const matchesDept = !activeDept || dept.includes(activeDept) || code.includes(activeDept);

            // 3. Company
            const matchesComp = !selectedComp || comp.includes(selectedComp);

            // 4. Role
            const matchesRole = !selectedRole || role.includes(selectedRole);

            // 5. Package Range
            let matchesPkg = true;
            if (selectedPkg === 'above_5') {
                matchesPkg = pkg >= 5.0;
            } else if (selectedPkg === '4_to_5') {
                matchesPkg = pkg >= 4.0 && pkg < 5.0;
            } else if (selectedPkg === 'below_4') {
                matchesPkg = pkg < 4.0;
            }

            if (matchesQuery && matchesDept && matchesComp && matchesRole && matchesPkg) {
                card.style.display = '';
                matchCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleCounter) {
            visibleCounter.textContent = matchCount;
        }

        if (noMatchBox) {
            if (matchCount === 0 && cards.length > 0) {
                noMatchBox.classList.remove('d-none');
            } else {
                noMatchBox.classList.add('d-none');
            }
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (compSelect) compSelect.addEventListener('change', applyFilters);
    if (roleSelect) roleSelect.addEventListener('change', applyFilters);
    if (pkgSelect) pkgSelect.addEventListener('change', applyFilters);

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (compSelect) compSelect.value = '';
            if (roleSelect) roleSelect.value = '';
            if (pkgSelect) pkgSelect.value = '';
            deptPills.forEach((p, idx) => {
                if (idx === 0) {
                    p.classList.remove('btn-outline-primary');
                    p.classList.add('btn-primary');
                } else {
                    p.classList.remove('btn-primary');
                    p.classList.add('btn-outline-primary');
                }
            });
            activeDept = '';
            applyFilters();
        });
    }
});
</script>

<!-- 4. STRATEGIC INDUSTRY & ACADEMIC COLLABORATIONS (from Brochure Page 8) -->
<div class="bg-light py-5 mb-5 border-top border-bottom border-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-30 px-3 py-1.5 rounded-pill mb-2 font-semibold" style="font-size: 14px;">
                <i class="fa-solid fa-handshake text-warning me-1"></i> Strategic Partnerships &amp; MoUs
            </span>
            <h2 class="font-display fw-bold text-primary-color">Industry &amp; Academic Collaborations</h2>
            <p class="text-muted mx-auto" style="max-width: 700px;">SOET actively partners with leading automotive, manufacturing, IT leaders, and premier research institutions to deliver sponsored training, internships, and live joint labs.</p>
        </div>

        <?php 
        $collaborations = [
            [
                'name' => 'Škoda Auto Volkswagen India',
                'desc' => 'Automotive engineering, joint mechatronics labs & EV testing projects',
                'logo' => 'skoda_volkswagen.png',
                'category' => 'Automotive OEM',
                'icon' => 'fa-solid fa-car-side'
            ],
            [
                'name' => 'Infosys',
                'desc' => 'Campus Connect, Springboard curricula & software certification',
                'logo' => 'infosys.png',
                'category' => 'IT & Software',
                'icon' => 'fa-solid fa-code'
            ],
            [
                'name' => 'LIEBHERR',
                'desc' => 'Heavy machinery design, refrigeration systems & plant internships',
                'logo' => 'liebherr.png',
                'category' => 'Heavy Engineering',
                'icon' => 'fa-solid fa-industry'
            ],
            [
                'name' => 'HIKAL',
                'desc' => 'Chemical engineering, process safety & pharmaceutical testing',
                'logo' => 'hikal.png',
                'category' => 'Chemical & Pharma',
                'icon' => 'fa-solid fa-vial-virus'
            ],
            [
                'name' => 'Atlas Copco',
                'desc' => 'Compressor technology, smart tools & industrial air solutions',
                'logo' => 'atlas_copco.png',
                'category' => 'Industrial Machinery',
                'icon' => 'fa-solid fa-screwdriver-wrench'
            ],
            [
                'name' => 'NASSCOM',
                'desc' => 'FutureSkills Prime certification & emerging IT sector standards',
                'logo' => 'nasscom.png',
                'category' => 'National IT Body',
                'icon' => 'fa-solid fa-laptop-code'
            ],
            [
                'name' => 'RUCHA ENGINEERING 2.0',
                'desc' => 'Automotive components, precision sheet metal & robotics tooling',
                'logo' => 'rucha_engineering.png',
                'category' => 'Robotics & Tooling',
                'icon' => 'fa-solid fa-gears'
            ],
            [
                'name' => 'CIPET',
                'desc' => 'Central Institute of Petrochemicals Engineering & Technology joint labs',
                'logo' => 'cipet.png',
                'category' => 'Polymer Tech',
                'icon' => 'fa-solid fa-flask'
            ],
            [
                'name' => 'Innovation Incubation Center',
                'desc' => 'AIC-BAMU startup incubation, innovation seed funding & patent cell',
                'logo' => 'aic_bamu.png',
                'category' => 'Startup & Patents',
                'icon' => 'fa-solid fa-lightbulb'
            ],
            [
                'name' => 'RAJURI STEEL',
                'desc' => 'Structural engineering, TMT steel rebar testing & metallurgy',
                'logo' => 'rajuri_steel.png',
                'category' => 'Metallurgy & Civil',
                'icon' => 'fa-solid fa-cubes-stacked'
            ],
            [
                'name' => 'Bridgewater State University (USA)',
                'desc' => 'International academic exchange, joint faculty research & global curriculum',
                'logo' => 'bridgewater_university.png',
                'category' => 'Global University (USA)',
                'icon' => 'fa-solid fa-earth-americas'
            ],
            [
                'name' => 'ISHRAE',
                'desc' => 'Indian Society of Heating, Refrigerating & Air Conditioning Engineers',
                'logo' => 'ishrae.png',
                'category' => 'HVAC & Energy',
                'icon' => 'fa-solid fa-fan'
            ],
            [
                'name' => 'MAHAVITARAN (MSEDCL)',
                'desc' => 'Electrical power transmission, smart grids & distribution labs',
                'logo' => 'mahavitaran.png',
                'category' => 'Power & Smart Grid',
                'icon' => 'fa-solid fa-bolt-lightning'
            ],
            [
                'name' => 'SONY',
                'desc' => 'Consumer electronics, image processing, sensors & multimedia tech',
                'logo' => 'sony.png',
                'category' => 'Consumer Electronics',
                'icon' => 'fa-solid fa-tv'
            ],
            [
                'name' => 'NIELIT',
                'desc' => 'National Institute of Electronics & Information Technology technical standards',
                'logo' => 'nielit.png',
                'category' => 'Govt. Electronics Lab',
                'icon' => 'fa-solid fa-microchip'
            ]
        ];
        ?>

        <div class="row g-4">
            <?php foreach ($collaborations as $collab): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="collab-logo-card">
                        <div class="collab-logo-box">
                            <?php if (!empty($collab['logo'])): ?>
                                <img src="<?php echo uploadUrl('collaborations', $collab['logo']); ?>" alt="<?php echo htmlspecialchars($collab['name']); ?>" class="collab-logo-img" loading="lazy" decoding="async">
                            <?php else: ?>
                                <i class="<?php echo $collab['icon']; ?> text-primary" style="font-size: 22px;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                <h6 class="fw-bold mb-0 text-primary-color font-display text-truncate" style="font-size: 14.5px;" title="<?php echo htmlspecialchars($collab['name']); ?>"><?php echo htmlspecialchars($collab['name']); ?></h6>
                            </div>
                            <span class="badge bg-warning-subtle text-dark border border-warning border-opacity-20 px-2 py-0.5 rounded-pill font-semibold mb-1" style="font-size: 14px;">
                                <?php echo htmlspecialchars($collab['category']); ?>
                            </span>
                            <small class="text-muted d-block" style="font-size: 14px; line-height: 1.35;"><?php echo htmlspecialchars($collab['desc']); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
