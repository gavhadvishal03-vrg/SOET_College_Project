<?php
/**
 * Campus Facilities & Research Laboratories Manager Module
 *
 * Manages campus amenities and departmental research facilities:
 * - Laboratory and facility creation (Title, Category, Description, FontAwesome icon)
 * - Facility image uploads with format validation
 * - Display sort order and public visibility toggling
 * - Facilities directory CSV export
 *
 * Required Permissions: 'manage_departments', 'manage_gallery', 'backup_restore', or Super Admin
 */
require_once __DIR__ . '/../../core/bootstrap.php';
// Accessible to users with manage_departments, manage_gallery, or backup_restore
Auth::requireLogin();
if (!Auth::hasPermission('manage_departments') && !Auth::hasPermission('manage_gallery') && !Auth::hasPermission('backup_restore') && !Auth::hasRole('Super Admin')) {
    setFlash('danger', 'Access denied.');
    redirect(APP_URL . '/admin/dashboard.php');
}

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_fac = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT title, category, icon, image_path, sort_order, is_active, created_at FROM facilities ORDER BY sort_order ASC, id ASC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Campus_Facilities_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Facility Title', 'Category', 'Icon', 'Image File', 'Display Order', 'Status', 'Date Created']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['title'],
            $r['category'],
            $r['icon'],
            $r['image_path'],
            $r['sort_order'],
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Campus Facilities & Laboratories Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_fac = $db->fetchOne("SELECT * FROM facilities WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_facility'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $fac_id = isset($_POST['fac_id']) ? (int)$_POST['fac_id'] : null;
    $title = trim($_POST['title']);
    $category = trim($_POST['category'] ?? 'Laboratory');
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon'] ?? 'fa-solid fa-microchip');
    $features = trim($_POST['features'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $image_path = $edit_fac ? $edit_fac['image_path'] : null;
    $valid_upload = true;

    if (isset($_FILES['facility_image']) && $_FILES['facility_image']['error'] === UPLOAD_ERR_OK) {
        $errors = Security::validateUpload($_FILES['facility_image'], ALLOWED_IMAGE_TYPES);
        if (!empty($errors)) {
            setFlash('danger', implode(' ', $errors));
            $valid_upload = false;
        } else {
            $uploaded = Security::uploadFile($_FILES['facility_image'], 'facilities', 'facility_');
            if ($uploaded) {
                $image_path = $uploaded;
            } else {
                setFlash('danger', 'Failed to upload facility image.');
                $valid_upload = false;
            }
        }
    }

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($description)) {
        setFlash('danger', 'Please enter facility title and description.');
    } elseif ($valid_upload) {
        $data = [
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'icon' => $icon,
            'image_path' => $image_path,
            'features' => $features,
            'sort_order' => $sort_order,
            'is_active' => $is_active
        ];

        if ($fac_id) {
            $db->update('facilities', $data, 'id = ?', [$fac_id]);
            setFlash('success', 'Campus facility details updated successfully.');
        } else {
            $db->insert('facilities', $data);
            setFlash('success', 'New campus facility / laboratory created successfully.');
        }
        redirect('facilities.php');
    }
}

// Handle Delete (with soft delete / toggle or hard delete)
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('facilities', 'id = ?', [$target_id]);
    setFlash('success', 'Facility removed from directory.');
    redirect('facilities.php');
}

// Handle Status Toggle
if ($action === 'toggle_status' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $curr = $db->fetchOne("SELECT is_active FROM facilities WHERE id = ?", [$target_id]);
    if ($curr) {
        $newStatus = $curr['is_active'] ? 0 : 1;
        $db->update('facilities', ['is_active' => $newStatus], 'id = ?', [$target_id]);
        setFlash('success', 'Facility status updated to ' . ($newStatus ? 'Active' : 'Inactive') . '.');
    }
    redirect('facilities.php');
}

