<?php
/**
 * Public Blogs Portal Controller & View
 *
 * Provides the public student & faculty blog directory and single article reader:
 * - Single article view via unique URL slugs (with estimated reading time, author, and department)
 * - Paginated published blog catalog with cover images and summaries
 * - Public submission callout encouraging students/faculty to contribute articles
 */
$page_title = "Blogs Portal";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$slug = isset($_GET['slug']) ? Security::escape($_GET['slug']) : '';

if (!empty($slug)) {
    // Single Blog View
    $blog = $cms->db->fetchOne(
        "SELECT b.*, u.full_name as author_name, d.name as department_name 
         FROM blogs b JOIN users u ON b.author_id = u.id 
         LEFT JOIN departments d ON b.department_id = d.id 
         WHERE b.slug = ? AND b.status = 'published'",
        [$slug]
    );

    if (!$blog) {
        setFlash('danger', 'Blog article not found or not approved yet.');
        redirect('blogs.php');
    }
    
    $wordCount = str_word_count(strip_tags($blog['content']));
    $readMinutes = max(1, ceil($wordCount / 200));
    ?>
    <!-- Breadcrumb Header -->
    <div class="page-hero mb-5">
        <div class="container position-relative z-1">
            <h1 class="font-display fw-extrabold mb-2 text-white"><?php echo htmlspecialchars($blog['title']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="blogs.php" class="text-white opacity-75 text-decoration-none">Blogs</a></li>
                    <li class="breadcrumb-item active text-warning" aria-current="page"><?php echo truncate(htmlspecialchars($blog['title']), 30); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Blog details -->
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4">
                    <div class="d-flex align-items-center gap-3 mb-4 text-muted small flex-wrap">
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><i class="fa-regular fa-calendar me-1 text-warning"></i><?php echo formatDate($blog['published_at']); ?></span>
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><i class="fa-solid fa-user-pen me-1 text-primary"></i>By <?php echo htmlspecialchars($blog['author_name']); ?></span>
                        <span class="badge bg-primary text-white px-3 py-2 rounded-pill"><?php echo htmlspecialchars($blog['department_name'] ?? 'General'); ?></span>
                        <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill"><i class="fa-regular fa-clock me-1"></i><?php echo $readMinutes; ?> min read</span>
                    </div>

                    <?php if ($blog['image_path']): ?>
                        <div class="text-center mb-4 rounded-4 overflow-hidden shadow-xs" style="max-height: 450px;">
                            <img src="<?php echo uploadUrl('blogs', $blog['image_path']); ?>" alt="Cover Banner" class="img-fluid w-100" style="object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <div class="blog-content fs-6 text-dark" style="line-height: 1.9; letter-spacing: 0.2px;">
                        <?php echo nl2br(htmlspecialchars($blog['content'])); ?>
                    </div>
                </div>
            </div>

            <!-- Recent Blogs list sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 bg-white mb-4 rounded-4">
                    <h5 class="font-display fw-bold text-dark mb-3"><i class="fa-solid fa-fire text-warning me-2"></i>Other Recent Articles</h5>
                    <?php 
                        $others = $cms->db->fetchAll(
                            "SELECT title, slug, published_at FROM blogs WHERE status = 'published' AND slug != ? ORDER BY published_at DESC LIMIT 5",
                            [$slug]
                        );
                        if (empty($others)):
                            echo '<p class="text-muted small mb-0">No other articles available.</p>';
                        else:
                            echo '<div class="list-group list-group-flush rounded-3">';
                            foreach ($others as $oth) {
                                echo '<a href="blogs.php?slug=' . urlencode($oth['slug']) . '" class="list-group-item list-group-item-action py-3 px-2 border-bottom">';
                                echo '<strong class="text-dark d-block mb-1 hover-primary">' . htmlspecialchars($oth['title']) . '</strong>';
                                echo '<span class="text-muted small"><i class="fa-regular fa-calendar-days me-1 text-warning"></i>' . formatDate($oth['published_at']) . '</span>';
                                echo '</a>';
                            }
                            echo '</div>';
                        endif;
                    ?>
                </div>

                <div class="card border-0 shadow-sm p-4 text-center text-white rounded-4" style="background: linear-gradient(135deg, var(--primary), var(--primary-600));">
                    <div class="stat-icon-wrapper bg-white bg-opacity-10 text-warning mx-auto mb-3" style="width: 55px; height: 55px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-pen-nib fa-xl"></i>
                    </div>
                    <h5 class="font-display fw-bold mb-1">Share Your Knowledge</h5>
                    <p class="small text-white opacity-75 mb-3">Submit your technical research, tutorials, or campus projects to the editorial board.</p>
                    <a href="submit-blog.php" class="btn btn-warning text-dark font-semibold rounded-pill px-4 shadow-sm">Submit an Article</a>
                </div>
            </div>
        </div>
    </div>

    <?php
} else {
    // Blogs List View
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $paginator = $cms->paginate('blogs', "status = 'published'", [], $page, 6);
    $blogs = $cms->db->fetchAll(
        "SELECT b.*, u.full_name as author_name, d.name as department_name 
         FROM blogs b JOIN users u ON b.author_id = u.id 
         LEFT JOIN departments d ON b.department_id = d.id 
         WHERE b.status = 'published' ORDER BY b.published_at DESC LIMIT ? OFFSET ?",
        [$paginator['per_page'], $paginator['offset']]
    );
    $blogs_bg = !empty($settings['blogs_hero_image']) ? uploadUrl('settings', $settings['blogs_hero_image']) : uploadUrl('gallery', 'soet_future_tech_banner.jpg');
    ?>

    <!-- Breadcrumb Header -->
    <div class="page-hero mb-5" style="<?php echo $blogs_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{$blogs_bg}') center/cover no-repeat;" : ""; ?>">
        <div class="container position-relative z-1 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="font-display fw-extrabold mb-2 text-white">Blogs & Tech Insights</h1>
                <p class="lead opacity-80 mb-3">Student Articles, Faculty Research & Academic Innovations</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                        <li class="breadcrumb-item active text-warning" aria-current="page">Blogs</li>
                    </ol>
                </nav>
            </div>
            <a href="submit-blog.php" class="btn btn-warning text-dark rounded-pill px-4 py-2 font-semibold shadow-sm"><i class="fa-solid fa-pen-to-square me-1"></i> Write a Blog</a>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Live Search Bar -->
        <div class="row mb-5 justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border p-1">
                    <span class="input-group-text bg-white border-0 text-warning ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="blogSearchInput" class="form-control border-0 shadow-none ps-2" placeholder="Search blog articles by title, author, or keyword...">
                </div>
            </div>
        </div>

        <div class="row g-4" id="blogsGrid">
            <?php if (empty($blogs)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <div class="stat-icon-wrapper bg-light text-secondary mx-auto mb-3" style="width: 70px; height: 70px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-newspaper fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Articles Published Yet</h5>
                    <p class="text-muted">Check back soon or be the first to publish an article!</p>
                </div>
            <?php else: ?>
                <?php foreach ($blogs as $blog): 
                    $wordCount = str_word_count(strip_tags($blog['content']));
                    $readMinutes = max(1, ceil($wordCount / 200));
                ?>
                    <div class="col-md-6 col-lg-4 blog-card-col" 
                         data-title="<?php echo strtolower(htmlspecialchars($blog['title'])); ?>"
                         data-content="<?php echo strtolower(htmlspecialchars(substr($blog['content'], 0, 200))); ?>"
                         data-author="<?php echo strtolower(htmlspecialchars($blog['author_name'])); ?>">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift transition-all">
                            <?php if ($blog['image_path']): ?>
                                <img src="<?php echo uploadUrl('blogs', $blog['image_path']); ?>" class="card-img-top" alt="Cover" style="height: 220px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 220px; background: linear-gradient(135deg, var(--primary), var(--primary-600)) !important;">
                                    <i class="fa-solid fa-pen-nib fa-3x text-warning"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column justify-content-between p-4">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small"><i class="fa-regular fa-calendar me-1 text-warning"></i><?php echo formatDate($blog['published_at']); ?></span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;"><?php echo htmlspecialchars($blog['department_name'] ?? 'General'); ?></span>
                                    </div>
                                    <h5 class="font-display fw-bold text-dark mb-2"><?php echo htmlspecialchars($blog['title']); ?></h5>
                                    <p class="text-muted small mb-3"><?php echo truncate(htmlspecialchars($blog['content']), 120); ?></p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-2">
                                    <small class="text-muted font-semibold"><i class="fa-regular fa-clock me-1"></i><?php echo $readMinutes; ?> min read</small>
                                    <a href="blogs.php?slug=<?php echo urlencode($blog['slug']); ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 font-semibold">Read Full <i class="fa-solid fa-arrow-right small ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="row mt-5">
            <div class="col-12">
                <?php echo renderPagination($paginator['page'], $paginator['total_pages'], 'blogs.php'); ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('blogSearchInput');
        const cards = document.querySelectorAll('.blog-card-col');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                cards.forEach(card => {
                    const title = card.getAttribute('data-title') || '';
                    const content = card.getAttribute('data-content') || '';
                    const author = card.getAttribute('data-author') || '';

                    if (!query || title.includes(query) || content.includes(query) || author.includes(query)) {
                        card.classList.remove('d-none');
                    } else {
                        card.classList.add('d-none');
                    }
                });
            });
        }
    });
    </script>

    <?php
}
include_once __DIR__ . '/includes/footer.php';
?>
