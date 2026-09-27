<?php
$page_title = "About Us";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$about_bg = !empty($settings['about_hero_image']) ? uploadUrl('settings', $settings['about_hero_image']) : null;
$director_img = !empty($settings['director_image']) ? uploadUrl('settings', $settings['director_image']) : 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=300&q=80';
?>

<!-- Page Hero Banner -->
<div class="page-hero mb-5">
    <div class="container position-relative z-1">
        <h1 class="font-display fw-extrabold mb-2 text-white">About SOET</h1>
        <p class="lead opacity-80 mb-3" style="font-size: 1.1rem; max-width: 650px;">Empowering innovators, fostering engineering brilliance, and shaping future technological leaders.</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About Us</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-5 align-items-center">
        <!-- Text details -->
        <div class="col-lg-7">
            <div class="badge bg-primary-light text-primary-color border border-primary border-opacity-10 px-3 py-1.5 rounded-pill mb-3 font-semibold" style="font-size: 12px;">
                <i class="fa-solid fa-landmark me-1.5 text-warning"></i> Institutional Heritage
            </div>
            <h2 id="history" class="fw-bold text-primary-color mb-3 font-display" style="font-size: 2.2rem;">Our History &amp; Academic Vision</h2>
            <p class="lead text-secondary mb-4" style="font-size: 1.15rem; font-weight: 500;">Empowering engineering students through world-class labs and technological wisdom since 2012.</p>
            
            <p class="text-muted" style="line-height: 1.75;">The School of Engineering and Technology (SOET) was established with the core objective of producing highly skilled, industry-ready engineering professionals. Over the last decade, we have grown into one of the top engineering schools in the region, recognized for academic excellence, state-of-the-art labs, and brilliant placement outcomes.</p>
            
            <p class="text-muted" style="line-height: 1.75;">Our curriculum integrates core theoretical concepts with extensive project-based practical training, assuring that our students are equipped to lead in modern domains such as Software Engineering, AI/ML, Cyber Security, Robotics, Automation, and Smart Infrastructure.</p>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <div class="p-3.5 bg-light rounded-3 border border-light shadow-xs h-100">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="p-2.5 rounded-3 bg-warning bg-opacity-15 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fa-solid fa-eye fa-lg"></i>
                            </div>
                            <h5 class="fw-bold text-primary-color mb-0 font-display">Our Vision</h5>
                        </div>
                        <p class="text-muted small mb-0" style="line-height: 1.6;">To be a globally recognized center of excellence in engineering education and research, fostering innovation and social responsibility.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3.5 bg-light rounded-3 border border-light shadow-xs h-100">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="p-2.5 rounded-3 bg-primary bg-opacity-10 text-primary-color d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fa-solid fa-crosshairs fa-lg"></i>
                            </div>
                            <h5 class="fw-bold text-primary-color mb-0 font-display">Our Mission</h5>
                        </div>
                        <p class="text-muted small mb-0" style="line-height: 1.6;">To provide exceptional technical training, encourage interdisciplinary research, and nurture ethical values to meet global technological challenges.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dean/Director Message -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 18px; border: 1px solid #e2e8f0 !important;">
                <div class="text-center mb-3">
                    <div class="d-inline-block p-1 bg-white rounded-circle shadow-xs border border-warning mb-3">
                        <img src="<?php echo $director_img; ?>" alt="Director Profile" class="rounded-circle" style="width: 140px; height: 140px; object-fit: cover;">
                    </div>
                    <h5 class="fw-bold text-primary-color mb-1 font-display"><?php echo htmlspecialchars($settings['director_name'] ?? 'Dr. Parminder Kaur Dhingra'); ?></h5>
                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;"><?php echo htmlspecialchars($settings['director_designation'] ?? 'Dean & Director, SOET MGM University'); ?></small>
                </div>
                <blockquote class="blockquote bg-light p-3.5 rounded-3 shadow-xs mb-0 border-start border-4 border-warning">
                    <p class="mb-0 text-muted small" style="line-height: 1.7;"><i class="fa-solid fa-quote-left text-warning me-2"></i><?php echo htmlspecialchars($settings['director_message'] ?? "At SOET MGM University, we are committed to providing top-tier technological education with modern laboratories, expert faculty, and high-paying industry placements. Our mission is to produce innovative, ethically driven engineering professionals."); ?></p>
                </blockquote>
            </div>
        </div>
    </div>
</div>

<!-- Infrastructures / Campuses details -->
<div class="bg-light py-5 border-top border-light">
    <div class="container">
        <div class="section-header">
            <h2>Campus Infrastructure</h2>
            <p>Our campus is built to foster academic excellence, with advanced amenities, smart lecture halls, and top-tier labs.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-xs h-100 overflow-hidden hover-lift" style="border-radius: 14px;">
                    <img src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="Smart Classrooms" style="height: 200px; object-fit: cover;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-primary-color mb-2 font-display"><i class="fa-solid fa-chalkboard-user me-2 text-warning"></i>Smart Classrooms</h5>
                        <p class="text-muted small mb-0" style="line-height: 1.6;">All classrooms are fully air-conditioned and equipped with smart projectors, high-definition displays, and sound systems for interactive learning.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-xs h-100 overflow-hidden hover-lift" style="border-radius: 14px;">
                    <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="Advanced Labs" style="height: 200px; object-fit: cover;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-primary-color mb-2 font-display"><i class="fa-solid fa-flask-vial me-2 text-warning"></i>Advanced Labs</h5>
                        <p class="text-muted small mb-0" style="line-height: 1.6;">State-of-the-art computer labs, microprocessor kits, robotics centers, civil engineering testing labs, and high-performance server rooms.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-xs h-100 overflow-hidden hover-lift" style="border-radius: 14px;">
                    <img src="https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="Digital Library" style="height: 200px; object-fit: cover;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-primary-color mb-2 font-display"><i class="fa-solid fa-book me-2 text-warning"></i>Digital Library</h5>
                        <p class="text-muted small mb-0" style="line-height: 1.6;">A modern central library holding over 45,000 physical volumes, combined with digital subscriptions to IEEE journals, ACM, and Springer Link.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
