<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_gallery');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_gal = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT title, category, image_path, is_active, created_at FROM gallery ORDER BY created_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Gallery_Media_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Image Title', 'Category', 'Image File', 'Active Status', 'Uploaded Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['title'],
            $r['category'],
            $r['image_path'],
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Gallery Manager";
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
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($category)) {
        setFlash('danger', 'Please enter a title and category.');
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
            setFlash('danger', 'Please upload an image.');
            $valid_upload = false;
        }

        if ($valid_upload) {
            if ($gal_id) {
                // Edit
                $db->update('gallery', [
                    'title' => $title,
                    'category' => $category,
                    'image_path' => $uploaded_image,
                    'is_active' => $is_active
                ], 'id = ?', [$gal_id]);
                setFlash('success', 'Gallery image details modified.');
                redirect('gallery.php');
            } else {
                // Add
                $db->insert('gallery', [
                    'title' => $title,
                    'category' => $category,
                    'image_path' => $uploaded_image,
                    'is_active' => $is_active,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
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

// Fetch all gallery images
$gallery_items = $db->fetchAll("SELECT * FROM gallery ORDER BY created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-images text-warning me-2"></i>Campus Gallery Management</h1>
        <small class="text-muted">Manage campus photographs, lab facilities, event albums, and sports media</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="gallery.php?action=export_csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="gallery.php?action=add" class="btn btn-primary btn-sm"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Image</a>
        </div>
    <?php else: ?>
        <a href="gallery.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-photo-film text-warning me-2"></i>Uploaded Media Assets (<?php echo count($gallery_items); ?>)</h5>
            <input type="text" id="gallerySearchInput" class="form-control form-control-sm" placeholder="Search gallery title, category..." style="width: 250px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="galleryTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 15%;">Preview</th>
                            <th>Image Title</th>
                            <th>Category</th>
                            <th>Date Uploaded</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gallery_items)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No gallery items uploaded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($gallery_items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo uploadUrl('gallery', $item['image_path']); ?>" alt="Thumbnail" class="img-fluid rounded border border-warning shadow-xs" style="max-height: 60px; max-width: 90px; object-fit: cover;">
                                    </td>
                                    <td class="fw-bold text-primary-color"><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                    <td><?php echo formatDate($item['created_at']); ?></td>
                                    <td class="text-center"><?php echo statusBadge($item['is_active'] ? 'active' : 'inactive'); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="gallery.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <a href="gallery.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete" onclick="return confirm('Delete this gallery image?');"><i class="fa-solid fa-trash"></i></a>
                                        </div>
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
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-images text-warning me-2"></i><?php echo $edit_gal ? 'Modify Image Details' : 'Upload Campus Image'; ?></h4>
                <form method="POST" action="gallery.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_gal): ?>
                        <input type="hidden" name="gal_id" value="<?php echo $edit_gal['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Image Title *</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($edit_gal['title'] ?? ''); ?>" placeholder="e.g. AI & Robotics Research Lab">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Category Tag *</label>
                            <select name="category" class="form-select" required>
                                <option value="Campus" <?php echo ($edit_gal && $edit_gal['category'] === 'Campus') ? 'selected' : ''; ?>>Campus Life & Infrastructure</option>
                                <option value="Labs" <?php echo ($edit_gal && $edit_gal['category'] === 'Labs') ? 'selected' : ''; ?>>Research Labs & Innovations</option>
                                <option value="Events" <?php echo ($edit_gal && $edit_gal['category'] === 'Events') ? 'selected' : ''; ?>>Hackathons & Cultural Events</option>
                                <option value="Sports" <?php echo ($edit_gal && $edit_gal['category'] === 'Sports') ? 'selected' : ''; ?>>Sports & Athletics</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label font-semibold">Select Photograph <?php echo $edit_gal ? '<small class="text-muted">(leave blank to keep current)</small>' : '*'; ?></label>
                            <input type="file" name="image" class="form-control" accept="image/*" <?php echo $edit_gal ? '' : 'required'; ?>>
                        </div>

                        <?php if ($edit_gal && !empty($edit_gal['image_path'])): ?>
                            <div class="col-md-4">
                                <label class="form-label font-semibold d-block">Current Image</label>
                                <img src="<?php echo uploadUrl('gallery', $edit_gal['image_path']); ?>" alt="Current" class="img-thumbnail" style="height: 60px;">
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_gal || $edit_gal['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActive">Display in Public Gallery</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="gallery.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_gallery" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Image</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
