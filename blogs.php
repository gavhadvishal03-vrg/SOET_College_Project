<?php
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
    <div class="bg-light py-4 border-bottom mb-5">
        <div class="container">
            <h1 class="fw-bold mb-1 text-primary-color"><?php echo htmlspecialchars($blog['title']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="blogs.php" class="text-decoration-none">Blogs</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo truncate(htmlspecialchars($blog['title']), 30); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Blog details -->
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                    <div class="d-flex align-items-center gap-3 mb-4 text-muted small flex-wrap">
                        <span><i class="fa-regular fa-calendar me-1 text-warning"></i><?php echo formatDate($blog['published_at']); ?></span>
                        <span><i class="fa-solid fa-user-pen me-1 text-primary"></i>By <?php echo htmlspecialchars($blog['author_name']); ?></span>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($blog['department_name'] ?? 'General'); ?></span>
                        <span class="badge bg-light text-dark border"><i class="fa-regular fa-clock me-1"></i><?php echo $readMinutes; ?> min read</span>
                    </div>

                    <?php if ($blog['image_path']): ?>
                        <div class="text-center mb-4 rounded overflow-hidden shadow-xs" style="max-height: 400px;">
                            <img src="<?php echo uploadUrl('blogs', $blog['image_path']); ?>" alt="Cover Banner" class="img-fluid w-100" style="object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <div class="blog-content" style="line-height: 1.85; font-size: 16px; color: #2d3748;">
                        <?php echo nl2br(htmlspecialchars($blog['content'])); ?>
                    </div>
                </div>
            </div>

            <!-- Recent Blogs list sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 bg-light mb-4 rounded-3">
                    <h5 class="fw-bold text-primary-color mb-3"><i class="fa-solid fa-fire text-warning me-2"></i>Other Recent Articles</h5>
                    <?php 
                        $others = $cms->db->fetchAll(
                            "SELECT title, slug, published_at FROM blogs WHERE status = 'published' AND slug != ? ORDER BY published_at DESC LIMIT 5",
                            [$slug]
                        );
                        if (empty($others)):
                            echo '<p class="text-muted small mb-0">No other articles available.</p>';
                        else:
                            echo '<div class="list-group list-group-flush rounded shadow-xs">';
                            foreach ($others as $oth) {
                                echo '<a href="blogs.php?slug=' . urlencode($oth['slug']) . '" class="list-group-item list-group-item-action py-2.5 small">';
                                echo '<strong class="text-primary-color d-block">' . htmlspecialchars($oth['title']) . '</strong>';
                                echo '<span class="text-muted" style="font-size: 10px;">' . formatDate($oth['published_at']) . '</span>';
                                echo '</a>';
                            }
                            echo '</div>';
                        endif;
                    ?>
                </div>

                <div class="card border-0 shadow-sm p-4 text-center bg-dark text-white rounded-3">
                    <i class="fa-solid fa-pen-nib fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1">Share Your Knowledge</h6>
                    <p class="small text-white-50 mb-3">Submit your technical research, tutorials, or campus projects to the editorial board.</p>
                    <a href="submit-blog.php" class="btn btn-warning btn-sm font-semibold rounded-pill px-4">Submit an Article</a>
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
    $settings = $cms->getSiteSettings();
    $blogs_bg = !empty($settings['blogs_hero_image']) ? uploadUrl('settings', $settings['blogs_hero_image']) : null;
    ?>

    <!-- Breadcrumb Header -->
    <div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $blogs_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$blogs_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
        <div class="container position-relative z-1 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="fw-extrabold mb-1 text-white display-5">Blogs Portal</h1>
                <p class="lead opacity-75 mb-3">Tech Insights, Student Articles, and Academic Innovations</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active text-warning" aria-current="page">Blogs</li>
                    </ol>
                </nav>
            </div>
            <a href="submit-blog.php" class="btn btn-warning rounded-pill px-4 py-2 fw-bold shadow-sm"><i class="fa-solid fa-pen-to-square me-1"></i> Write a Blog</a>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Live Search Bar -->
        <div class="row mb-4 justify-content-center">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-warning"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="blogSearchInput" class="form-control border-start-0" placeholder="Search blog articles by title or keyword...">
                </div>
            </div>
        </div>

        <div class="row g-4" id="blogsGrid">
            <?php if (empty($blogs)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fa-solid fa-newspaper fa-4x mb-3 text-secondary"></i>
                    <p>No blog articles have been published yet.</p>
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
                        <div class="card custom-card h-100 border-0 shadow-sm hover-elevate">
                            <?php if ($blog['image_path']): ?>
                                <img src="<?php echo uploadUrl('blogs', $blog['image_path']); ?>" class="card-img-top" alt="Cover" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fa-solid fa-pen-nib fa-3x text-warning"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column justify-content-between p-3.5">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i><?php echo formatDate($blog['published_at']); ?></span>
                                        <span class="badge bg-secondary opacity-75" style="font-size: 10px;"><?php echo htmlspecialchars($blog['department_name'] ?? 'General'); ?></span>
                                    </div>
                                    <h5 class="fw-bold text-primary-color mb-2"><?php echo htmlspecialchars($blog['title']); ?></h5>
                                    <p class="text-muted small mb-3"><?php echo truncate(htmlspecialchars($blog['content']), 120); ?></p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <small class="text-muted font-semibold"><i class="fa-regular fa-clock me-1"></i><?php echo $readMinutes; ?> min read</small>
                                    <a href="blogs.php?slug=<?php echo urlencode($blog['slug']); ?>" class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded font-semibold" style="font-size: 11px;">Read Full <i class="fa-solid fa-arrow-right small ms-1"></i></a>
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
