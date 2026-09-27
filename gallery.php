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
<div class="page-hero mb-5" style="<?php echo $gallery_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 58, 95, 0.85) 100%), url('{$gallery_bg}') center/cover no-repeat;" : ""; ?>">
    <div class="container position-relative z-1">
        <h1 class="font-display fw-extrabold mb-2 text-white">Campus Photo Gallery</h1>
        <p class="lead opacity-80 mb-3">Explore snapshots of our state-of-the-art campus, laboratories, cultural events, and student life</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Gallery</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <!-- Category Filters -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <div class="d-inline-flex gap-2 flex-wrap bg-white p-2 rounded-pill shadow-xs border">
                <button class="btn btn-sm btn-primary rounded-pill px-4 gallery-filter-btn active font-semibold" data-filter="all"><i class="fa-solid fa-border-all me-1"></i> All Photos</button>
                <button class="btn btn-sm btn-outline-secondary border-0 rounded-pill px-4 gallery-filter-btn font-semibold" data-filter="campus"><i class="fa-solid fa-building me-1"></i> Campus Life</button>
                <button class="btn btn-sm btn-outline-secondary border-0 rounded-pill px-4 gallery-filter-btn font-semibold" data-filter="labs"><i class="fa-solid fa-flask-vial me-1"></i> Research Labs</button>
                <button class="btn btn-sm btn-outline-secondary border-0 rounded-pill px-4 gallery-filter-btn font-semibold" data-filter="events"><i class="fa-solid fa-calendar-check me-1"></i> Events & Fests</button>
                <button class="btn btn-sm btn-outline-secondary border-0 rounded-pill px-4 gallery-filter-btn font-semibold" data-filter="sports"><i class="fa-solid fa-volleyball me-1"></i> Sports Arena</button>
            </div>
        </div>
    </div>

    <!-- Gallery Grid -->
    <div class="row g-4" id="galleryGrid">
        <?php if (empty($images)): ?>
            <!-- Default mock gallery photos when DB is empty -->
            <div class="col-md-4 col-sm-6 gallery-item-col" data-cat="campus">
                <div class="card border-0 shadow-sm overflow-hidden rounded-4 h-100 hover-lift transition-all">
                    <div class="overflow-hidden" style="height: 250px;">
                        <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=600&q=80" alt="Campus View" class="w-100 h-100 gallery-preview-img object-fit-cover cursor-pointer hover-zoom transition-all">
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 text-uppercase" style="font-size: 10px;">Campus</span>
                        </div>
                        <h6 class="font-display fw-bold text-dark mb-0">SOET Main Administration Block</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 gallery-item-col" data-cat="labs">
                <div class="card border-0 shadow-sm overflow-hidden rounded-4 h-100 hover-lift transition-all">
                    <div class="overflow-hidden" style="height: 250px;">
                        <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=600&q=80" alt="Lab View" class="w-100 h-100 gallery-preview-img object-fit-cover cursor-pointer hover-zoom transition-all">
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 text-uppercase" style="font-size: 10px;">Labs</span>
                        </div>
                        <h6 class="font-display fw-bold text-dark mb-0">Advanced Computer Science Lab</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 gallery-item-col" data-cat="events">
                <div class="card border-0 shadow-sm overflow-hidden rounded-4 h-100 hover-lift transition-all">
                    <div class="overflow-hidden" style="height: 250px;">
                        <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=600&q=80" alt="Convocation Day" class="w-100 h-100 gallery-preview-img object-fit-cover cursor-pointer hover-zoom transition-all">
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 text-uppercase" style="font-size: 10px;">Events</span>
                        </div>
                        <h6 class="font-display fw-bold text-dark mb-0">Annual Tech Fest Exhibition</h6>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($images as $img): 
                $catSlug = strtolower(htmlspecialchars($img['category'] ?? 'campus'));
            ?>
                <div class="col-md-4 col-sm-6 gallery-item-col" data-cat="<?php echo $catSlug; ?>">
                    <div class="card border-0 shadow-sm overflow-hidden rounded-4 h-100 hover-lift transition-all">
                        <div class="overflow-hidden" style="height: 250px;">
                            <img src="<?php echo uploadUrl('gallery', $img['image_path']); ?>" alt="<?php echo htmlspecialchars($img['title']); ?>" class="w-100 h-100 gallery-preview-img object-fit-cover cursor-pointer hover-zoom transition-all">
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 text-uppercase" style="font-size: 10px;"><?php echo htmlspecialchars($img['category']); ?></span>
                            </div>
                            <h6 class="font-display fw-bold text-dark mb-0"><?php echo htmlspecialchars($img['title']); ?></h6>
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
        <div class="modal-content border-0 rounded-4 shadow-xl overflow-hidden" style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px);">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title font-display fw-bold text-white" id="lightboxTitle">Image Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="lightboxImg" src="" alt="Enlarged view" class="img-fluid rounded-3 shadow-lg" style="max-height: 75vh; object-fit: contain;">
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
