<?php
$page_title = "About Us";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$about_bg = !empty($settings['about_hero_image']) ? uploadUrl('settings', $settings['about_hero_image']) : null;
$director_img = !empty($settings['director_image']) ? uploadUrl('settings', $settings['director_image']) : 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=300&q=80';
?>

<!-- Breadcrumb/Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $about_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$about_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">About SOET</h1>
        <p class="lead opacity-75 mb-3">Empowering innovators and shaping future engineering leaders</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">About Us</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-5">
        <!-- Text details -->
        <div class="col-lg-7">
            <h2 class="fw-bold text-primary-color mb-3">Our History and Vision</h2>
            <p class="lead text-secondary mb-4">Empowering students through cutting-edge technology and engineering wisdom since 2012.</p>
            
            <p class="text-muted">The School of Engineering and Technology (SOET) was established with the core objective of producing highly skilled, industry-ready engineering professionals. Over the last decade, we have grown into one of the top engineering schools in the region, recognized for academic excellence, state-of-the-art labs, and brilliant placement outcomes.</p>
            
            <p class="text-muted">Our curriculum integrates core theoretical concepts with extensive project-based practical training, assuring that our students are equipped to lead in modern domains such as Software Engineering, Advanced Networking, Robotics, Automation, and Smart Infrastructure.</p>

            <div class="row g-4 mt-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-start gap-3">
                        <div class="p-3 rounded-circle bg-warning bg-opacity-15 text-warning">
                            <i class="fa-solid fa-eye fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-primary-color">Our Vision</h5>
                            <p class="text-muted small">To be a globally recognized center of excellence in engineering education and research, fostering innovation and social responsibility.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-start gap-3">
                        <div class="p-3 rounded-circle bg-primary bg-opacity-15 text-primary">
                            <i class="fa-solid fa-crosshairs fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-primary-color">Our Mission</h5>
                            <p class="text-muted small">To provide exceptional technical training, encourage interdisciplinary research, and nurture ethical values to support global technological challenges.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dean/Director Message -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <div class="text-center mb-4">
                    <img src="<?php echo $director_img; ?>" alt="Director Profile" class="img-fluid rounded-circle shadow-sm mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h5 class="fw-bold text-primary-color mb-1"><?php echo htmlspecialchars($settings['director_name'] ?? 'Dr. Parminder Kaur Dhingra'); ?></h5>
                    <small class="text-muted text-uppercase fw-semibold"><?php echo htmlspecialchars($settings['director_designation'] ?? 'Dean & Director, SOET MGM University'); ?></small>
                </div>
                <blockquote class="blockquote blockquote-custom bg-white p-3 rounded shadow-xs mb-0" style="border-left: 4px solid var(--secondary-color);">
                    <p class="mb-0 text-muted small"><i class="fa-solid fa-quote-left text-warning me-2"></i><?php echo htmlspecialchars($settings['director_message'] ?? "At SOET MGM University, we are committed to providing top-tier technological education with modern laboratories, expert faculty, and high-paying industry placements. Our mission is to produce innovative, ethically driven engineering professionals."); ?></p>
                </blockquote>
            </div>
        </div>
    </div>
</div>

<!-- Infrastructures / Campuses details -->
<div class="bg-light py-5">
    <div class="container">
        <div class="section-header">
            <h2>Campus Infrastructure</h2>
            <p>Our campus is built to foster academic excellence, with advanced amenities, smart lecture halls, and top-tier labs.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card custom-card h-100">
                    <img src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="Smart Classrooms" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="fw-bold text-primary-color"><i class="fa-solid fa-chalkboard-user me-2 text-warning"></i>Smart Classrooms</h5>
                        <p class="text-muted small">All classrooms are fully air-conditioned and equipped with smart projectors, high-definition displays, and sound systems for interactive learning.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card h-100">
                    <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="Advanced Labs" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="fw-bold text-primary-color"><i class="fa-solid fa-flask-vial me-2 text-warning"></i>Advanced Labs</h5>
                        <p class="text-muted small">State-of-the-art computer labs, microprocessor kits, robotics centers, civil engineering testing labs, and high-performance server rooms.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card h-100">
                    <img src="https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="Digital Library" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="fw-bold text-primary-color"><i class="fa-solid fa-book me-2 text-warning"></i>Digital Library</h5>
                        <p class="text-muted small">A modern central library holding over 45,000 physical volumes, combined with digital subscriptions to IEEE journals, ACM, and Springer Link.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
