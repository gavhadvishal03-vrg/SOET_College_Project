<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';
$editItem = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT question, answer, category, keywords, status, created_at FROM faq ORDER BY id DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=CampusAI_FAQs_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Question', 'Answer', 'Category', 'Keywords', 'Status', 'Created At']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['question'],
            $r['answer'],
            $r['category'],
            $r['keywords'],
            ucfirst($r['status']),
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Chatbot FAQ Manager";
include_once __DIR__ . '/../../admin/includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $editItem = $db->fetchOne("SELECT * FROM faq WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Save FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faq'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $category = trim($_POST['category']);
    $keywords = trim($_POST['keywords']);
    $status = $_POST['status'] ?? 'active';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($question) || empty($answer)) {
        setFlash('danger', 'Question and answer are required.');
    } else {
        if ($id) {
            $db->update('faq', [
                'question' => $question,
                'answer' => $answer,
                'category' => $category,
                'keywords' => $keywords,
                'status' => $status
            ], 'id = ?', [$id]);
            setFlash('success', 'FAQ entry updated.');
        } else {
            $db->insert('faq', [
                'question' => $question,
                'answer' => $answer,
                'category' => $category,
                'keywords' => $keywords,
                'status' => $status
            ]);
            setFlash('success', 'FAQ entry created.');
        }
        redirect('faq.php');
    }
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $db->delete('faq', 'id = ?', [(int)$_GET['id']]);
    setFlash('success', 'FAQ entry deleted.');
    redirect('faq.php');
}

$categories = $db->fetchAll("SELECT * FROM chatbot_categories ORDER BY name");
$faqs = $db->fetchAll("SELECT * FROM faq ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-circle-question text-warning me-2"></i>Frequently Asked Questions (FAQ)</h1>
        <small class="text-muted">Manage question-and-answer pairs returned by 🤖 CampusAI and displayed on the portal</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="faq.php?action=export_csv" class="btn btn-sm btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Export FAQ CSV
        </a>
        <a href="faq.php?action=add" class="btn btn-primary btn-sm font-semibold"><i class="fa-solid fa-plus me-1"></i> Create FAQ</a>
    </div>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link active font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1"></i> Doc Upload &amp; Text Extractor</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<?php if (in_array($action, ['add', 'edit'])): ?>
    <div class="card border-0 shadow-sm p-4 mb-4">
        <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2">
            <?php echo $editItem ? 'Edit FAQ Item' : 'Create New FAQ Item'; ?>
        </h5>
        <form method="POST" action="faq.php">
            <?php echo Security::csrfField(); ?>
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label font-semibold small">Question *</label>
                    <input type="text" name="question" class="form-control" value="<?php echo htmlspecialchars($editItem['question'] ?? ''); ?>" required placeholder="e.g. What is the fee structure for B.Tech CSE?">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-semibold small">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo ($editItem['category'] ?? '') === $cat['slug'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold small">Answer *</label>
                    <textarea name="answer" class="form-control" rows="4" required placeholder="Write clear, official answer..."><?php echo htmlspecialchars($editItem['answer'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label font-semibold small">Search Keywords</label>
                    <input type="text" name="keywords" class="form-control" value="<?php echo htmlspecialchars($editItem['keywords'] ?? ''); ?>" placeholder="e.g. fees, cse, tuition, payment">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-semibold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($editItem['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editItem['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12 text-end mt-4">
                    <a href="faq.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" name="save_faq" class="btn btn-primary font-semibold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save FAQ Item</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- FAQ List Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-list-check text-warning me-2"></i>Active FAQ Records (<?php echo count($faqs); ?>)</h5>
        <input type="text" id="faqSearchInput" class="form-control form-control-sm" placeholder="Search FAQ questions, answers..." style="width: 260px;">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0" id="faqTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 35%;">Question</th>
                        <th>Category</th>
                        <th style="width: 40%;">Answer Preview</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faqs)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No FAQ records added yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($faqs as $f): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-primary-color"><?php echo htmlspecialchars($f['question']); ?></div>
                                    <?php if (!empty($f['keywords'])): ?>
                                        <small class="text-muted"><i class="fa-solid fa-tags me-1"></i><?php echo htmlspecialchars($f['keywords']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($f['category']); ?></span></td>
                                <td class="text-muted small"><?php echo truncate(htmlspecialchars($f['answer']), 110); ?></td>
                                <td class="text-center"><?php echo statusBadge($f['status']); ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="faq.php?action=edit&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit FAQ"><i class="fa-solid fa-pen"></i></a>
                                    <a href="faq.php?action=delete&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete FAQ" onclick="return confirm('Delete this FAQ record?');"><i class="fa-solid fa-trash"></i></a>
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
    const searchInput = document.getElementById('faqSearchInput');
    const table = document.getElementById('faqTable');
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
