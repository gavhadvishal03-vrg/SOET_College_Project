<?php
$page_title = "Home";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$notices = $cms->getActiveNotices(5);
$recent_news = $cms->getPublishedNews(3);
$recent_blogs = $cms->getPublishedBlogs(3);
$settings = $cms->getSiteSettings();
$hero_bg = !empty($settings['home_hero_image']) && $settings['home_hero_image'] !== 'hero_default.jpg'
    ? uploadUrl('settings', $settings['home_hero_image'])
    : 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1600&q=80';
?>

<?php
$pinnedNotice = null;
foreach ($notices as $n) {
    if ($n['is_pinned']) {
        $pinnedNotice = $n;
        break;
    }
}
?>

<?php if ($pinnedNotice): ?>
    <div class="bg-warning text-dark py-2 px-3 border-bottom shadow-xs">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <span class="badge bg-danger text-white me-2"><i class="fa-solid fa-bell me-1"></i> Urgent Announcement</span>
                <strong class="small"><?php echo htmlspecialchars($pinnedNotice['title']); ?>:</strong>
                <span class="small ms-1 text-truncate" style="max-width: 500px;"><?php echo truncate(htmlspecialchars($pinnedNotice['content']), 80); ?></span>
            </div>
            <a href="index.php#notice-board-section" class="btn btn-xs btn-dark rounded-pill px-3 py-0.5" style="font-size: 11px;">View Notice Details</a>
        </div>
    </div>
<?php endif; ?>

<!-- Hero / Carousel Section -->
<div id="heroCarousel" class="carousel slide hero-section" data-bs-ride="carousel">
    <div class="hero-overlay"></div>
    <div class="carousel-inner h-100">
        <div class="carousel-item active h-100" style="background-image: url('<?php echo $hero_bg; ?>'); background-size: cover; background-position: center;">
            <div class="container h-100 d-flex align-items-center">
                <div class="hero-content">
                    <h1 class="hero-title">Shaping The Future Of <span>Engineering</span></h1>
                    <p class="hero-subtitle">SOET is committed to providing top-tier technological education with modern labs, expert faculty, and high-paying industry placements.</p>
                    <div class="d-flex gap-3">
                        <a href="admissions.php" class="btn btn-secondary-custom btn-lg"><i class="fa-solid fa-user-plus me-1"></i> Apply Now</a>
                        <a href="about.php" class="btn btn-outline-light btn-lg">Explore SOET</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main content area -->
