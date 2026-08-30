<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_notices');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_notice = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT title, content, is_pinned, expires_at, is_active, created_at FROM notices ORDER BY is_pinned DESC, created_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Notices_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Notice Title', 'Content / Description', 'Pinned Status', 'Expiry Date', 'Active Status', 'Posted Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['title'],
            $r['content'],
            $r['is_pinned'] ? 'Pinned' : 'Regular',
            $r['expires_at'] ?: 'Never Expires',
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Handle 1-click Pin/Unpin Toggle
if ($action === 'toggle_pin' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $current = $db->fetchOne("SELECT is_pinned FROM notices WHERE id = ?", [$target_id]);
    if ($current) {
        $new_val = $current['is_pinned'] ? 0 : 1;
        $db->update('notices', ['is_pinned' => $new_val], 'id = ?', [$target_id]);
        setFlash('success', 'Announcement pin status updated.');
    }
    redirect('notices.php');
}

$page_title = "Notice Board Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_notice = $db->fetchOne("SELECT * FROM notices WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notice'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $notice_id = isset($_POST['notice_id']) ? (int)$_POST['notice_id'] : null;
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $expires = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($content)) {
        setFlash('danger', 'Please enter a title and description.');
    } else {
        $uploaded_doc = $edit_notice ? $edit_notice['attachment_path'] : null;
        $valid_upload = true;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $errors = Security::validateUpload($_FILES['attachment'], ALLOWED_DOC_TYPES);
            if (!empty($errors)) {
                setFlash('danger', implode(' ', $errors));
                $valid_upload = false;
            } else {
                $uploaded_doc = Security::uploadFile($_FILES['attachment'], 'notices', 'notice_');
                if (!$uploaded_doc) {
                    setFlash('danger', 'Failed to upload attachment file.');
                    $valid_upload = false;
                }
            }
        }

        if ($valid_upload) {
            if ($notice_id) {
                // Edit
                $db->update('notices', [
                    'title' => $title,
                    'content' => $content,
                    'attachment_path' => $uploaded_doc,
                    'is_pinned' => $is_pinned,
                    'expires_at' => $expires,
                    'is_active' => $is_active
                ], 'id = ?', [$notice_id]);
                setFlash('success', 'Announcement details modified successfully.');
                redirect('notices.php');
            } else {
                // Add
                $db->insert('notices', [
                    'title' => $title,
                    'content' => $content,
                    'attachment_path' => $uploaded_doc,
                    'is_pinned' => $is_pinned,
                    'expires_at' => $expires,
                    'is_active' => $is_active,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                setFlash('success', 'New announcement published on the Notice Board.');
                redirect('notices.php');
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('notices', 'id = ?', [$target_id]);
    setFlash('success', 'Announcement removed from Notice Board.');
    redirect('notices.php');
}

// Fetch all notices
$notices = $db->fetchAll("SELECT * FROM notices ORDER BY is_pinned DESC, created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-bullhorn text-warning me-2"></i>Notice Board / Announcements</h1>
        <small class="text-muted">Publish campus alerts, exam circulars, academic notices, and pinned alerts</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="notices.php?action=export_csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="notices.php?action=add" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Post Announcement</a>
        </div>
    <?php else: ?>
        <a href="notices.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-clipboard-list text-warning me-2"></i>Active Circulars (<?php echo count($notices); ?>)</h5>
            <input type="text" id="noticeSearchInput" class="form-control form-control-sm" placeholder="Search notices by title, description..." style="width: 250px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="noticesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Notice Title</th>
                            <th>Description</th>
                            <th>Expiry Date</th>
                            <th class="text-center">Pinned</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notices)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No active notices posted.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notices as $nt): ?>
                                <tr>
                                    <td class="fw-bold text-primary-color">
                                        <?php echo htmlspecialchars($nt['title']); ?>
                                        <?php if ($nt['attachment_path']): ?>
                                            <a href="<?php echo uploadUrl('notices', $nt['attachment_path']); ?>" target="_blank" class="ms-1 small text-decoration-none" title="Download PDF Circular"><i class="fa-solid fa-file-pdf text-danger"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small" style="max-width: 350px;"><?php echo truncate(htmlspecialchars($nt['content']), 100); ?></td>
                                    <td><span class="small font-semibold text-secondary"><?php echo $nt['expires_at'] ? formatDate($nt['expires_at']) : 'Never Expires'; ?></span></td>
                                    <td class="text-center">
                                        <a href="notices.php?action=toggle_pin&id=<?php echo $nt['id']; ?>" class="text-decoration-none" title="Click to Toggle Pin">
                                            <?php if ($nt['is_pinned']): ?>
                                                <span class="badge bg-danger"><i class="fa-solid fa-thumbtack me-1"></i> Pinned</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Regular</span>
                                            <?php endif; ?>
                                        </a>
                                    </td>
                                    <td class="text-center"><?php echo statusBadge($nt['is_active'] ? 'active' : 'inactive'); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="notices.php?action=edit&id=<?php echo $nt['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <a href="notices.php?action=delete&id=<?php echo $nt['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete" onclick="return confirm('Delete this announcement?');"><i class="fa-solid fa-trash"></i></a>
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
        const searchInput = document.getElementById('noticeSearchInput');
        const table = document.getElementById('noticesTable');
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
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-bullhorn text-warning me-2"></i><?php echo $edit_notice ? 'Edit Notice Details' : 'Post New Announcement'; ?></h4>
                <form method="POST" action="notices.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_notice): ?>
                        <input type="hidden" name="notice_id" value="<?php echo $edit_notice['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font-semibold">Notice Title *</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($edit_notice['title'] ?? ''); ?>" placeholder="Admissions Circular / Examination Timetable">
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Description / Announcement Content *</label>
                            <textarea name="content" class="form-control" rows="6" required placeholder="Write detailed notice text here..."><?php echo htmlspecialchars($edit_notice['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Auto-Expiry Date (Optional)</label>
                            <input type="date" name="expires_at" class="form-control" value="<?php echo htmlspecialchars($edit_notice['expires_at'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Upload PDF Document (Optional)</label>
                            <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx">
                        </div>

                        <?php if ($edit_notice && !empty($edit_notice['attachment_path'])): ?>
                            <div class="col-12">
                                <span class="small text-secondary">Current File: </span>
                                <a href="<?php echo uploadUrl('notices', $edit_notice['attachment_path']); ?>" target="_blank" class="badge bg-danger text-white text-decoration-none">
                                    <i class="fa-solid fa-file-pdf me-1"></i> View Attached Circular
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="d-flex gap-4 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinned" <?php echo ($edit_notice && $edit_notice['is_pinned']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-semibold" for="isPinned">Pin to Top of Homepage &amp; Notice Board</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_notice || $edit_notice['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-semibold" for="isActive">Published / Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="notices.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_notice" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Announcement</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
