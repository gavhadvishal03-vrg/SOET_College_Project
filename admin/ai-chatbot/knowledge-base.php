<?php
/**
 * Chatbot Knowledge Base Manager (Admin Panel)
 *
 * Manages verified institutional articles, program summaries, fee details,
 * and campus policies indexed by CampusAI's full-text and semantic search engines.
 * Supports categories, search keyword tags, source links, and CSV data exports.
 *
 * @package SOET_College_Project
 * @subpackage Admin_AI_Chatbot
 */

require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';
$editItem = null;

// -------------------------------------------------------------------------
// CSV Data Export: Knowledge Base Articles
// -------------------------------------------------------------------------
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

// Fetch single article for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $editItem = $db->fetchOne("SELECT * FROM knowledge_base WHERE id = ?", [(int)$_GET['id']]);
}

// -------------------------------------------------------------------------
// POST: Create or Update Knowledge Base Article
// -------------------------------------------------------------------------
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
            // Update existing article
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
            // Insert new article
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

// -------------------------------------------------------------------------
// GET: Delete Knowledge Base Article
// -------------------------------------------------------------------------
if ($action === 'delete' && isset($_GET['id'])) {
    $db->delete('knowledge_base', 'id = ?', [(int)$_GET['id']]);
    setFlash('success', 'Knowledge Base article deleted.');
    redirect('knowledge-base.php');
}

$categories = $db->fetchAll("SELECT * FROM chatbot_categories ORDER BY name");
$articles = $db->fetchAll("SELECT * FROM knowledge_base ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">RAG Knowledge Engine</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Knowledge Base Manager</h2>
        <p class="text-muted small mb-0">Manage verified institutional facts, syllabus overviews, and policy answers indexed by CampusAI</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="knowledge-base.php?action=export_csv" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs font-semibold">
            <i class="fa-solid fa-file-excel me-1 text-success"></i> Export KB CSV
        </a>
        <a href="knowledge-base.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3.5 shadow-sm hover-lift font-semibold"><i class="fa-solid fa-plus me-1.5"></i> Add Knowledge Article</a>
    </div>
</div>

<!-- Navigation Pills -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-2 mb-4">
    <ul class="nav nav-pills gap-1 flex-wrap" id="aiSubNav">
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="index.php"><i class="fa-solid fa-chart-line me-1.5 text-primary"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active rounded-pill font-semibold px-3 py-2 text-sm" href="knowledge-base.php"><i class="fa-solid fa-book me-1.5"></i> Knowledge Base</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="faq.php"><i class="fa-solid fa-circle-question me-1.5 text-info"></i> FAQs</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1.5 text-warning"></i> Documents Extractor</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="unanswered.php"><i class="fa-solid fa-question me-1.5 text-danger"></i> Unanswered</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="conversations.php"><i class="fa-solid fa-comments me-1.5 text-success"></i> Conversations</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1.5 text-warning"></i> Feedback</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="settings.php"><i class="fa-solid fa-gears me-1.5 text-muted"></i> AI Config</a></li>
    </ul>
</div>

<?php if (in_array($action, ['add', 'edit'])): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-3 font-display">
            <i class="fa-solid <?php echo $editItem ? 'fa-pen-to-square' : 'fa-plus'; ?> text-primary me-2"></i>
            <?php echo $editItem ? 'Edit Knowledge Base Article' : 'Create New Knowledge Article'; ?>
        </h5>
        <form method="POST" action="knowledge-base.php">
            <?php echo Security::csrfField(); ?>
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label font-semibold text-dark small">Article Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required placeholder="e.g. B.Tech Artificial Intelligence Program Overview">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-semibold text-dark small">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select rounded-3">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo ($editItem['category'] ?? '') === $cat['slug'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold text-dark small">Article Verified Content <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control rounded-3" rows="6" required placeholder="Enter authoritative institution facts, descriptions, or policy details..."><?php echo htmlspecialchars($editItem['content'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label font-semibold text-dark small">Search Keywords (comma separated)</label>
                    <input type="text" name="keywords" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['keywords'] ?? ''); ?>" placeholder="e.g. cse, fee, syllabus, admission">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-semibold text-dark small">Source URL Page Link</label>
                    <input type="text" name="source_url" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['source_url'] ?? ''); ?>" placeholder="e.g. courses.php">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-semibold text-dark small">Status</label>
                    <select name="status" class="form-select rounded-3">
                        <option value="active" <?php echo ($editItem['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active (Indexed)</option>
                        <option value="inactive" <?php echo ($editItem['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                    </select>
                </div>
                <div class="col-12 text-end mt-4 pt-3 border-top">
                    <a href="knowledge-base.php" class="btn btn-outline-secondary rounded-pill px-4 me-2">Cancel</a>
                    <button type="submit" name="save_kb" class="btn btn-primary rounded-pill font-semibold px-4 py-2 shadow-sm hover-lift"><i class="fa-solid fa-floppy-disk me-1.5"></i> Save Knowledge Article</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Knowledge Base List Table -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h5 class="fw-bold mb-0 text-dark">Indexed Knowledge Entries <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1" style="font-size: 14px;"><?php echo count($articles); ?></span></h5>
        </div>
        <div class="input-group input-group-sm" style="max-width: 260px;">
            <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="kbSearchInput" class="form-control bg-light border-start-0 rounded-end-pill pe-3" placeholder="Search title, keywords...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="kbTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 28%;">Article Title</th>
                        <th>Category</th>
                        <th style="width: 38%;">Content Summary</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-book-open display-6 text-secondary opacity-40 mb-3 d-block"></i>
                                No Knowledge Base articles recorded.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($articles as $art): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($art['title']); ?></div>
                                    <?php if (!empty($art['keywords'])): ?>
                                        <div class="text-muted small mt-0.5" style="font-size: 14px;"><i class="fa-solid fa-tags me-1 text-primary"></i><?php echo htmlspecialchars($art['keywords']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 text-uppercase" style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($art['category']); ?></span></td>
                                <td>
                                    <div class="text-muted small" style="line-height: 1.5;"><?php echo truncate(htmlspecialchars($art['content']), 110); ?></div>
                                    <?php if (!empty($art['source_url'])): ?>
                                        <div class="mt-0.5"><span class="badge bg-light text-primary border font-monospace" style="font-size: 14px;"><i class="fa-solid fa-link me-1"></i><?php echo htmlspecialchars($art['source_url']); ?></span></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo statusBadge($art['status']); ?></td>
                                <td class="text-end pe-4 text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <a href="knowledge-base.php?action=edit&id=<?php echo $art['id']; ?>" class="btn btn-outline-primary rounded-start-pill px-2.5" title="Edit Article"><i class="fa-solid fa-pen"></i></a>
                                        <a href="knowledge-base.php?action=delete&id=<?php echo $art['id']; ?>" class="btn btn-outline-danger rounded-end-pill px-2.5" title="Delete Article" onclick="return confirm('Delete this Knowledge Base article?');"><i class="fa-solid fa-trash"></i></a>
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
