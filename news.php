<?php
/**
 * Public News & Press Releases Controller & View
 *
 * Displays official university news bulletins, academic achievements,
 * and press announcements:
 * - Single article reading mode with author metadata and reading time estimation
 * - Paginated published news cards with thumbnails and publication dates
 * - Community news submission callout
 */
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
    <!-- Page Hero Banner for Single Article -->
    <div class="page-hero mb-5">
        <div class="container position-relative z-1">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-warning text-dark rounded-pill px-3 py-1 font-semibold" style="font-size: 14px;">Campus Press Release</span>
                <span class="text-white-50 small">&bull;</span>
                <span class="text-white-50 small"><i class="fa-regular fa-clock me-1"></i><?php echo $readMinutes; ?> min read</span>
            </div>
            <h1 class="font-display fw-extrabold mb-3 text-white" style="font-size: 2.2rem; max-width: 850px;"><?php echo htmlspecialchars($news['title']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="news.php" class="text-white opacity-75 text-decoration-none">News Portal</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo truncate(htmlspecialchars($news['title']), 30); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- News details -->
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 p-md-5 bg-white" style="border-radius: 18px; border: 1px solid #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-3 mb-4 text-muted small flex-wrap pb-3 border-bottom">
                        <span><i class="fa-regular fa-calendar me-1 text-warning"></i><?php echo formatDate($news['published_at']); ?></span>
                        <span><i class="fa-solid fa-user-pen me-1 text-primary"></i>Reported by <strong class="text-dark"><?php echo htmlspecialchars($news['author_name']); ?></strong></span>
                    </div>

                    <?php if ($news['image_path']): ?>
                        <div class="text-center mb-4 rounded-3 overflow-hidden shadow-xs" style="max-height: 420px;">
                            <img src="<?php echo uploadUrl('news', $news['image_path']); ?>" alt="News Banner" class="img-fluid w-100" style="object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <div class="news-content" style="line-height: 1.85; font-size: 16px; color: #1e293b;">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </div>
                </div>
            </div>

            <!-- Recent News sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                    <h5 class="fw-bold text-primary-color mb-3 font-display"><i class="fa-solid fa-bullhorn text-warning me-2"></i>Recent Press Releases</h5>
                    <?php 
                        $others = $cms->db->fetchAll(
                            "SELECT title, slug, published_at FROM news WHERE status = 'published' AND slug != ? ORDER BY published_at DESC LIMIT 5",
                            [$slug]
                        );
                        if (empty($others)):
                            echo '<p class="text-muted small mb-0">No other news headlines available.</p>';
                        else:
                            echo '<div class="list-group list-group-flush rounded-3 border overflow-hidden">';
                            foreach ($others as $oth) {
                                echo '<a href="news.php?slug=' . urlencode($oth['slug']) . '" class="list-group-item list-group-item-action py-3 small">';
                                echo '<strong class="text-primary-color d-block font-display mb-1">' . htmlspecialchars($oth['title']) . '</strong>';
                                echo '<span class="text-muted" style="font-size: 14px;"><i class="fa-regular fa-clock me-1"></i>' . formatDate($oth['published_at']) . '</span>';
                                echo '</a>';
                            }
                            echo '</div>';
                        endif;
                    ?>
                </div>

                <div class="card border-0 shadow-sm p-4 text-center text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-radius: 16px; border-top: 3px solid var(--accent) !important;">
                    <i class="fa-solid fa-paper-plane fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1 font-display">Campus Media Desk</h6>
                    <p class="small text-white-50 mb-3" style="font-size: 14px; line-height: 1.5;">Submit official departmental press releases, academic milestones, or research symposium coverage.</p>
                    <a href="submit-news.php" class="btn btn-warning btn-sm font-bold rounded-pill px-4 shadow-xs text-dark">Submit News Report</a>
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
    $news_bg = !empty($settings['news_hero_image']) ? uploadUrl('settings', $settings['news_hero_image']) : uploadUrl('gallery', 'event_experts_live_india.jpg');
    ?>

    <!-- Page Hero Banner for News Grid -->
    <div class="page-hero mb-5" style="<?php echo $news_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{$news_bg}') center/cover no-repeat;" : ""; ?>">
        <div class="container position-relative z-1 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="font-display fw-extrabold mb-2 text-white">Campus News &amp; Media Portal</h1>
                <p class="lead opacity-80 mb-3" style="font-size: 1.1rem; max-width: 650px;">Official institute announcements, academic achievements, research breakthroughs, and press releases.</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">News Portal</li>
                    </ol>
                </nav>
            </div>
            <a href="submit-news.php" class="btn btn-warning rounded-pill px-4 py-2 font-bold shadow-sm text-dark"><i class="fa-solid fa-paper-plane me-1.5"></i> Submit News</a>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Live Search Bar -->
        <div class="row mb-4 justify-content-center">
            <div class="col-md-6">
                <div class="input-group shadow-xs">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="newsSearchInput" class="form-control border-start-0 ps-0" placeholder="Search news articles, topics, or headlines...">
                </div>
            </div>
        </div>

        <div class="row g-4" id="newsGrid">
            <?php if (empty($news_list)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fa-solid fa-newspaper fa-4x mb-3 text-secondary opacity-50"></i>
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
                        <div class="card h-100 border-0 shadow-sm hover-lift bg-white overflow-hidden" style="border-radius: 16px; border: 1px solid #e2e8f0 !important;">
                            <?php if ($news['image_path']): ?>
                                <img src="<?php echo uploadUrl('news', $news['image_path']); ?>" class="card-img-top" alt="Cover" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fa-solid fa-newspaper fa-3x text-warning"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column justify-content-between p-4">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i><?php echo formatDate($news['published_at']); ?></span>
                                        <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 font-bold" style="font-size: 14px;">Official Press</span>
                                    </div>
                                    <h5 class="fw-bold text-primary-color mb-2 font-display"><?php echo htmlspecialchars($news['title']); ?></h5>
                                    <p class="text-muted small mb-3" style="line-height: 1.6;"><?php echo truncate(htmlspecialchars($news['content']), 120); ?></p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                    <small class="text-muted font-semibold"><i class="fa-regular fa-clock me-1"></i><?php echo $readMinutes; ?> min read</small>
                                    <a href="news.php?slug=<?php echo urlencode($news['slug']); ?>" class="btn btn-xs btn-outline-primary py-1 px-3 rounded-pill font-semibold" style="font-size: 14px;">Read Full <i class="fa-solid fa-arrow-right small ms-1"></i></a>
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
