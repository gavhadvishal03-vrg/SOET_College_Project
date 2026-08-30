<?php
$page_title = "News Portal";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$slug = isset($_GET['slug']) ? Security::escape($_GET['slug']) : '';

if (!empty($slug)) {
    // Single News View
    $news = $cms->db->fetchOne(
        "SELECT n.*, u.full_name as author_name FROM news n 
         JOIN users u ON n.author_id = u.id 
         WHERE n.slug = ? AND n.status = 'published'",
        [$slug]
    );

    if (!$news) {
        setFlash('danger', 'News article not found or not published.');
        redirect('news.php');
    }

    $wordCount = str_word_count(strip_tags($news['content']));
    $readMinutes = max(1, ceil($wordCount / 200));
    ?>
    <!-- Breadcrumb Header -->
    <div class="bg-light py-4 border-bottom mb-5">
        <div class="container">
            <h1 class="fw-bold mb-1 text-primary-color"><?php echo htmlspecialchars($news['title']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="news.php" class="text-decoration-none">News Portal</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo truncate(htmlspecialchars($news['title']), 30); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- News details -->
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                    <div class="d-flex align-items-center gap-3 mb-4 text-muted small flex-wrap">
                        <span><i class="fa-regular fa-calendar me-1 text-warning"></i><?php echo formatDate($news['published_at']); ?></span>
                        <span><i class="fa-solid fa-user-pen me-1 text-primary"></i>Reported by <?php echo htmlspecialchars($news['author_name']); ?></span>
                        <span class="badge bg-light text-dark border"><i class="fa-regular fa-clock me-1"></i><?php echo $readMinutes; ?> min read</span>
                    </div>

                    <?php if ($news['image_path']): ?>
                        <div class="text-center mb-4 rounded overflow-hidden shadow-xs" style="max-height: 400px;">
                            <img src="<?php echo uploadUrl('news', $news['image_path']); ?>" alt="News Banner" class="img-fluid w-100" style="object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <div class="news-content" style="line-height: 1.85; font-size: 16px; color: #2d3748;">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </div>
                </div>
            </div>

            <!-- Recent News sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 bg-light mb-4 rounded-3">
                    <h5 class="fw-bold text-primary-color mb-3"><i class="fa-solid fa-bullhorn text-warning me-2"></i>Recent Press Releases</h5>
                    <?php 
                        $others = $cms->db->fetchAll(
                            "SELECT title, slug, published_at FROM news WHERE status = 'published' AND slug != ? ORDER BY published_at DESC LIMIT 5",
                            [$slug]
                        );
                        if (empty($others)):
                            echo '<p class="text-muted small mb-0">No other news headlines available.</p>';
                        else:
                            echo '<div class="list-group list-group-flush rounded shadow-xs">';
                            foreach ($others as $oth) {
                                echo '<a href="news.php?slug=' . urlencode($oth['slug']) . '" class="list-group-item list-group-item-action py-2.5 small">';
                                echo '<strong class="text-primary-color d-block">' . htmlspecialchars($oth['title']) . '</strong>';
                                echo '<span class="text-muted" style="font-size: 10px;">' . formatDate($oth['published_at']) . '</span>';
                                echo '</a>';
                            }
                            echo '</div>';
                        endif;
                    ?>
                </div>

                <div class="card border-0 shadow-sm p-4 text-center bg-dark text-white rounded-3">
                    <i class="fa-solid fa-paper-plane fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1">Campus Media Desk</h6>
                    <p class="small text-white-50 mb-3">Submit official departmental news, academic press releases, or conference coverage.</p>
                    <a href="submit-news.php" class="btn btn-warning btn-sm font-semibold rounded-pill px-4">Submit News Report</a>
                </div>
            </div>
        </div>
    </div>

    <?php
} else {
    // News List View
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $paginator = $cms->paginate('news', "status = 'published'", [], $page, 6);
    $news_list = $cms->db->fetchAll(
        "SELECT n.*, u.full_name as author_name FROM news n 
         JOIN users u ON n.author_id = u.id 
         WHERE n.status = 'published' ORDER BY n.published_at DESC LIMIT ? OFFSET ?",
        [$paginator['per_page'], $paginator['offset']]
    );
    $settings = $cms->getSiteSettings();
    $news_bg = !empty($settings['news_hero_image']) ? uploadUrl('settings', $settings['news_hero_image']) : null;
    ?>

    <!-- Breadcrumb Header -->
    <div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $news_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$news_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
        <div class="container position-relative z-1 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="fw-extrabold mb-1 text-white display-5">News Portal</h1>
                <p class="lead opacity-75 mb-3">Official Announcements, Press Releases, and Achievements</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active text-warning" aria-current="page">News</li>
                    </ol>
                </nav>
            </div>
            <a href="submit-news.php" class="btn btn-warning rounded-pill px-4 py-2 fw-bold shadow-sm"><i class="fa-solid fa-paper-plane me-1"></i> Submit News</a>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Live Search Bar -->
        <div class="row mb-4 justify-content-center">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-warning"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="newsSearchInput" class="form-control border-start-0" placeholder="Search news articles or headlines...">
                </div>
            </div>
        </div>

        <div class="row g-4" id="newsGrid">
            <?php if (empty($news_list)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fa-solid fa-newspaper fa-4x mb-3 text-secondary"></i>
                    <p>No news updates have been published yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($news_list as $news): 
                    $wordCount = str_word_count(strip_tags($news['content']));
                    $readMinutes = max(1, ceil($wordCount / 200));
                ?>
                    <div class="col-md-6 col-lg-4 news-card-col"
                         data-title="<?php echo strtolower(htmlspecialchars($news['title'])); ?>"
                         data-content="<?php echo strtolower(htmlspecialchars(substr($news['content'], 0, 200))); ?>">
                        <div class="card custom-card h-100 border-0 shadow-sm hover-elevate">
                            <?php if ($news['image_path']): ?>
                                <img src="<?php echo uploadUrl('news', $news['image_path']); ?>" class="card-img-top" alt="Cover" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fa-solid fa-newspaper fa-3x text-warning"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column justify-content-between p-3.5">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i><?php echo formatDate($news['published_at']); ?></span>
                                        <span class="badge bg-warning text-dark" style="font-size: 10px;">Official News</span>
                                    </div>
                                    <h5 class="fw-bold text-primary-color mb-2"><?php echo htmlspecialchars($news['title']); ?></h5>
                                    <p class="text-muted small mb-3"><?php echo truncate(htmlspecialchars($news['content']), 120); ?></p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <small class="text-muted font-semibold"><i class="fa-regular fa-clock me-1"></i><?php echo $readMinutes; ?> min read</small>
                                    <a href="news.php?slug=<?php echo urlencode($news['slug']); ?>" class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded font-semibold" style="font-size: 11px;">Read Full <i class="fa-solid fa-arrow-right small ms-1"></i></a>
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
                <?php echo renderPagination($paginator['page'], $paginator['total_pages'], 'news.php'); ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('newsSearchInput');
        const cards = document.querySelectorAll('.news-card-col');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                cards.forEach(card => {
                    const title = card.getAttribute('data-title') || '';
                    const content = card.getAttribute('data-content') || '';

                    if (!query || title.includes(query) || content.includes(query)) {
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
