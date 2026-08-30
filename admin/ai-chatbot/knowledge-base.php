<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';
$editItem = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT title, category, keywords, content, source_url, status, created_at FROM knowledge_base ORDER BY id DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=CampusAI_Knowledge_Base_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Title', 'Category', 'Keywords', 'Content', 'Source Page URL', 'Status', 'Created At']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['title'],
            $r['category'],
            $r['keywords'],
            $r['content'],
            $r['source_url'],
            ucfirst($r['status']),
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Chatbot Knowledge Base Manager";
include_once __DIR__ . '/../../admin/includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $editItem = $db->fetchOne("SELECT * FROM knowledge_base WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Save KB Article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_kb'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $content = trim($_POST['content']);
    $keywords = trim($_POST['keywords']);
    $sourceUrl = trim($_POST['source_url']);
    $status = $_POST['status'] ?? 'active';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($content)) {
        setFlash('danger', 'Title and content are required.');
    } else {
        if ($id) {
            $db->update('knowledge_base', [
                'title' => $title,
                'category' => $category,
                'content' => $content,
                'keywords' => $keywords,
                'source_url' => $sourceUrl,
                'status' => $status
            ], 'id = ?', [$id]);
            setFlash('success', 'Knowledge Base article updated.');
        } else {
            $db->insert('knowledge_base', [
                'title' => $title,
                'category' => $category,
                'content' => $content,
                'keywords' => $keywords,
                'source_url' => $sourceUrl,
                'status' => $status
            ]);
            setFlash('success', 'Knowledge Base article created.');
        }
        redirect('knowledge-base.php');
    }
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $db->delete('knowledge_base', 'id = ?', [(int)$_GET['id']]);
    setFlash('success', 'Knowledge Base article deleted.');
    redirect('knowledge-base.php');
}

$categories = $db->fetchAll("SELECT * FROM chatbot_categories ORDER BY name");
$articles = $db->fetchAll("SELECT * FROM knowledge_base ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-book text-warning me-2"></i>Knowledge Base Manager</h1>
        <small class="text-muted">Manage verified institutional facts, syllabus overviews, and policy answers indexed by CampusAI</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="knowledge-base.php?action=export_csv" class="btn btn-sm btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Export KB CSV
        </a>
        <a href="knowledge-base.php?action=add" class="btn btn-primary btn-sm font-semibold"><i class="fa-solid fa-plus me-1"></i> Add Knowledge Article</a>
    </div>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link active font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1"></i> Doc Upload &amp; Text Extractor</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<?php if (in_array($action, ['add', 'edit'])): ?>
    <div class="card border-0 shadow-sm p-4 mb-4">
        <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2">
            <?php echo $editItem ? 'Edit Knowledge Base Article' : 'Create New Knowledge Article'; ?>
        </h5>
        <form method="POST" action="knowledge-base.php">
            <?php echo Security::csrfField(); ?>
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label font-semibold small">Article Title *</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-semibold small">Category *</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo ($editItem['category'] ?? '') === $cat['slug'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold small">Article Verified Content *</label>
                    <textarea name="content" class="form-control" rows="5" required><?php echo htmlspecialchars($editItem['content'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label font-semibold small">Search Keywords (comma separated)</label>
                    <input type="text" name="keywords" class="form-control" value="<?php echo htmlspecialchars($editItem['keywords'] ?? ''); ?>" placeholder="e.g. cse, fee, syllabus, admission">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-semibold small">Source URL Page Link</label>
                    <input type="text" name="source_url" class="form-control" value="<?php echo htmlspecialchars($editItem['source_url'] ?? ''); ?>" placeholder="e.g. /courses.php">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-semibold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($editItem['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editItem['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12 text-end mt-4">
                    <a href="knowledge-base.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" name="save_kb" class="btn btn-primary font-semibold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Knowledge Article</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Knowledge Base List Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-layer-group text-warning me-2"></i>Indexed Knowledge Entries (<?php echo count($articles); ?>)</h5>
        <input type="text" id="kbSearchInput" class="form-control form-control-sm" placeholder="Search title, keywords, content..." style="width: 260px;">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0" id="kbTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 30%;">Article Title</th>
                        <th>Category</th>
                        <th style="width: 35%;">Content Summary</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No Knowledge Base articles recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($articles as $art): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-primary-color"><?php echo htmlspecialchars($art['title']); ?></div>
                                    <?php if (!empty($art['keywords'])): ?>
                                        <small class="text-muted"><i class="fa-solid fa-tags me-1"></i><?php echo htmlspecialchars($art['keywords']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($art['category']); ?></span></td>
                                <td>
                                    <div class="text-muted small"><?php echo truncate(htmlspecialchars($art['content']), 110); ?></div>
                                    <?php if (!empty($art['source_url'])): ?>
                                        <small class="text-primary"><i class="fa-solid fa-link me-1"></i><code><?php echo htmlspecialchars($art['source_url']); ?></code></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo statusBadge($art['status']); ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="knowledge-base.php?action=edit&id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit Article"><i class="fa-solid fa-pen"></i></a>
                                    <a href="knowledge-base.php?action=delete&id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete Article" onclick="return confirm('Delete this Knowledge Base article?');"><i class="fa-solid fa-trash"></i></a>
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
    const searchInput = document.getElementById('kbSearchInput');
    const table = document.getElementById('kbTable');
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

<?php include_once __DIR__ . '/../../admin/includes/footer.php'; ?>