// Fetch all facilities
$facilities = $db->fetchAll("SELECT * FROM facilities ORDER BY sort_order ASC, id ASC");
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Infrastructure & Labs</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Campus Facilities &amp; Laboratories</h2>
        <p class="text-muted small mb-0">Manage cutting-edge laboratories, innovation hubs, smart classrooms, and student amenities</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="facilities.php?action=export_csv" class="btn btn-sm btn-success rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="facilities.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold"><i class="fa-solid fa-plus me-1 text-warning"></i> Add Facility / Lab</a>
        </div>
    <?php else: ?>
        <a href="facilities.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-microchip text-warning me-2"></i>Campus Facilities (<?php echo count($facilities); ?>)</h5>
            <input type="text" id="facSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search facility title, category..." style="width: 250px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="facilitiesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 60px;">Icon</th>
                            <th>Facility Name</th>
                            <th>Category</th>
                            <th>Display Order</th>
                            <th>Key Features</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($facilities)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">No campus facilities registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facilities as $fac): ?>
                                <tr>
                                    <td class="ps-4 text-center">
                                        <div class="rounded-3 d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 42px; height: 42px; background: rgba(37, 99, 235, 0.1); color: #2563eb; font-size: 18px;">
                                            <i class="<?php echo htmlspecialchars($fac['icon'] ?: 'fa-solid fa-microchip'); ?>"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-display fw-bold text-dark mb-0.5"><?php echo htmlspecialchars($fac['title']); ?></div>
                                        <small class="text-muted"><?php echo truncate(htmlspecialchars($fac['description']), 80); ?></small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill"><?php echo htmlspecialchars($fac['category']); ?></span></td>
                                    <td><span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5"><?php echo (int)$fac['sort_order']; ?></span></td>
                                    <td>
                                        <?php if (!empty($fac['features'])): ?>
                                            <small class="text-muted"><?php echo truncate(str_replace("\n", " • ", htmlspecialchars($fac['features'])), 60); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">N/A</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="facilities.php?action=toggle_status&id=<?php echo $fac['id']; ?>" class="text-decoration-none" title="Click to toggle status">
                                            <?php echo statusBadge($fac['is_active'] ? 'active' : 'inactive'); ?>
                                        </a>
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        <a href="facilities.php?action=edit&id=<?php echo $fac['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1" title="Edit"><i class="fa-solid fa-pen me-1"></i> Edit</a>
                                        <a href="facilities.php?action=delete&id=<?php echo $fac['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 btn-delete-confirm font-semibold" title="Delete" onclick="return confirm('Are you sure you want to remove this facility?');"><i class="fa-solid fa-trash me-1"></i> Delete</a>
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
        const searchInput = document.getElementById('facSearchInput');
        const table = document.getElementById('facilitiesTable');
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
    <!-- Add/Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h4 class="fw-bold text-dark mb-4 font-display">
                    <i class="fa-solid fa-microchip text-warning me-2"></i>
                    <?php echo $edit_fac ? 'Edit Facility Details' : 'Register New Facility / Laboratory'; ?>
                </h4>
                <form method="POST" action="facilities.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_fac): ?>
                        <input type="hidden" name="fac_id" value="<?php echo $edit_fac['id']; ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label font-semibold text-dark small">Facility / Laboratory Name <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" required value="<?php echo htmlspecialchars($edit_fac['title'] ?? ''); ?>" placeholder="e.g. Industrial Robotics Lab">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold text-dark small">Category</label>
                            <input type="text" name="category" class="form-control rounded-3" value="<?php echo htmlspecialchars($edit_fac['category'] ?? 'Laboratory'); ?>" placeholder="e.g. Advanced Laboratory, Infrastructure" list="categoryOptions">
                            <datalist id="categoryOptions">
                                <option value="Advanced Laboratory">
                                <option value="Clean Mobility & Power">
                                <option value="Smart Automation">
                                <option value="Startup & R&D Hub">
                                <option value="Academic Infrastructure">
                                <option value="Student Living & Sports">
                            </datalist>
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold text-dark small">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control rounded-3" rows="3" required placeholder="Outline equipment, learning objectives, and lab capacity..."><?php echo htmlspecialchars($edit_fac['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">FontAwesome Icon Class</label>
                            <input type="text" name="icon" class="form-control rounded-3" value="<?php echo htmlspecialchars($edit_fac['icon'] ?? 'fa-solid fa-microchip'); ?>" placeholder="e.g. fa-solid fa-robot">
                            <small class="text-muted" style="font-size: 14px;">Examples: <code>fa-solid fa-robot</code>, <code>fa-solid fa-car-battery</code>, <code>fa-solid fa-gears</code>, <code>fa-solid fa-lightbulb</code>, <code>fa-solid fa-chalkboard-user</code>, <code>fa-solid fa-futbol</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Display Order</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="<?php echo (int)($edit_fac['sort_order'] ?? 0); ?>" min="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold text-dark small">Key Features / Highlights (one per line)</label>
                            <textarea name="features" class="form-control rounded-3" rows="3" placeholder="Industrial Robotic Arms&#10;Kinematics &amp; ROS Programming&#10;Computer Vision Systems"><?php echo htmlspecialchars($edit_fac['features'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label font-semibold text-dark small">Upload Facility Cover Image</label>
                            <input type="file" name="facility_image" class="form-control rounded-3" accept="image/*">
                        </div>

                        <?php if ($edit_fac && !empty($edit_fac['image_path'])): ?>
                            <div class="col-md-4">
                                <label class="form-label font-semibold d-block text-dark small">Current Image</label>
                                <img src="<?php echo uploadUrl('facilities', $edit_fac['image_path']); ?>" alt="Current Cover" class="img-thumbnail rounded-3" style="max-height: 60px;">
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" <?php echo (!isset($edit_fac) || $edit_fac['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold text-dark" for="isActiveCheck">Publish &amp; Show on Public Website</label>
                            </div>
                        </div>

                        <div class="col-12 mt-4 pt-2 border-top">
                            <button type="submit" name="save_facility" class="btn btn-primary rounded-pill px-4 py-2 font-semibold shadow-sm hover-lift">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> <?php echo $edit_fac ? 'Update Facility' : 'Save Facility'; ?>
                            </button>
                            <a href="facilities.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 font-semibold ms-2">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
