<?php
$page_title = "Campus Gallery";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$selected_cat = isset($_GET['category']) ? $_GET['category'] : '';

// Get gallery images
$images = $cms->db->fetchAll("SELECT * FROM gallery WHERE is_active = 1 ORDER BY created_at DESC");
$settings = $cms->getSiteSettings();
$gallery_bg = !empty($settings['gallery_hero_image']) ? uploadUrl('settings', $settings['gallery_hero_image']) : null;
?>

<!-- Breadcrumb Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $gallery_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$gallery_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">Campus Gallery</h1>
        <p class="lead opacity-75 mb-3">Explore snapshots of our state-of-the-art campus, laboratories, events, and student life</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Gallery</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <!-- Category Filters -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <div class="d-inline-flex gap-2 flex-wrap bg-light p-2 rounded-pill shadow-xs">
                <button class="btn btn-sm btn-primary rounded-pill px-4 gallery-filter-btn active" data-filter="all">All Photos</button>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-4 gallery-filter-btn" data-filter="campus">Campus Life</button>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-4 gallery-filter-btn" data-filter="labs">Research Labs</button>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-4 gallery-filter-btn" data-filter="events">Events & Fests</button>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-4 gallery-filter-btn" data-filter="sports">Sports Arena</button>
            </div>
        </div>
    </div>

    <!-- Gallery Grid -->
    <div class="row g-4" id="galleryGrid">
        <?php if (empty($images)): ?>
            <!-- Default mock gallery photos when DB is empty -->
            <div class="col-md-4 gallery-item-col" data-cat="campus">
                <div class="card border-0 shadow-sm overflow-hidden custom-card h-100 hover-elevate">
                    <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=600&q=80" alt="Campus View" class="w-100 gallery-preview-img" style="height: 250px; object-fit: cover; cursor: pointer;">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1">SOET Main Administration Block</h6>
                        <span class="badge bg-warning text-dark text-uppercase" style="font-size: 9px;">Campus</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 gallery-item-col" data-cat="labs">
                <div class="card border-0 shadow-sm overflow-hidden custom-card h-100 hover-elevate">
                    <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=600&q=80" alt="Lab View" class="w-100 gallery-preview-img" style="height: 250px; object-fit: cover; cursor: pointer;">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1">Advanced Computer Science Lab</h6>
                        <span class="badge bg-warning text-dark text-uppercase" style="font-size: 9px;">Labs</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 gallery-item-col" data-cat="events">
                <div class="card border-0 shadow-sm overflow-hidden custom-card h-100 hover-elevate">
                    <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=600&q=80" alt="Convocation Day" class="w-100 gallery-preview-img" style="height: 250px; object-fit: cover; cursor: pointer;">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1">Annual Tech Fest Exhibition</h6>
                        <span class="badge bg-warning text-dark text-uppercase" style="font-size: 9px;">Events</span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($images as $img): 
                $catSlug = strtolower(htmlspecialchars($img['category'] ?? 'campus'));
            ?>
                <div class="col-md-4 col-sm-6 gallery-item-col" data-cat="<?php echo $catSlug; ?>">
                    <div class="card border-0 shadow-sm overflow-hidden custom-card h-100 hover-elevate">
                        <img src="<?php echo uploadUrl('gallery', $img['image_path']); ?>" alt="<?php echo htmlspecialchars($img['title']); ?>" class="w-100 gallery-preview-img" style="height: 250px; object-fit: cover; cursor: pointer;">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($img['title']); ?></h6>
                            <span class="badge bg-warning text-dark text-uppercase" style="font-size: 9px;"><?php echo htmlspecialchars($img['category']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Lightbox Modal -->
<div class="modal fade" id="galleryLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-dark text-white">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-semibold text-warning" id="lightboxTitle">Image Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="lightboxImg" src="" alt="Enlarged view" class="img-fluid rounded" style="max-height: 75vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Instant Category Filtering
    const buttons = document.querySelectorAll('.gallery-filter-btn');
    const items = document.querySelectorAll('.gallery-item-col');

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            buttons.forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary', 'active');

            const filter = this.getAttribute('data-filter');

            items.forEach(item => {
                const cat = item.getAttribute('data-cat') || '';
                if (filter === 'all' || cat.includes(filter)) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        });
    });

    // 2. Lightbox preview modal
    const lightboxModal = new bootstrap.Modal(document.getElementById('galleryLightboxModal'));
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxTitle = document.getElementById('lightboxTitle');

    document.querySelectorAll('.gallery-preview-img').forEach(img => {
        img.addEventListener('click', function() {
            const src = this.getAttribute('src');
            const alt = this.getAttribute('alt') || 'Campus Photo';
            lightboxImg.src = src;
            lightboxTitle.innerText = alt;
            lightboxModal.show();
        });
    });
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
