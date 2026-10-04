<?php
/**
 * Chatbot Frequently Asked Questions (FAQ) Manager (Admin Panel)
 *
 * Manages verified, canonical Q&A pairs queried directly by CampusAI's
 * database retriever engine and displayed on public portal FAQ widgets.
 * Supports categorization, search keywords, status toggling, and CSV exports.
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
// CSV Data Export: FAQs
// -------------------------------------------------------------------------
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

// Fetch single FAQ item for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $editItem = $db->fetchOne("SELECT * FROM faq WHERE id = ?", [(int)$_GET['id']]);
}

// -------------------------------------------------------------------------
// POST: Create or Update FAQ Item
// -------------------------------------------------------------------------
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
            // Update existing FAQ
            $db->update('faq', [
                'question' => $question,
                'answer' => $answer,
                'category' => $category,
                'keywords' => $keywords,
                'status' => $status
            ], 'id = ?', [$id]);
            setFlash('success', 'FAQ entry updated.');
        } else {
            // Insert new FAQ
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

// -------------------------------------------------------------------------
// GET: Delete FAQ Item
// -------------------------------------------------------------------------
if ($action === 'delete' && isset($_GET['id'])) {
    $db->delete('faq', 'id = ?', [(int)$_GET['id']]);
    setFlash('success', 'FAQ entry deleted.');
    redirect('faq.php');
}

$categories = $db->fetchAll("SELECT * FROM chatbot_categories ORDER BY name");
$faqs = $db->fetchAll("SELECT * FROM faq ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Instant FAQ Engine</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Frequently Asked Questions (FAQ)</h2>
        <p class="text-muted small mb-0">Manage question-and-answer pairs returned by 🤖 CampusAI and displayed on the public portal</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="faq.php?action=export_csv" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs font-semibold">
            <i class="fa-solid fa-file-excel me-1.5 text-success"></i> Export CSV
        </a>
        <a href="faq.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3.5 shadow-sm hover-lift font-semibold">
            <i class="fa-solid fa-plus me-1.5"></i> Create FAQ
        </a>
    </div>
</div>

<!-- Navigation Pills -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-2 mb-4">
    <ul class="nav nav-pills gap-1 flex-wrap" id="aiSubNav">
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="index.php"><i class="fa-solid fa-chart-line me-1.5 text-primary"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="knowledge-base.php"><i class="fa-solid fa-book me-1.5 text-primary"></i> Knowledge Base</a></li>
        <li class="nav-item"><a class="nav-link active rounded-pill font-semibold px-3 py-2 text-sm" href="faq.php"><i class="fa-solid fa-circle-question me-1.5"></i> FAQs</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1.5 text-warning"></i> Documents Extractor</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="unanswered.php"><i class="fa-solid fa-question me-1.5 text-danger"></i> Unanswered</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="conversations.php"><i class="fa-solid fa-comments me-1.5 text-success"></i> Conversations</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1.5 text-warning"></i> Feedback</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="settings.php"><i class="fa-solid fa-gears me-1.5 text-muted"></i> AI Config</a></li>
    </ul>
</div>

<?php if (in_array($action, ['add', 'edit'])): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <h5 class="font-display fw-bold text-dark mb-0">
                <i class="fa-solid fa-pen-to-square text-primary me-2"></i><?php echo $editItem ? 'Edit FAQ Item' : 'Create New FAQ Item'; ?>
            </h5>
            <span class="text-muted small">Canonical question &amp; verified answer</span>
        </div>
        <form method="POST" action="faq.php">
            <?php echo Security::csrfField(); ?>
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label font-semibold small text-secondary">Question <span class="text-danger">*</span></label>
                    <input type="text" name="question" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['question'] ?? ''); ?>" required placeholder="e.g. What is the fee structure for B.Tech CSE?">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-semibold small text-secondary">Category</label>
                    <select name="category" class="form-select rounded-3">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo ($editItem['category'] ?? '') === $cat['slug'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold small text-secondary">Answer <span class="text-danger">*</span></label>
                    <textarea name="answer" class="form-control rounded-3" rows="4" required placeholder="Write clear, official response..."><?php echo htmlspecialchars($editItem['answer'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label font-semibold small text-secondary">Search Keywords (Comma separated)</label>
                    <input type="text" name="keywords" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['keywords'] ?? ''); ?>" placeholder="e.g. fees, cse, tuition, payment, schedule">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-semibold small text-secondary">Status</label>
                    <select name="status" class="form-select rounded-3">
                        <option value="active" <?php echo ($editItem['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active (Visible)</option>
                        <option value="inactive" <?php echo ($editItem['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                    </select>
                </div>
                <div class="col-12 text-end pt-2">
                    <a href="faq.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2">Cancel</a>
                    <button type="submit" name="save_faq" class="btn btn-primary rounded-pill font-semibold px-4 shadow-sm hover-lift">
                        <i class="fa-solid fa-floppy-disk me-1.5"></i> Save FAQ Item
                    </button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- FAQ List Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="font-display fw-bold text-dark mb-0">
            <i class="fa-solid fa-list-check text-warning me-2"></i>Active FAQ Records (<?php echo count($faqs); ?>)
        </h5>
        <div class="input-group input-group-sm" style="max-width: 260px;">
            <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
            <input type="text" id="faqSearchInput" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Search questions, answers...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="faqTable">
                <thead class="table-light text-secondary text-uppercase text-xs tracking-wider">
                    <tr>
                        <th class="ps-4" style="width: 35%;">Question</th>
                        <th>Category</th>
                        <th style="width: 40%;">Answer Preview</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($faqs)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No FAQ records added yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($faqs as $f): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($f['question']); ?></div>
                                    <?php if (!empty($f['keywords'])): ?>
                                        <small class="text-muted text-xs d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-tags text-primary text-opacity-75"></i> <?php echo htmlspecialchars($f['keywords']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1 text-xs text-uppercase font-semibold">
                                        <?php echo htmlspecialchars($f['category']); ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?php echo truncate(htmlspecialchars($f['answer']), 110); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($f['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 text-xs">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1 text-xs">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="faq.php?action=edit&id=<?php echo $f['id']; ?>" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 me-1 shadow-xs" title="Edit FAQ">
                                        <i class="fa-solid fa-pen me-1"></i> Edit
                                    </a>
                                    <a href="faq.php?action=delete&id=<?php echo $f['id']; ?>" class="btn btn-xs btn-outline-danger rounded-pill px-2.5 py-1" title="Delete FAQ" onclick="return confirm('Delete this FAQ record?');">
                                        <i class="fa-solid fa-trash"></i>
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