<div class="container my-5">
    <div class="row g-4">
        <!-- Announcements / Notice Board -->
        <div class="col-lg-4" id="notice-board-section">
            <div class="card notice-board shadow-sm h-100">
                <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between p-3">
                    <h5 class="mb-0 text-uppercase"><i class="fa-solid fa-bullhorn text-warning me-2"></i>Notices</h5>
                    <span class="badge bg-danger">Live</span>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($notices)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fa-regular fa-folder-open mb-2" style="font-size: 24px;"></i>
                            <p class="mb-0">No active announcements at the moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notices as $notice): ?>
                            <div class="notice-item p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small"><i class="fa-regular fa-calendar-days me-1"></i><?php echo formatDate($notice['created_at']); ?></span>
                                    <?php if ($notice['is_pinned']): ?>
                                        <span class="notice-badge bg-danger text-white"><i class="fa-solid fa-thumbtack me-1"></i>Important</span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($notice['title']); ?></h6>
                                <p class="text-muted small mb-2"><?php echo truncate(htmlspecialchars($notice['content']), 100); ?></p>
                                <?php if ($notice['attachment_path']): ?>
                                    <a href="<?php echo uploadUrl('notices', $notice['attachment_path']); ?>" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2 rounded" style="font-size: 11px;">
                                        <i class="fa-solid fa-file-pdf me-1"></i> Download PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- College Welcome & Quick Overview -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 h-100">
                <?php
                $welcome_img = !empty($settings['home_welcome_image']) ? uploadUrl('settings', $settings['home_welcome_image']) : null;
                if ($welcome_img):
                ?>
                    <div class="mb-4 rounded overflow-hidden shadow-xs" style="max-height: 250px;">
                        <img src="<?php echo $welcome_img; ?>" alt="SOET Campus" class="w-100 h-100" style="object-fit: cover;">
                    </div>
                <?php endif; ?>
                <h2 class="fw-bold mb-3 text-primary-color">Welcome to SOET College</h2>
                <h5 class="text-secondary mb-4">Nurturing Leaders, Innovators, and Engineers since 2012</h5>
                <p class="text-muted">The School of Engineering and Technology (SOET) is a leading institution dedicated to offering high-quality technical education. Our programs are designed to meet the challenges of the rapidly evolving engineering industry, combining theoretical foundations with hands-on practice in state-of-the-art laboratory facilities.</p>
                <p class="text-muted">We offer specialization courses under B.Tech programs in Computer Science & Engineering, Electronics & Communication Engineering, Mechanical Engineering, and Civil Engineering. Our partnerships with leading companies ensure that our curriculum remains industry-aligned and our students achieve stellar career growth.</p>
                <div class="row g-3 mt-3">
                    <div class="col-sm-4 text-center">
                        <div class="p-3 bg-light rounded-3 shadow-xs">
                            <h3 class="fw-extrabold text-primary mb-0">1500+</h3>
                            <small class="text-muted">Active Students</small>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="p-3 bg-light rounded-3 shadow-xs">
                            <h3 class="fw-extrabold text-primary mb-0">85+</h3>
                            <small class="text-muted">Expert Faculty</small>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="p-3 bg-light rounded-3 shadow-xs">
                            <h3 class="fw-extrabold text-primary mb-0">90%</h3>
                            <small class="text-muted">Placement Rate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Departments Summary Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="section-header">
            <h2>Our Departments</h2>
            <p class="mb-0">Explore specialized engineering disciplines offering top-tier training and academic research programs.</p>
        </div>
        <div class="row g-4">
            <?php
            $active_depts = $cms->db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name LIMIT 4");
            if (empty($active_depts)):
            ?>
                <div class="col-12 text-center text-muted">
                    <p>No active departments found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($active_depts as $dept):
                    $dept_img = !empty($dept['image_path'])
                        ? uploadUrl('departments', $dept['image_path'])
                        : 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=600&q=80';
                ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="dept-card" style="background-image: url('<?php echo $dept_img; ?>');">
                            <div class="dept-overlay">
                                <h4 class="fw-bold"><?php echo htmlspecialchars($dept['name']); ?></h4>
                                <p class="small mb-3"><?php echo truncate(htmlspecialchars($dept['description'] ?? ''), 60); ?></p>
                                <a href="departments.php" class="btn btn-sm btn-outline-light rounded-pill align-self-start">Learn More</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- News & Blogs Portal Section -->
<div class="container my-5">
    <div class="row g-4">
        <!-- News Portal -->
        <div class="col-md-6">
            <h3 class="fw-bold mb-4 pb-2 border-bottom border-secondary"><i class="fa-solid fa-newspaper text-primary me-2"></i>Campus News</h3>
            <?php if (empty($recent_news)): ?>
                <p class="text-muted">No news updates available.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($recent_news as $news): ?>
                        <div class="card border-0 shadow-sm h-100 overflow-hidden d-flex flex-row">
                            <?php if ($news['image_path']): ?>
                                <img src="<?php echo uploadUrl('news', $news['image_path']); ?>" alt="News banner" style="width: 120px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body p-3">
                                <span class="text-muted small d-block mb-1"><?php echo formatDate($news['published_at']); ?></span>
                                <h6 class="fw-bold mb-1"><a href="news.php" class="text-decoration-none text-dark"><?php echo htmlspecialchars($news['title']); ?></a></h6>
                                <p class="text-muted small mb-0"><?php echo truncate(htmlspecialchars($news['content']), 80); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Student Blogs -->
        <div class="col-md-6">
            <h3 class="fw-bold mb-4 pb-2 border-bottom border-secondary"><i class="fa-solid fa-pen-nib text-primary me-2"></i>Faculty & Student Blogs</h3>
            <?php if (empty($recent_blogs)): ?>
                <p class="text-muted">No blog entries submitted yet. Be the first to share your thoughts!</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($recent_blogs as $blog): ?>
                        <div class="card border-0 shadow-sm h-100 overflow-hidden d-flex flex-row">
                            <?php if ($blog['image_path']): ?>
                                <img src="<?php echo uploadUrl('blogs', $blog['image_path']); ?>" alt="Blog image" style="width: 120px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small"><?php echo formatDate($blog['published_at']); ?></span>
                                    <span class="badge bg-secondary opacity-75" style="font-size: 10px;"><?php echo htmlspecialchars($blog['department_name'] ?? 'General'); ?></span>
                                </div>
                                <h6 class="fw-bold mb-1"><a href="blogs.php" class="text-decoration-none text-dark"><?php echo htmlspecialchars($blog['title']); ?></a></h6>
                                <p class="text-muted small mb-0">By: <?php echo htmlspecialchars($blog['author_name']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>