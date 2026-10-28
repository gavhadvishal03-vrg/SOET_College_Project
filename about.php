<?php
/**
 * Public About Us Page Controller & View
 *
 * Presents comprehensive institutional profile and history of SOET MGM University:
 * - Institutional overview (Integrated 6-Year and Regular 4-Year B.Tech programs)
 * - Academic vision, mission, and core values
 * - Director's message (Dr. Parminder Kaur Dhingra)
 * - Infrastructure and laboratory showcases
 * - MGM Trust 42-year educational legacy milestones
 */

$page_title = "About Us - Where Technology Meets Tomorrow";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$about_bg = !empty($settings['about_hero_image']) ? uploadUrl('settings', $settings['about_hero_image']) : uploadUrl('gallery', 'soet_campus_aerial.jpg');
$director_img = !empty($settings['director_image']) ? uploadUrl('settings', $settings['director_image']) : uploadUrl('faculty', 'Dr_Parminder_Kaur_Dhingra_2.jpeg');
?>

<!-- Page Hero Banner -->
<div class="page-hero mb-5" style="<?php echo $about_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{$about_bg}') center/cover no-repeat;" : ""; ?>">
    <div class="container position-relative z-1">
        <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill mb-2 fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">
            <i class="fa-solid fa-sparkles me-1"></i> <?php echo htmlspecialchars($settings['university_name'] ?? 'MGM University'); ?>
        </span>
        <h1 class="font-display fw-extrabold mb-2 text-white"><?php echo htmlspecialchars($settings['about_hero_title'] ?? 'About SOET'); ?></h1>
        <p class="lead text-warning fw-semibold mb-2" style="font-size: 1.3rem;"><?php echo htmlspecialchars($settings['about_hero_tagline'] ?? 'Where Technology Meets Tomorrow'); ?></p>
        <p class="opacity-80 mb-3 text-white" style="font-size: 1.05rem; max-width: 700px;"><?php echo htmlspecialchars($settings['about_hero_subtitle'] ?? 'Empowering students to engineer the future through cutting-edge laboratories, future-ready curricula, and a 42-year legacy of educational innovation.'); ?></p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">About Us</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-5 align-items-start">
        <!-- Main Institutional Overview -->
        <div class="col-lg-7">
            <div class="badge bg-primary-light text-primary-color border border-primary border-opacity-10 px-3 py-1.5 rounded-pill mb-3 font-semibold" style="font-size: 14px;">
                <i class="fa-solid fa-landmark me-1.5 text-warning"></i> Pioneer in Engineering Education
            </div>
            <h2 id="history" class="fw-bold text-primary-color mb-3 font-display" style="font-size: 2.2rem;">About School of Engineering and Technology</h2>
            <p class="lead text-secondary mb-4" style="font-size: 1.15rem; font-weight: 500;">
                <?php echo htmlspecialchars($settings['about_overview_lead'] ?? "You're not just studying engineering. You're engineering the future."); ?>
            </p>
            
            <p class="text-muted" style="line-height: 1.8; text-align: justify;">
                <?php echo nl2br(htmlspecialchars($settings['about_overview_p1'] ?? "The School of Engineering and Technology (SOET) at MGM University is a pioneer in offering Integrated Six-Year B.Tech. Programs (after SSC / Class 10) and Four-Year B.Tech. Programs (after HSC / Class 12) in the Marathwada region. The Integrated program allows students to begin their engineering education right after Class 10, offering an early advantage and extended time for hands-on learning, innovation, and deep exploration of engineering fundamentals. This structure empowers young aspirants to build a strong foundation and stay ahead in the competitive landscape. SOET's integrated curriculum is designed to nurture curiosity, develop critical thinking, and prepare students for advanced industry demands from an early stage.")); ?>
            </p>
            
            <p class="text-muted" style="line-height: 1.8; text-align: justify;">
                <?php echo nl2br(htmlspecialchars($settings['about_overview_p2'] ?? "At the same time, SOET offers focused Four-Year B.Tech. programs after Class 12, catering to students who aspire to pursue professional engineering education immediately after HSC. These programs are aligned with the latest industry trends and include emerging domains such as Computer Science and Engineering (IoT, Cyber Security & Blockchain), Cyber Security, Electronics Engineering (VLSI Design & Technology), Robotics & Automation, and Electrical Engineering. The teaching and learning process is Project-Based backed by Research, and is powered by Collaborative methods.")); ?>
            </p>

            <p class="text-muted" style="line-height: 1.8; text-align: justify;">
                <?php echo nl2br(htmlspecialchars($settings['about_overview_p3'] ?? "Students benefit from internship and placement opportunities through active collaborations with industries. With a legacy of over 42 years of MGM Trust, SOET is committed to producing future-ready engineers through holistic education, modern infrastructure, and a strong emphasis on innovation and ethics.")); ?>
            </p>

            <!-- Key Feature Pillars from Brochure -->
            <div class="row g-3 my-4">
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100 d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;">NEP 2020 Aligned</h6>
                            <small class="text-muted">Modern flexible academic curriculum</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100 d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: rgba(245, 158, 11, 0.15); color: #d97706;">
                            <i class="fa-solid fa-award"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;">Outcome Based Education</h6>
                            <small class="text-muted">OBE system with clear competency metrics</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100 d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: rgba(16, 185, 129, 0.12); color: #059669;">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;">Experiential &amp; PBL Learning</h6>
                            <small class="text-muted">Problem &amp; project-based methodologies</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100 d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: rgba(147, 51, 234, 0.12); color: #9333ea;">
                            <i class="fa-solid fa-rocket"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;">Startup &amp; Incubation Cell</h6>
                            <small class="text-muted">Innovation Incubation &amp; Research Center</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vision & Mission Cards -->
            <div class="row g-4 mt-2">
                <!-- Vision Card -->
                <div class="col-md-6">
                    <div class="p-4 bg-light rounded-3 border shadow-xs h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 46px; height: 46px; background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1.5px solid #f59e0b;">
                                <i class="fa-solid fa-eye" style="font-size: 20px; color: #b45309;"></i>
                            </div>
                            <h5 class="fw-bold text-primary-color mb-0 font-display">Vision of SOET</h5>
                        </div>
                        <p class="text-muted small mb-0" style="line-height: 1.75;">
                            <?php echo htmlspecialchars($settings['about_vision'] ?? "To empower students as leaders of tomorrow by fostering a culture of innovation, skill development, and adaptability, bridging the gap between academic excellence and industry readiness in an ever-evolving global economy."); ?>
                        </p>
                    </div>
                </div>

                <!-- Mission Card -->
                <div class="col-md-6">
                    <div class="p-4 bg-light rounded-3 border shadow-xs h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 46px; height: 46px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); border: 1.5px solid #2563eb;">
                                <i class="fa-solid fa-bullseye" style="font-size: 20px; color: #1e3a8a;"></i>
                            </div>
                            <h5 class="fw-bold text-primary-color mb-0 font-display">Mission of SOET</h5>
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                            <li class="d-flex align-items-start gap-2 text-muted small" style="line-height: 1.6;">
                                <i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                                <span><?php echo htmlspecialchars($settings['about_mission_1'] ?? "To cultivate future-ready professionals through transformative training and experiential learning."); ?></span>
                            </li>
                            <li class="d-flex align-items-start gap-2 text-muted small" style="line-height: 1.6;">
                                <i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                                <span><?php echo htmlspecialchars($settings['about_mission_2'] ?? "To inspire students to embrace emerging trends and technologies, building versatile and sustainable career paths."); ?></span>
                            </li>
                            <li class="d-flex align-items-start gap-2 text-muted small" style="line-height: 1.6;">
                                <i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                                <span><?php echo htmlspecialchars($settings['about_mission_3'] ?? "To provide personalized career mentorship and foster meaningful collaborations with leading industries."); ?></span>
                            </li>
                            <li class="d-flex align-items-start gap-2 text-muted small" style="line-height: 1.6;">
                                <i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                                <span><?php echo htmlspecialchars($settings['about_mission_4'] ?? "To create an ecosystem that nurtures talent, values diversity, and encourages lifelong learning for global success."); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: About MGM University & Dean Message -->
        <div class="col-lg-5">
            <!-- About MGM University Card (from Brochure Page 3) -->
            <div class="card border-0 shadow-sm p-4 text-white mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-radius: 18px; border-top: 4px solid var(--accent) !important;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px; background: rgba(245, 158, 11, 0.2); border: 1.5px solid #f59e0b;">
                        <i class="fa-solid fa-building-columns text-warning fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-white mb-0 font-display"><?php echo htmlspecialchars($settings['about_mgmu_title'] ?? 'About MGM University'); ?></h5>
                        <small class="text-warning fw-semibold"><?php echo htmlspecialchars($settings['about_mgmu_subtitle'] ?? '42+ Years of Educational Legacy'); ?></small>
                    </div>
                </div>
                <p class="small text-white-50 mb-3" style="line-height: 1.75; text-align: justify;">
                    <?php echo htmlspecialchars($settings['about_mgmu_text'] ?? "MGM University, established by the revered Mahatma Gandhi Mission Trust, a pioneer with four decades of remarkable legacy in education, research, and service, is a State Private University, approved by Government of Maharashtra MGM University Act No. XXVI of 2019."); ?>
                </p>
                <div class="p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-10 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-certificate text-warning fa-lg"></i>
                        <div class="small">
                            <strong class="text-white d-block">UGC 2(f) Recognized Status</strong>
                            <span class="text-white-50" style="font-size: 14px;"><?php echo htmlspecialchars($settings['about_mgmu_ugc'] ?? 'Granted by University Grants Commission (UGC) of India Under Section 2(f).'); ?></span>
                        </div>
                    </div>
                </div>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small text-white-50">
                    <li><i class="fa-solid fa-check text-warning me-2"></i>Approved by Govt. of Maharashtra (Act No. XXVI of 2019)</li>
                    <li><i class="fa-solid fa-check text-warning me-2"></i>Over <?php echo htmlspecialchars($settings['trust_legacy_years'] ?? '42+'); ?> years of MGM Trust excellence</li>
                    <li><i class="fa-solid fa-check text-warning me-2"></i>Recognized Under Section 2(f) of UGC Act</li>
                </ul>
            </div>

            <!-- Dean/Director Message -->
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 18px; border: 1px solid #e2e8f0 !important;">
                <div class="text-center mb-3">
                    <div class="d-inline-block p-1 bg-white rounded-circle shadow-xs border border-warning mb-3">
                        <img src="<?php echo $director_img; ?>" alt="Director Profile" class="rounded-circle" style="width: 130px; height: 130px; object-fit: cover;" loading="lazy" decoding="async">
                    </div>
                    <h5 class="fw-bold text-primary-color mb-1 font-display"><?php echo htmlspecialchars($settings['director_name'] ?? 'Dr. Parminder Kaur Dhingra'); ?></h5>
                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 14px; letter-spacing: 0.5px;"><?php echo htmlspecialchars($settings['director_designation'] ?? 'Dean & Director, SOET MGM University'); ?></small>
                </div>
                <blockquote class="blockquote bg-light p-3.5 rounded-3 shadow-xs mb-0 border-start border-4 border-warning">
                    <p class="mb-0 text-muted small" style="line-height: 1.7;"><i class="fa-solid fa-quote-left text-warning me-2"></i><?php echo htmlspecialchars($settings['director_message'] ?? "At SOET MGM University, we are committed to providing top-tier technological education with modern laboratories, expert faculty, and high-paying industry placements. Our mission is to produce innovative, ethically driven engineering professionals."); ?></p>
                </blockquote>
            </div>
        </div>
    </div>
