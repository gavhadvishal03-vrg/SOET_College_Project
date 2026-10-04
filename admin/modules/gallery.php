<?php
/**
 * Campus Photo Gallery & Media Assets Manager Module
 *
 * Provides full administrative control over public photo galleries:
 * - Upload and catalog campus media across multiple categories (Campus, Lab, Event, Sports)
 * - Assign featured status badges and custom display sort ordering
 * - Validates image formats and safely uploads to assets/uploads/gallery/
 * - Complete gallery roster CSV export
 *
 * Required Permission: 'manage_gallery'
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_gallery');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_gal = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT title, category, is_featured, sort_order, image_path, is_active, created_at FROM gallery ORDER BY is_featured DESC, sort_order ASC, created_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Gallery_Media_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Image Title', 'Category', 'Featured', 'Sort Order', 'Image File', 'Active Status', 'Uploaded Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['title'],
            $r['category'],
            $r['is_featured'] ? 'Yes' : 'No',
            $r['sort_order'] ?? 0,
            $r['image_path'],
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Campus Gallery & Media Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_gal = $db->fetchOne("SELECT * FROM gallery WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gallery'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $gal_id = isset($_POST['gal_id']) ? (int)$_POST['gal_id'] : null;
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $custom_category = trim($_POST['custom_category'] ?? '');
    if ($category === '__other__' && !empty($custom_category)) {
        $category = $custom_category;
    }
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($category)) {
        setFlash('danger', 'Please enter an image title and category.');
    } else {
        $uploaded_image = $edit_gal ? $edit_gal['image_path'] : null;
        $valid_upload = true;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $errors = Security::validateUpload($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if (!empty($errors)) {
                setFlash('danger', implode(' ', $errors));
                $valid_upload = false;
            } else {
                $uploaded_image = Security::uploadFile($_FILES['image'], 'gallery', 'gal_');
                if (!$uploaded_image) {
                    setFlash('danger', 'Failed to upload image file.');
                    $valid_upload = false;
                }
            }
        } elseif (!$edit_gal) {
            setFlash('danger', 'Please upload an image file.');
            $valid_upload = false;
        }

        if ($valid_upload) {
            $data = [
                'title' => $title,
                'category' => $category,
                'image_path' => $uploaded_image,
                'is_featured' => $is_featured,
                'sort_order' => $sort_order,
                'is_active' => $is_active
            ];

            if ($gal_id) {
                // Edit
                $db->update('gallery', $data, 'id = ?', [$gal_id]);
                setFlash('success', 'Gallery image details modified.');
                redirect('gallery.php');
            } else {
                // Add
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('gallery', $data);
                setFlash('success', 'Image added to campus gallery.');
                redirect('gallery.php');
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('gallery', 'id = ?', [$target_id]);
    setFlash('success', 'Media asset removed.');
    redirect('gallery.php');
}

// Handle Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $gal_id = (int)$_GET['id'];
    $current = $db->fetchOne("SELECT is_active FROM gallery WHERE id = ?", [$gal_id]);
    if ($current) {
        $new_status = $current['is_active'] ? 0 : 1;
        $db->update('gallery', ['is_active' => $new_status], 'id = ?', [$gal_id]);
        setFlash('success', 'Gallery item visibility updated.');
    }
    redirect('gallery.php');
}

// Fetch all gallery images
$gallery_items = $db->fetchAll("SELECT * FROM gallery ORDER BY is_featured DESC, sort_order ASC, created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Media &amp; Press</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Campus Gallery Management</h2>
        <p class="text-muted small mb-0">Manage campus photography, laboratory facilities, event albums, workshops, and student achievements</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="gallery.php?action=export_csv" class="btn btn-sm btn-success rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="gallery.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-cloud-arrow-up me-1 text-warning"></i> Upload Image
            </a>
        </div>
    <?php else: ?>
        <a href="gallery.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold">
            <i class="fa-solid fa-angle-left me-1"></i> Back to List
        </a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-photo-film text-warning me-2"></i>Uploaded Media Assets (<?php echo count($gallery_items); ?>)</h5>
            <input type="text" id="gallerySearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search gallery title, category..." style="width: 260px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="galleryTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 12%;">Preview</th>
                            <th>Image Title</th>
                            <th>Category</th>
                            <th class="text-center">Featured</th>
                            <th>Sort Order</th>
                            <th>Date Uploaded</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gallery_items)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">No gallery items uploaded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($gallery_items as $g): ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php if (!empty($g['image_path'])): ?>
                                            <img src="<?php echo uploadUrl('gallery', $g['image_path']); ?>" alt="Preview" class="rounded-3 shadow-xs" style="width: 54px; height: 38px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded-3 text-muted d-flex align-items-center justify-content-center border" style="width: 54px; height: 38px; font-size: 14px;">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="font-display fw-bold text-dark mb-0.5"><?php echo htmlspecialchars($g['title']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1">
                                            <?php echo htmlspecialchars($g['category']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($g['is_featured'])): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-star me-1"></i> Featured</span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo (int)($g['sort_order'] ?? 0); ?></span></td>
                                    <td><small class="text-muted"><?php echo formatDate($g['created_at']); ?></small></td>
                                    <td class="text-center">
                                        <a href="gallery.php?toggle_status=1&id=<?php echo $g['id']; ?>" class="text-decoration-none" title="Click to toggle status">
                                            <?php echo statusBadge($g['is_active'] ? 'active' : 'inactive'); ?>
                                        </a>
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        <a href="gallery.php?action=edit&id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1" title="Edit">
                                            <i class="fa-solid fa-pen me-1"></i> Edit
                                        </a>
                                        <a href="gallery.php?action=delete&id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 btn-delete-confirm font-semibold" title="Delete" onclick="return confirm('Delete this gallery photo?');">
                                            <i class="fa-solid fa-trash me-1"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('gallerySearchInput');
        const table = document.getElementById('galleryTable');
        if (searchInput && table) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                table.querySelectorAll('tbody tr').forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = (!q || text.includes(q)) ? '' : 'none';
                });
            });
        }
    });
    </script>
<?php else: ?>
    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                    <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-images"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0 font-display"><?php echo $edit_gal ? 'Modify Image Details' : 'Upload Campus Image'; ?></h4>
                        <small class="text-muted">Select an image file, assign a gallery category, and set display priority</small>
                    </div>
                </div>

                <form method="POST" action="gallery.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_gal): ?>
                        <input type="hidden" name="gal_id" value="<?php echo $edit_gal['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Image Title / Caption <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" required value="<?php echo htmlspecialchars($edit_gal['title'] ?? ''); ?>" placeholder="e.g. Industrial Robotics Training Bench">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Gallery Category <span class="text-danger">*</span></label>
                            <?php
                            $standardCategories = [
                                'Laboratories' => 'Laboratories & Research Centers',
                                'Campus' => 'Campus Life & Aerial Infrastructure',
                                'Classroom' => 'Interactive Smart Classrooms & Central Library',
                                'Technical Events' => 'Technical Events, Hackathons & Exhibitions',
                                'Cultural Events' => 'Cultural Events & Gatherings',
                                'Fest' => 'Annual Supernova Fest',
                                'Workshops' => 'Workshops & Hands-On Bootcamps',
                                'Seminars' => 'Seminars & Guest Lectures',
                                'Sports' => 'Sports, Tournaments & Athletics',
                                'Placements' => 'Placements & Corporate Relations',
                                'Achievements' => 'Student Achievements & Awards',
                                'Student Activities' => 'Student Clubs & NSS Activities'
                            ];
                            $currentCat = $edit_gal['category'] ?? 'Laboratories';
                            ?>
                            <select name="category" class="form-select rounded-3" required id="categorySelect">
                                <?php foreach ($standardCategories as $catVal => $catLabel): ?>
                                    <option value="<?php echo $catVal; ?>" <?php echo ($currentCat === $catVal) ? 'selected' : ''; ?>><?php echo htmlspecialchars($catLabel); ?></option>
                                <?php endforeach; ?>
                                <option value="__other__" <?php echo (!array_key_exists($currentCat, $standardCategories) && !empty($currentCat)) ? 'selected' : ''; ?>>+ Other Custom Category...</option>
                            </select>
                        </div>

                        <div class="col-12" id="customCatDiv" style="<?php echo (!array_key_exists($currentCat, $standardCategories) && !empty($currentCat)) ? '' : 'display:none;'; ?>">
                            <label class="form-label font-semibold text-dark small">Custom Category Name</label>
                            <input type="text" name="custom_category" class="form-control rounded-3" value="<?php echo (!array_key_exists($currentCat, $standardCategories)) ? htmlspecialchars($currentCat) : ''; ?>" placeholder="Enter custom category title...">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Display Sort Order</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="<?php echo $edit_gal ? (int)($edit_gal['sort_order'] ?? 0) : 0; ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Select Photograph <?php echo $edit_gal ? '<small class="text-muted">(leave blank to keep current)</small>' : '*'; ?></label>
                            <input type="file" name="image" class="form-control rounded-3" accept="image/*" <?php echo $edit_gal ? '' : 'required'; ?>>
                        </div>

                        <?php if ($edit_gal && !empty($edit_gal['image_path'])): ?>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-3 border">
                                    <img src="<?php echo uploadUrl('gallery', $edit_gal['image_path']); ?>" alt="Current" class="img-thumbnail" style="height: 60px;">
                                    <div>
                                        <small class="text-muted d-block">Current image active on website</small>
                                        <code class="small"><?php echo htmlspecialchars($edit_gal['image_path']); ?></code>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="isFeaturedCheck" <?php echo ($edit_gal && $edit_gal['is_featured']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold text-dark" for="isFeaturedCheck">Feature on Homepage / Highlights</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_gal || $edit_gal['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold text-dark" for="isActive">Display in Public Gallery</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4 pt-2 border-top d-flex justify-content-end gap-2">
                            <a href="gallery.php" class="btn btn-secondary rounded-pill px-4">Cancel</a>
                            <button type="submit" name="save_gallery" class="btn btn-primary rounded-pill px-4 font-semibold shadow-sm hover-lift">
                                <i class="fa-solid fa-floppy-disk me-1"></i> <?php echo $edit_gal ? 'Update Image' : 'Save & Upload'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('categorySelect').addEventListener('change', function() {
        var customDiv = document.getElementById('customCatDiv');
        if (this.value === '__other__') {
            customDiv.style.display = 'block';
        } else {
            customDiv.style.display = 'none';
        }
    });
    </script>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
