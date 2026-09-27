<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requireLogin();

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';
$edit_item = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT n1.id, n1.title, n1.url, n1.target, n1.icon, n2.title as parent_title, n1.sort_order, n1.is_active 
                           FROM navigation_menu n1 
                           LEFT JOIN navigation_menu n2 ON n1.parent_id = n2.id 
                           ORDER BY COALESCE(n1.parent_id, n1.id), n1.sort_order ASC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Navigation_Menu_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'URL', 'Target', 'Icon Class', 'Parent Item', 'Sort Order', 'Status']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id'],
            $r['title'],
            $r['url'],
            $r['target'],
            $r['icon'],
            $r['parent_title'] ?? 'Top-Level (Main Navbar)',
            $r['sort_order'],
            $r['is_active'] ? 'Active (Visible)' : 'Hidden'
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Dynamic Navigation Menu Manager";
include_once __DIR__ . '/../includes/header.php';

// Handle Delete Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_nav_item'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $nav_id = (int)$_POST['nav_id'];
    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $db->delete('navigation_menu', 'id = ?', [$nav_id]);
        setFlash('success', 'Navigation item deleted successfully.');
        redirect('navigation.php');
    }
}

// Handle Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $nav_id = (int)$_GET['id'];
    $current = $db->fetchOne("SELECT is_active FROM navigation_menu WHERE id = ?", [$nav_id]);
    if ($current) {
        $new_status = $current['is_active'] ? 0 : 1;
        $db->update('navigation_menu', ['is_active' => $new_status], 'id = ?', [$nav_id]);
        setFlash('success', 'Navigation item visibility updated.');
    }
    redirect('navigation.php');
}

