<?php
/**
 * Homepage Student Activities & Highlights Manager Module
 *
 * Manages promotional activity cards displayed on the public landing page:
 * - Activity creation (Title, Badge, Year/Tag, Detailed summary, Photo)
 * - Sort order configuration for public layout
 * - Visibility toggling (Active / Inactive)
 * - Student activities CSV export
 *
 * Required Permission: 'manage_events'
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_events'); // Event & Campus Life managers

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_activity = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT title, badge, year_or_tag, description, image_path, sort_order, is_active, created_at FROM homepage_activities ORDER BY sort_order ASC, id ASC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Homepage_Activities_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Activity Title', 'Badge', 'Year / Tag', 'Description', 'Image File', 'Sort Order', 'Active Status', 'Created Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['title'],
            $r['badge'],
            $r['year_or_tag'],
            $r['description'],
            $r['image_path'] ?? 'None',
            $r['sort_order'],
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Homepage Student Activities Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_activity = $db->fetchOne("SELECT * FROM homepage_activities WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_activity'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $act_id = isset($_POST['act_id']) ? (int)$_POST['act_id'] : null;
    $title = trim($_POST['title']);
    $badge = trim($_POST['badge']);
    $year_or_tag = trim($_POST['year_or_tag']);
    $description = trim($_POST['description']);
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $image_path = $edit_activity ? $edit_activity['image_path'] : null;

    if (isset($_FILES['activity_image']) && $_FILES['activity_image']['error'] === UPLOAD_ERR_OK) {
        $img_errors = Security::validateUpload($_FILES['activity_image'], ALLOWED_IMAGE_TYPES);
        if (empty($img_errors)) {
            $uploaded = Security::uploadFile($_FILES['activity_image'], 'events', 'act_');
            if ($uploaded) {
                $image_path = $uploaded;
            }
        } else {
            setFlash('danger', implode(' ', $img_errors));
        }
    }

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($description)) {
        setFlash('danger', 'Please enter activity title and description.');
    } else {
        $data = [
            'title' => $title,
            'badge' => $badge ?: 'Conclave',
            'year_or_tag' => $year_or_tag ?: '2026',
            'description' => $description,
            'image_path' => $image_path,
            'sort_order' => $sort_order,
            'is_active' => $is_active
        ];

        if ($act_id) {
            $db->update('homepage_activities', $data, 'id = ?', [$act_id]);
            setFlash('success', 'Homepage activity card updated successfully.');
        } else {
            $db->insert('homepage_activities', $data);
            setFlash('success', 'New student activity card created successfully.');
        }
        redirect('homepage_activities.php');
    }
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('homepage_activities', 'id = ?', [$target_id]);
    setFlash('success', 'Activity card deleted successfully.');
    redirect('homepage_activities.php');
}

// Handle Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $act_id = (int)$_GET['id'];
    $current = $db->fetchOne("SELECT is_active FROM homepage_activities WHERE id = ?", [$act_id]);
    if ($current) {
        $new_status = $current['is_active'] ? 0 : 1;
        $db->update('homepage_activities', ['is_active' => $new_status], 'id = ?', [$act_id]);
        setFlash('success', 'Activity visibility updated.');
    }
    redirect('homepage_activities.php');
}

// Fetch all activities
$activities = $db->fetchAll("SELECT * FROM homepage_activities ORDER BY sort_order ASC, id ASC");
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Campus Life &amp; Events</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Homepage Student Activities Manager</h2>
        <p class="text-muted small mb-0">Manage technical conclaves, hackathons, championships, and student life highlights shown on the homepage</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="homepage_activities.php?action=export_csv" class="btn btn-sm btn-success rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="homepage_activities.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-plus me-1 text-warning"></i> Add Activity Card
            </a>
        </div>
    <?php else: ?>
        <a href="homepage_activities.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold">
            <i class="fa-solid fa-angle-left me-1"></i> Back to List
        </a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-trophy text-warning me-2"></i>Homepage Activities (<?php echo count($activities); ?>)</h5>
            <input type="text" id="actSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search title, badge, tag..." style="width: 250px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="activitiesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 10%;">Cover</th>
                            <th>Activity Title</th>
                            <th>Badge / Track</th>
                            <th>Year / Tag</th>
                            <th>Sort Order</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activities)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">No homepage activity cards created yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activities as $act): ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php if (!empty($act['image_path'])): ?>
                                            <img src="<?php echo uploadUrl('events', $act['image_path']); ?>" alt="Cover" class="rounded-3 shadow-xs" style="width: 52px; height: 36px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-3 bg-light text-muted d-flex align-items-center justify-content-center border" style="width: 52px; height: 36px; font-size: 14px;">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="font-display fw-bold text-dark"><?php echo htmlspecialchars($act['title']); ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 320px;"><?php echo htmlspecialchars($act['description']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo htmlspecialchars($act['badge']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><?php echo htmlspecialchars($act['year_or_tag']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo (int)$act['sort_order']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="homepage_activities.php?toggle_status=1&id=<?php echo $act['id']; ?>" class="text-decoration-none" title="Click to toggle status">
                                            <?php echo statusBadge($act['is_active'] ? 'active' : 'inactive'); ?>
                                        </a>
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        <a href="homepage_activities.php?action=edit&id=<?php echo $act['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                        </a>
                                        <a href="homepage_activities.php?action=delete&id=<?php echo $act['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 btn-delete-confirm font-semibold" onclick="return confirm('Delete this activity card?');">
                                            <i class="fa-solid fa-trash-can me-1"></i> Delete
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
<?php else: ?>
    <!-- Add / Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                    <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0 font-display"><?php echo $edit_activity ? 'Edit Homepage Activity Card' : 'Create Homepage Activity Card'; ?></h4>
                        <small class="text-muted">Fill out the details below to display this activity highlight on the homepage</small>
                    </div>
                </div>

                <form method="POST" action="homepage_activities.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_activity): ?>
                        <input type="hidden" name="act_id" value="<?php echo $edit_activity['id']; ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label font-semibold text-dark small">Activity Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" required value="<?php echo $edit_activity ? htmlspecialchars($edit_activity['title']) : ''; ?>" placeholder="e.g. National Technozion Hackathon">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Badge Label</label>
                            <input type="text" name="badge" class="form-control rounded-3" value="<?php echo $edit_activity ? htmlspecialchars($edit_activity['badge']) : 'Conclave'; ?>" placeholder="e.g. Conclave / Hackathon / Championship">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Year or Tag</label>
                            <input type="text" name="year_or_tag" class="form-control rounded-3" value="<?php echo $edit_activity ? htmlspecialchars($edit_activity['year_or_tag']) : '2026'; ?>" placeholder="e.g. 2026 / State Level">
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold text-dark small">Activity Summary / Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control rounded-3" rows="3" required placeholder="Describe what students achieved or participated in..."><?php echo $edit_activity ? htmlspecialchars($edit_activity['description']) : ''; ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Display Sort Order</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="<?php echo $edit_activity ? (int)$edit_activity['sort_order'] : 0; ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark small">Activity Card Photo</label>
                            <input type="file" name="activity_image" class="form-control rounded-3 mb-2" accept="image/*">
                            <?php if ($edit_activity && !empty($edit_activity['image_path'])): ?>
                                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3 border">
                                    <img src="<?php echo uploadUrl('events', $edit_activity['image_path']); ?>" class="rounded-2" style="width: 48px; height: 32px; object-fit: cover;">
                                    <small class="text-muted">Current photo attached</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="actActive" <?php echo (!$edit_activity || $edit_activity['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="actActive">Active &amp; Display on Homepage</label>
                            </div>
                        </div>

                        <div class="col-12 mt-4 pt-2 border-top d-flex justify-content-end gap-2">
                            <a href="homepage_activities.php" class="btn btn-secondary rounded-pill px-4">Cancel</a>
                            <button type="submit" name="save_activity" class="btn btn-primary rounded-pill px-4 font-semibold shadow-sm hover-lift">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> <?php echo $edit_activity ? 'Update Activity Card' : 'Create Activity Card'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('actSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#activitiesTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