</div>

<!-- Cutting-Edge Laboratories & Facilities Section (from Database) -->
<div class="bg-light py-5 border-top border-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-1.5 rounded-pill mb-2 font-semibold" style="font-size: 14px;">
                <i class="fa-solid fa-microchip me-1"></i> Future-Ready Campus Infrastructure
            </span>
            <h2 class="font-display fw-bold text-primary-color">Cutting-Edge Laboratories &amp; Advanced Facilities</h2>
            <p class="text-muted mx-auto" style="max-width: 750px;">Students at SOET have access to some of the most advanced facilities in the region, designed to spark innovation, research, and hands-on learning.</p>
        </div>
        
        <?php 
        $activeFacilities = $cms->getFacilities(true);
        if (empty($activeFacilities)) {
            $activeFacilities = [
                ['title' => 'Industrial Robotics Lab', 'category' => 'Advanced Laboratory', 'description' => 'Equipped with industrial robotic arms, automated kinematics rigs, and computer vision systems where students build and program intelligent machines.', 'icon' => 'fa-solid fa-robot'],
                ['title' => 'Electric Vehicle (EV) Lab', 'category' => 'Clean Mobility & Power', 'description' => 'Dedicated to exploring sustainable mobility, battery management systems (BMS), motor control drives, and next-generation green transportation.', 'icon' => 'fa-solid fa-car-battery'],
                ['title' => 'Mechatronics & Industry 4.0', 'category' => 'Smart Automation', 'description' => 'State-of-the-art PLC, SCADA, HMI, smart sensors, and cyber-physical systems designed for prototyping automated Industry 4.0 smart factory solutions.', 'icon' => 'fa-solid fa-gears'],
                ['title' => 'Innovation & Incubation Center', 'category' => 'Startup & R&D Hub', 'description' => 'The IIRC provides seed mentorship, patent guidance, and prototyping workspace for students building drones, IoT platforms, and commercial startups.', 'icon' => 'fa-solid fa-lightbulb'],
                ['title' => 'Smart Classrooms & Library', 'category' => 'Academic Infrastructure', 'description' => 'Air-conditioned interactive smart lecture halls, central library with 45,000+ volumes, IEEE digital subscriptions, and high-speed campus Wi-Fi.', 'icon' => 'fa-solid fa-chalkboard-user'],
                ['title' => 'Holistic Campus & Hostels', 'category' => 'Student Living & Sports', 'description' => 'Separate modern residential hostels for boys and girls, Olympic-standard sports grounds, fitness gymnasium, and vibrant student cultural forums.', 'icon' => 'fa-solid fa-futbol']
            ];
        }
        ?>

        <div class="row g-4">
            <?php foreach ($activeFacilities as $fac): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-xs h-100 overflow-hidden hover-lift bg-white" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
                        <div class="card-body p-4">
                            <div class="rounded-3 d-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px; background: rgba(37, 99, 235, 0.1); color: #2563eb; font-size: 22px;">
                                <i class="<?php echo htmlspecialchars($fac['icon'] ?: 'fa-solid fa-microchip'); ?>"></i>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill mb-2" style="font-size: 14px;"><?php echo htmlspecialchars($fac['category'] ?? 'Laboratory'); ?></span>
                            <h5 class="fw-bold text-primary-color mb-2 font-display"><?php echo htmlspecialchars($fac['title']); ?></h5>
                            <p class="text-muted small mb-0" style="line-height: 1.65;"><?php echo htmlspecialchars($fac['description']); ?></p>
                            <?php if (!empty($fac['features'])): ?>
                                <ul class="list-unstyled mt-2.5 pt-2 border-top mb-0 small text-muted">
                                    <?php foreach (explode("\n", trim($fac['features'])) as $feat): if (trim($feat)): ?>
                                        <li class="d-flex align-items-center gap-1.5 mb-1"><i class="fa-solid fa-circle-check text-success" style="font-size: 14px;"></i> <span><?php echo htmlspecialchars(trim($feat)); ?></span></li>
                                    <?php endif; endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
