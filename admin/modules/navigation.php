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

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-compass text-warning me-2"></i>Dynamic Navigation Menu Control Center</h1>
        <small class="text-muted">Manage, add, rename, reorder, and hide menu items &amp; dropdown links in real-time</small>
    </div>
    <div class="d-flex gap-2">
        <a href="navigation.php?action=export_csv" class="btn btn-sm btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Export CSV
        </a>
        <?php if ($action !== 'add'): ?>
            <a href="navigation.php?action=add" class="btn btn-primary btn-sm font-semibold"><i class="fa-solid fa-plus me-1"></i> Add Menu Item</a>
        <?php else: ?>
            <a href="navigation.php" class="btn btn-outline-secondary btn-sm font-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Back to List</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-primary-color">
                        <i class="fa-solid <?php echo $edit_item ? 'fa-pen-to-square' : 'fa-plus'; ?> text-warning me-2"></i>
                        <?php echo $edit_item ? 'Edit Navigation Item' : 'Add New Navigation Item'; ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="navigation.php">
                        <?php echo Security::csrfField(); ?>
                        <?php if ($edit_item): ?>
                            <input type="hidden" name="nav_id" value="<?php echo $edit_item['id']; ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Menu Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Scholarships, Research" value="<?php echo htmlspecialchars($edit_item['title'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">URL / Link Path <span class="text-danger">*</span></label>
                                <input type="text" name="url" class="form-control" placeholder="e.g. courses.php or https://..." value="<?php echo htmlspecialchars($edit_item['url'] ?? ''); ?>" required>
                                <small class="text-muted" style="font-size: 11px;">Use <code>#</code> for dropdown headers</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Parent Dropdown Category</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- Top-Level Item (Main Navbar Bar) --</option>
                                    <?php foreach ($parentCandidates as $pc): ?>
                                        <?php if ($edit_item && $pc['id'] == $edit_item['id']) continue; ?>
                                        <option value="<?php echo $pc['id']; ?>" <?php echo ($edit_item && $edit_item['parent_id'] == $pc['id']) ? 'selected' : ''; ?>>
                                            📁 Dropdown under: <?php echo htmlspecialchars($pc['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">FontAwesome Icon Class</label>
                                <input type="text" name="icon" class="form-control" placeholder="e.g. fa-graduation-cap, fa-house" value="<?php echo htmlspecialchars($edit_item['icon'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Sort Order (Lower numbers appear first)</label>
                                <input type="number" name="sort_order" class="form-control" value="<?php echo (int)($edit_item['sort_order'] ?? 10); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Link Target</label>
                                <select name="target" class="form-select">
                                    <option value="_self" <?php echo ($edit_item && $edit_item['target'] === '_self') ? 'selected' : ''; ?>>Same Window (_self)</option>
                                    <option value="_blank" <?php echo ($edit_item && $edit_item['target'] === '_blank') ? 'selected' : ''; ?>>New Tab (_blank)</option>
                                </select>
                            </div>
                            <div class="col-12 mt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_item || $edit_item['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-semibold" for="isActive">Visible in Public Navbar</label>
                                </div>
                            </div>
                            <div class="col-12 text-end mt-4">
                                <a href="navigation.php" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" name="save_nav_item" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Menu Item</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Navigation Tree & Table View -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-list-check text-warning me-2"></i>Public Navigation Menu Tree</h5>
            <input type="text" id="navSearchInput" class="form-control form-control-sm" placeholder="Filter menu items..." style="width: 240px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="navTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 60px;">Order</th>
                            <th>Menu Item &amp; Structure</th>
                            <th>Target URL</th>
                            <th>Icon</th>
                            <th class="text-center">Target</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mainMenuItems)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No navigation items configured.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mainMenuItems as $main): ?>
                                <?php $hasSub = isset($subItemsByParent[$main['id']]); ?>
                                <tr class="table-light fw-bold">
                                    <td class="text-center"><span class="badge bg-secondary"><?php echo $main['sort_order']; ?></span></td>
                                    <td>
                                        <i class="fa-solid <?php echo !empty($main['icon']) ? htmlspecialchars($main['icon']) : 'fa-link'; ?> me-2 text-warning"></i>
                                        <span class="fs-6 text-primary-color"><?php echo htmlspecialchars($main['title']); ?></span>
                                        <?php if ($hasSub): ?>
                                            <span class="badge bg-info ms-2 font-normal" style="font-size: 10px;"><?php echo count($subItemsByParent[$main['id']]); ?> Sub-Items Dropdown</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($main['url']); ?></code></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($main['icon'] ?? '-'); ?></small></td>
                                    <td class="text-center"><small class="badge bg-light text-dark border"><?php echo $main['target']; ?></small></td>
                                    <td class="text-center">
                                        <a href="navigation.php?toggle_status=1&id=<?php echo $main['id']; ?>" class="badge text-decoration-none <?php echo $main['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $main['is_active'] ? 'Active' : 'Hidden'; ?>
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <a href="navigation.php?action=edit&id=<?php echo $main['id']; ?>" class="btn btn-xs btn-outline-primary me-1" title="Edit"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                        <button class="btn btn-xs btn-outline-danger" onclick="confirmDeleteNav(<?php echo $main['id']; ?>, '<?php echo addslashes(htmlspecialchars($main['title'])); ?>')" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php if ($hasSub): ?>
                                    <?php foreach ($subItemsByParent[$main['id']] as $sub): ?>
                                        <tr>
                                            <td class="text-center text-muted"><small>└ <?php echo $sub['sort_order']; ?></small></td>
                                            <td class="ps-4">
                                                <i class="fa-solid fa-level-up-alt fa-rotate-90 me-2 text-muted"></i>
                                                <i class="fa-solid <?php echo !empty($sub['icon']) ? htmlspecialchars($sub['icon']) : 'fa-angle-right'; ?> me-1 text-info"></i>
                                                <span class="font-semibold text-dark"><?php echo htmlspecialchars($sub['title']); ?></span>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($sub['url']); ?></code></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($sub['icon'] ?? '-'); ?></small></td>
                                            <td class="text-center"><small class="badge bg-light text-dark border"><?php echo $sub['target']; ?></small></td>
                                            <td class="text-center">
                                                <a href="navigation.php?toggle_status=1&id=<?php echo $sub['id']; ?>" class="badge text-decoration-none <?php echo $sub['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $sub['is_active'] ? 'Active' : 'Hidden'; ?>
                                                </a>
                                            </td>
                                            <td class="text-end">
                                                <a href="navigation.php?action=edit&id=<?php echo $sub['id']; ?>" class="btn btn-xs btn-outline-primary me-1"><i class="fa-solid fa-pen-to-square"></i></a>
                                                <button class="btn btn-xs btn-outline-danger" onclick="confirmDeleteNav(<?php echo $sub['id']; ?>, '<?php echo addslashes(htmlspecialchars($sub['title'])); ?>')"><i class="fa-solid fa-trash"></i></button>
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