// Handle Add/Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_nav_item'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $nav_id = isset($_POST['nav_id']) ? (int)$_POST['nav_id'] : null;
    $title = trim($_POST['title']);
    $url = trim($_POST['url']);
    $target = $_POST['target'] ?? '_self';
    $icon = trim($_POST['icon'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($url)) {
        setFlash('danger', 'Title and URL are required.');
    } else {
        $data = [
            'title' => $title,
            'url' => $url,
            'target' => $target,
            'icon' => $icon,
            'parent_id' => $parent_id,
            'sort_order' => $sort_order,
            'is_active' => $is_active
        ];

        if ($nav_id) {
            $db->update('navigation_menu', $data, 'id = ?', [$nav_id]);
            setFlash('success', 'Navigation item updated successfully.');
        } else {
            $db->insert('navigation_menu', $data);
            setFlash('success', 'New navigation item added successfully.');
        }
        redirect('navigation.php');
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_item = $db->fetchOne("SELECT * FROM navigation_menu WHERE id = ?", [(int)$_GET['id']]);
}

// Fetch all parent candidates
$parentCandidates = $db->fetchAll("SELECT id, title FROM navigation_menu WHERE parent_id IS NULL ORDER BY sort_order ASC, title ASC");

// Fetch all menu items hierarchically
$mainMenuItems = $db->fetchAll("SELECT * FROM navigation_menu WHERE parent_id IS NULL ORDER BY sort_order ASC, title ASC");
$allSubItems = $db->fetchAll("SELECT * FROM navigation_menu WHERE parent_id IS NOT NULL ORDER BY sort_order ASC, title ASC");

$subItemsByParent = [];
foreach ($allSubItems as $sub) {
    $subItemsByParent[$sub['parent_id']][] = $sub;
}
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">Site Navigation</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Navbar Menu Manager</h2>
        <p class="text-muted small mb-0">Manage, add, rename, reorder, and hide menu items &amp; dropdown links in real-time</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="navigation.php?action=export_csv" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs">
            <i class="fa-solid fa-file-excel me-1 text-success"></i> Export CSV
        </a>
        <?php if ($action !== 'add'): ?>
            <a href="navigation.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3.5 shadow-sm hover-lift font-semibold">
                <i class="fa-solid fa-plus me-1.5"></i> Add Menu Item
            </a>
        <?php else: ?>
            <a href="navigation.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Back to Menu List</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <h5 class="fw-bold text-dark border-bottom pb-3 mb-4 font-display">
                    <i class="fa-solid <?php echo $edit_item ? 'fa-pen-to-square' : 'fa-plus'; ?> text-primary me-2"></i>
                    <?php echo $edit_item ? 'Edit Navigation Menu Item' : 'Add New Navigation Menu Item'; ?>
                </h5>
                <form method="POST" action="navigation.php">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="nav_id" value="<?php echo $edit_item['id']; ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Menu Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Scholarships, Research" value="<?php echo htmlspecialchars($edit_item['title'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">URL / Link Path <span class="text-danger">*</span></label>
                            <input type="text" name="url" class="form-control rounded-3" placeholder="e.g. courses.php or https://..." value="<?php echo htmlspecialchars($edit_item['url'] ?? ''); ?>" required>
                            <small class="text-muted" style="font-size: 11px;">Use <code>#</code> for dropdown headers</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Parent Dropdown Category</label>
                            <select name="parent_id" class="form-select rounded-3">
                                <option value="">-- Top-Level Item (Main Navbar Bar) --</option>
                                <?php foreach ($parentCandidates as $pc): ?>
                                    <?php if ($edit_item && $pc['id'] == $edit_item['id']) continue; ?>
                                    <option value="<?php echo $pc['id']; ?>" <?php echo ($edit_item && $edit_item['parent_id'] == $pc['id']) ? 'selected' : ''; ?>>
                                        Dropdown under: <?php echo htmlspecialchars($pc['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">FontAwesome Icon Class</label>
                            <input type="text" name="icon" class="form-control rounded-3" placeholder="e.g. fa-graduation-cap, fa-house" value="<?php echo htmlspecialchars($edit_item['icon'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Sort Order (Lower appears first)</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="<?php echo (int)($edit_item['sort_order'] ?? 10); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Link Target</label>
                            <select name="target" class="form-select rounded-3">
                                <option value="_self" <?php echo ($edit_item && $edit_item['target'] === '_self') ? 'selected' : ''; ?>>Same Window (_self)</option>
                                <option value="_blank" <?php echo ($edit_item && $edit_item['target'] === '_blank') ? 'selected' : ''; ?>>New Tab (_blank)</option>
                            </select>
                        </div>
                        <div class="col-12 mt-3 pt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_item || $edit_item['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold text-dark" for="isActive">Visible in Public Navbar</label>
                            </div>
                        </div>
                        <div class="col-12 text-end mt-4 pt-3 border-top">
                            <a href="navigation.php" class="btn btn-outline-secondary rounded-pill px-4 me-2">Cancel</a>
                            <button type="submit" name="save_nav_item" class="btn btn-primary rounded-pill px-4 py-2 font-semibold shadow-sm hover-lift">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Menu Item
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Navigation Tree & Table View -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-compass"></i>
                </div>
                <h5 class="fw-bold mb-0 text-dark">Public Navigation Menu Tree</h5>
            </div>
            <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="navSearchInput" class="form-control bg-light border-start-0 rounded-end-pill pe-3" placeholder="Filter menu items...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="navTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 80px;">Order</th>
                            <th>Menu Item &amp; Hierarchy</th>
                            <th>Target URL</th>
                            <th>Icon</th>
                            <th class="text-center">Target</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mainMenuItems)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-compass display-6 text-secondary opacity-40 mb-3 d-block"></i>
                                    No navigation items configured.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mainMenuItems as $main): ?>
                                <?php $hasSub = isset($subItemsByParent[$main['id']]); ?>
                                <tr class="bg-light-subtle">
                                    <td class="ps-4"><span class="badge bg-light text-dark border font-monospace"><?php echo $main['sort_order']; ?></span></td>
                                    <td>
                                        <i class="fa-solid <?php echo !empty($main['icon']) ? htmlspecialchars($main['icon']) : 'fa-link'; ?> me-2 text-primary"></i>
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($main['title']); ?></span>
                                        <?php if ($hasSub): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-2" style="font-size: 10px;"><?php echo count($subItemsByParent[$main['id']]); ?> Sub-Items</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="text-dark"><?php echo htmlspecialchars($main['url']); ?></code></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($main['icon'] ?? '—'); ?></small></td>
                                    <td class="text-center"><span class="badge bg-light text-muted border font-monospace"><?php echo $main['target']; ?></span></td>
                                    <td class="text-center">
                                        <a href="navigation.php?toggle_status=1&id=<?php echo $main['id']; ?>" class="badge border text-decoration-none rounded-pill px-2.5 py-1 <?php echo $main['is_active'] ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'; ?>">
                                            <?php echo $main['is_active'] ? 'Active' : 'Hidden'; ?>
                                        </a>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="navigation.php?action=edit&id=<?php echo $main['id']; ?>" class="btn btn-outline-primary rounded-start-pill px-2.5" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                            <button class="btn btn-outline-danger rounded-end-pill px-2.5" onclick="confirmDeleteNav(<?php echo $main['id']; ?>, '<?php echo addslashes(htmlspecialchars($main['title'])); ?>')" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php if ($hasSub): ?>
                                    <?php foreach ($subItemsByParent[$main['id']] as $sub): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><span class="badge bg-light text-muted border font-monospace ms-2">└ <?php echo $sub['sort_order']; ?></span></td>
                                            <td class="ps-4">
                                                <i class="fa-solid fa-arrow-turn-up fa-rotate-90 me-2 text-muted opacity-50 ms-3"></i>
                                                <i class="fa-solid <?php echo !empty($sub['icon']) ? htmlspecialchars($sub['icon']) : 'fa-angle-right'; ?> me-1 text-secondary small"></i>
                                                <span class="fw-medium text-dark"><?php echo htmlspecialchars($sub['title']); ?></span>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($sub['url']); ?></code></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($sub['icon'] ?? '—'); ?></small></td>
                                            <td class="text-center"><span class="badge bg-light text-muted border font-monospace"><?php echo $sub['target']; ?></span></td>
                                            <td class="text-center">
                                                <a href="navigation.php?toggle_status=1&id=<?php echo $sub['id']; ?>" class="badge border text-decoration-none rounded-pill px-2.5 py-1 <?php echo $sub['is_active'] ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'; ?>">
                                                    <?php echo $sub['is_active'] ? 'Active' : 'Hidden'; ?>
                                                </a>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="navigation.php?action=edit&id=<?php echo $sub['id']; ?>" class="btn btn-outline-primary rounded-start-pill px-2.5"><i class="fa-solid fa-pen-to-square"></i></a>
                                                    <button class="btn btn-outline-danger rounded-end-pill px-2.5" onclick="confirmDeleteNav(<?php echo $sub['id']; ?>, '<?php echo addslashes(htmlspecialchars($sub['title'])); ?>')"><i class="fa-solid fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteNavForm" method="POST" action="navigation.php">
        <?php echo Security::csrfField(); ?>
        <input type="hidden" name="nav_id" id="deleteNavId">
        <input type="hidden" name="delete_nav_item" value="1">
    </form>

    <script>
    function confirmDeleteNav(id, title) {
        if (confirm('Are you sure you want to delete menu item "' + title + '"? If it has sub-items, they will also be removed.')) {
            document.getElementById('deleteNavId').value = id;
            document.getElementById('deleteNavForm').submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('navSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var q = this.value.toLowerCase().trim();
                var rows = document.querySelectorAll('#navTable tbody tr');
                rows.forEach(function(row) {
                    var text = row.innerText.toLowerCase();
                    if (text.includes(q)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
    </script>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
