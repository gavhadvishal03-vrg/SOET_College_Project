<?php
/**
 * Unanswered Questions Queue & Feedback Loop (Admin Panel)
 *
 * Implements continuous learning for CampusAI:
 * 1. Automatically records visitor questions that triggered fallback responses or low-confidence matches.
 * 2. Displays session context, IP addresses, caller pages, and confidence levels.
 * 3. Allows administrators to draft verified answers and simultaneously publish them to both FAQ and KB tables.
 * 4. Provides resolution status filters (pending, answered, resolved, dismissed) and CSV exports.
 *
 * @package SOET_College_Project
 * @subpackage Admin_AI_Chatbot
 */

require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();

// -------------------------------------------------------------------------
// CSV Data Export: Unanswered Questions
// -------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $rows = $db->fetchAll("SELECT question, category, source_category, status, confidence, ip_address, conversation_token, page_url, admin_response, created_at, resolved_at FROM unanswered_questions ORDER BY created_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=CampusAI_Unanswered_Questions_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['User Question', 'Category', 'Source', 'Status', 'Confidence', 'User IP', 'Conversation Token', 'Page URL', 'Admin Published Response', 'Logged Date', 'Resolved Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['question'],
            $r['category'],
            $r['source_category'] ?: 'WEBSITE',
            ucfirst($r['status']),
            $r['confidence'],
            $r['ip_address'] ?: 'N/A',
            $r['conversation_token'] ?: 'N/A',
            $r['page_url'] ?: 'N/A',
            $r['admin_response'] ?: 'Pending Review',
            $r['created_at'],
            $r['resolved_at'] ?: 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Unanswered Questions Queue";
include_once __DIR__ . '/../../admin/includes/header.php';

// -------------------------------------------------------------------------
// POST: Add Manual Question to Review Queue
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_unanswered_question'])) {
    $question = trim($_POST['question']);
    $category = trim($_POST['category']);

    if (!empty($question)) {
        $db->insert('unanswered_questions', [
            'question'           => $question,
            'category'           => $category,
            'source_category'    => 'MANUAL_ENTRY',
            'confidence'         => 0.50,
            'status'             => 'pending',
            'created_at'         => date('Y-m-d H:i:s')
        ]);
        setFlash('success', 'Question added to Unanswered Queue for review.');
        redirect('unanswered.php');
    }
}

// -------------------------------------------------------------------------
// POST: Publish Verified Answer to Knowledge Base & FAQ
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_answer'])) {
    $qId = (int)$_POST['question_id'];
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $category = trim($_POST['category']);
    $syncKb = isset($_POST['sync_to_kb']) && $_POST['sync_to_kb'] === '1';
    $syncFaq = isset($_POST['sync_to_faq']) && $_POST['sync_to_faq'] === '1';

    if (!empty($question) && !empty($answer)) {
        // Optional Insert into FAQ
        if ($syncFaq) {
            $db->insert('faq', [
                'question'   => $question,
                'answer'     => $answer,
                'category'   => $category,
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Optional Insert into Knowledge Base
        if ($syncKb) {
            $db->insert('knowledge_base', [
                'title'      => $question,
                'content'    => $answer,
                'keywords'   => strtolower($question . ' ' . $category),
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Mark item as answered
        $db->update('unanswered_questions', [
            'status'         => 'answered',
            'admin_response' => $answer,
            'resolved_at'    => date('Y-m-d H:i:s'),
            'resolved_by'    => Session::get('user_id')
        ], 'id = ?', [$qId]);

        $syncMsg = ($syncKb && $syncFaq) ? 'published to Knowledge Base & FAQs' : ($syncKb ? 'published to Knowledge Base' : ($syncFaq ? 'published to FAQs' : 'saved'));
        setFlash('success', "Question answered and {$syncMsg} successfully!");
        redirect('unanswered.php');
    }
}

// -------------------------------------------------------------------------
// Action: 1-Click Mark as Resolved
// -------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'resolve' && isset($_GET['id'])) {
    $qId = (int)$_GET['id'];
    $db->update('unanswered_questions', [
        'status'      => 'resolved',
        'resolved_at' => date('Y-m-d H:i:s'),
        'resolved_by' => Session::get('user_id')
    ], 'id = ?', [$qId]);
    setFlash('success', 'Question marked as Resolved.');
    redirect('unanswered.php');
}

// -------------------------------------------------------------------------
// Action: Dismiss or Delete Question
// -------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'dismiss' && isset($_GET['id'])) {
    $qId = (int)$_GET['id'];
    $db->update('unanswered_questions', ['status' => 'dismissed'], 'id = ?', [$qId]);
    setFlash('success', 'Question marked as dismissed.');
    redirect('unanswered.php');
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $qId = (int)$_GET['id'];
    $db->delete('unanswered_questions', 'id = ?', [$qId]);
    setFlash('success', 'Question removed from queue.');
    redirect('unanswered.php');
}

// Fetch Unanswered by status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$questionsList = $db->fetchAll("SELECT * FROM unanswered_questions WHERE status = ? ORDER BY created_at DESC", [$statusFilter]);

// Counts
$pendingCount = $db->count('unanswered_questions', "status = 'pending'");
$answeredCount = $db->count('unanswered_questions', "status = 'answered'");
$resolvedCount = $db->count('unanswered_questions', "status = 'resolved'");
$dismissedCount = $db->count('unanswered_questions', "status = 'dismissed'");

$categories = [
    ['name' => 'General / Campus', 'slug' => 'general'],
    ['name' => 'Admissions & Quotas', 'slug' => 'admission'],
    ['name' => 'Fee Structure & Scholarships', 'slug' => 'fee'],
    ['name' => 'Hostel & Facilities', 'slug' => 'facilities'],
    ['name' => 'Placements & Packages', 'slug' => 'placement'],
    ['name' => 'Courses & Curricula', 'slug' => 'courses'],
    ['name' => 'Faculty & HOD', 'slug' => 'faculty'],
    ['name' => 'Contact & Office', 'slug' => 'contact'],
];
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Learning Feedback Loop</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Unanswered Questions Queue</h2>
        <p class="text-muted small mb-0">Review visitor queries that triggered fallback, view context details, and publish canonical answers to the knowledge base</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="unanswered.php?action=export_csv" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs font-semibold">
            <i class="fa-solid fa-file-excel me-1.5 text-success"></i> Export CSV
        </a>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 fs-6">
            <i class="fa-solid fa-clock me-1.5"></i> <?php echo $pendingCount; ?> Pending Review
        </span>
        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3.5 shadow-sm hover-lift font-semibold" data-bs-toggle="modal" data-bs-target="#modalAddUnanswered">
            <i class="fa-solid fa-plus me-1.5"></i> Add Question
        </button>
    </div>
</div>

<!-- Modal: Add Manual Question -->
<div class="modal fade" id="modalAddUnanswered" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST" action="unanswered.php">
                <div class="modal-header bg-slate-900 text-white py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="w-8 h-8 rounded-circle bg-warning bg-opacity-20 d-flex align-items-center justify-content-center text-warning">
                            <i class="fa-solid fa-circle-question"></i>
                        </div>
                        <h5 class="modal-title font-display fw-bold text-white mb-0">Add Question to Queue</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold small text-secondary">User Question <span class="text-danger">*</span></label>
                        <textarea name="question" class="form-control rounded-3" rows="3" placeholder="e.g. Is hostellers laundry service free on campus?" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label font-semibold small text-secondary">Category</label>
                        <select name="category" class="form-select rounded-3">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_unanswered_question" class="btn btn-primary btn-sm rounded-pill font-semibold px-4 shadow-sm hover-lift">
                        <i class="fa-solid fa-floppy-disk me-1.5"></i> Add to Queue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Navigation Pills -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-2 mb-4">
    <ul class="nav nav-pills gap-1 flex-wrap" id="aiSubNav">
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="index.php"><i class="fa-solid fa-chart-line me-1.5 text-primary"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="knowledge-base.php"><i class="fa-solid fa-book me-1.5 text-primary"></i> Knowledge Base</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="faq.php"><i class="fa-solid fa-circle-question me-1.5 text-info"></i> FAQs</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1.5 text-warning"></i> Documents Extractor</a></li>
        <li class="nav-item"><a class="nav-link active rounded-pill font-semibold px-3 py-2 text-sm" href="unanswered.php"><i class="fa-solid fa-question me-1.5"></i> Unanswered</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="conversations.php"><i class="fa-solid fa-comments me-1.5 text-success"></i> Conversations</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1.5 text-warning"></i> Feedback</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="settings.php"><i class="fa-solid fa-gears me-1.5 text-muted"></i> AI Config</a></li>
    </ul>
</div>

<!-- Filter Tabs & Search -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="btn-group p-1 bg-white rounded-pill shadow-xs border">
        <a href="unanswered.php?status=pending" class="btn btn-sm rounded-pill font-semibold px-3 <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-ghost text-secondary'; ?>">
            <i class="fa-solid fa-clock me-1.5 text-warning"></i> Pending (<?php echo $pendingCount; ?>)
        </a>
        <a href="unanswered.php?status=answered" class="btn btn-sm rounded-pill font-semibold px-3 <?php echo $statusFilter === 'answered' ? 'btn-primary' : 'btn-ghost text-secondary'; ?>">
            <i class="fa-solid fa-circle-check me-1.5 text-success"></i> Answered &amp; Published (<?php echo $answeredCount; ?>)
        </a>
        <a href="unanswered.php?status=resolved" class="btn btn-sm rounded-pill font-semibold px-3 <?php echo $statusFilter === 'resolved' ? 'btn-primary' : 'btn-ghost text-secondary'; ?>">
            <i class="fa-solid fa-check-double me-1.5 text-info"></i> Resolved (<?php echo $resolvedCount; ?>)
        </a>
        <a href="unanswered.php?status=dismissed" class="btn btn-sm rounded-pill font-semibold px-3 <?php echo $statusFilter === 'dismissed' ? 'btn-primary' : 'btn-ghost text-secondary'; ?>">
            <i class="fa-solid fa-eye-slash me-1.5"></i> Dismissed (<?php echo $dismissedCount; ?>)
        </a>
    </div>
    <div class="input-group input-group-sm" style="max-width: 280px;">
        <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
        <input type="text" id="qSearchInput" class="form-control bg-white border-start-0 rounded-end-pill" placeholder="Search by question, IP, token...">
    </div>
</div>

<?php if (empty($questionsList)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <div class="w-16 h-16 rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
            <i class="fa-solid fa-circle-check fa-2x"></i>
        </div>
        <h4 class="font-display fw-bold text-dark mb-1">Queue is Clear!</h4>
        <p class="text-muted small mb-3">There are currently no <strong><?php echo $statusFilter; ?></strong> visitor questions in this view.</p>
        <div>
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-semibold px-3.5" data-bs-toggle="modal" data-bs-target="#modalAddUnanswered">
                <i class="fa-solid fa-plus me-1.5"></i> Add Test Question
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4" id="questionsGrid">
        <?php foreach ($questionsList as $q):
            $cardBorder = 'border-warning';
            if ($q['status'] === 'answered') $cardBorder = 'border-success';
            elseif ($q['status'] === 'resolved') $cardBorder = 'border-info';
            elseif ($q['status'] === 'dismissed') $cardBorder = 'border-secondary';
        ?>
            <div class="col-md-6 question-card-col" data-text="<?php echo strtolower(htmlspecialchars($q['question'] . ' ' . $q['category'] . ' ' . ($q['ip_address'] ?? '') . ' ' . ($q['conversation_token'] ?? '') . ' ' . ($q['admin_response'] ?? ''))); ?>">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 <?php echo $cardBorder; ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1 text-xs text-uppercase font-semibold">
                                <?php echo htmlspecialchars($q['category']); ?>
                            </span>
                            <?php if (!empty($q['source_category'])): ?>
                                <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5 text-xs">
                                    <?php echo htmlspecialchars($q['source_category']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted text-xs"><i class="fa-regular fa-clock me-1"></i><?php echo formatDate($q['created_at']); ?></small>
                    </div>

                    <h5 class="font-display fw-bold text-dark mb-2">"<?php echo htmlspecialchars($q['question']); ?>"</h5>

                    <!-- User / Session Context Meta Bar -->
                    <div class="bg-light p-2.5 rounded-3 mb-3 border text-xs text-muted">
                        <div class="row g-1">
                            <div class="col-6">
                                <i class="fa-solid fa-network-wired me-1 text-secondary"></i> IP: <strong><?php echo htmlspecialchars($q['ip_address'] ?: 'Unknown'); ?></strong>
                            </div>
                            <div class="col-6 text-truncate" title="<?php echo htmlspecialchars($q['conversation_token'] ?? ''); ?>">
                                <i class="fa-solid fa-comments me-1 text-secondary"></i> Chat: <strong><?php echo htmlspecialchars(substr($q['conversation_token'] ?? 'N/A', 0, 16)); ?><?php echo strlen($q['conversation_token'] ?? '') > 16 ? '...' : ''; ?></strong>
                            </div>
                            <?php if (!empty($q['page_url'])): ?>
                                <div class="col-12 text-truncate" title="<?php echo htmlspecialchars($q['page_url']); ?>">
                                    <i class="fa-solid fa-link me-1 text-secondary"></i> Page: <span><?php echo htmlspecialchars($q['page_url']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($q['status'] === 'answered' && !empty($q['admin_response'])): ?>
                        <div class="bg-success-subtle border border-success-subtle p-3 rounded-3 mb-3">
                            <strong class="text-success small d-flex align-items-center gap-1.5 mb-1">
                                <i class="fa-solid fa-circle-check"></i> Published Knowledge Base Response:
                            </strong>
                            <p class="small text-dark text-opacity-80 mb-0"><?php echo nl2br(htmlspecialchars($q['admin_response'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-primary btn-sm rounded-pill font-semibold px-3 shadow-sm hover-lift" data-bs-toggle="modal" data-bs-target="#modalAnswer<?php echo $q['id']; ?>">
                            <i class="fa-solid fa-pen-to-square me-1.5"></i> <?php echo $q['status'] === 'answered' ? 'Edit &amp; Re-Sync' : 'Answer &amp; Publish'; ?>
                        </button>

                        <div class="d-flex gap-1.5">
                            <?php if ($q['status'] === 'pending'): ?>
                                <a href="unanswered.php?action=resolve&id=<?php echo $q['id']; ?>" class="btn btn-xs btn-outline-info rounded-pill px-2.5 py-1" title="Mark as Resolved">
                                    <i class="fa-solid fa-check me-1"></i> Resolve
                                </a>
                                <a href="unanswered.php?action=dismiss&id=<?php echo $q['id']; ?>" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1" title="Dismiss">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </a>
                            <?php endif; ?>
                            <a href="unanswered.php?action=delete&id=<?php echo $q['id']; ?>" class="btn btn-xs btn-outline-danger rounded-pill px-2.5 py-1" onclick="return confirm('Delete this question permanently?');" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Answer & Publish -->
            <div class="modal fade" id="modalAnswer<?php echo $q['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <form method="POST" action="unanswered.php">
                            <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($q['category']); ?>">
                            <div class="modal-header bg-slate-900 text-white py-3 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="w-8 h-8 rounded-circle bg-warning bg-opacity-20 d-flex align-items-center justify-content-center text-warning">
                                        <i class="fa-solid fa-robot"></i>
                                    </div>
                                    <h5 class="modal-title font-display fw-bold text-white mb-0">Answer &amp; Publish to Knowledge Base</h5>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label font-semibold small text-secondary">User Question Title</label>
                                    <input type="text" name="question" class="form-control rounded-3 fw-bold" value="<?php echo htmlspecialchars($q['question']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label font-semibold small text-secondary">Official Verified Answer <span class="text-danger">*</span></label>
                                    <textarea name="answer" class="form-control rounded-3" rows="5" placeholder="Write comprehensive, official response here..." required><?php echo htmlspecialchars($q['admin_response'] ?? ''); ?></textarea>
                                </div>
                                <div class="row g-2 p-3 bg-light rounded-3 border">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="sync_to_kb" value="1" id="syncKb<?php echo $q['id']; ?>" checked>
                                            <label class="form-check-label small fw-semibold" for="syncKb<?php echo $q['id']; ?>">
                                                <i class="fa-solid fa-book text-primary me-1"></i> Sync to Knowledge Base
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="sync_to_faq" value="1" id="syncFaq<?php echo $q['id']; ?>" checked>
                                            <label class="form-check-label small fw-semibold" for="syncFaq<?php echo $q['id']; ?>">
                                                <i class="fa-solid fa-circle-question text-info me-1"></i> Sync to Public FAQs
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light px-4 py-3 border-top">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="publish_answer" class="btn btn-primary btn-sm rounded-pill font-semibold px-4 shadow-sm hover-lift">
                                    <i class="fa-solid fa-upload me-1.5"></i> Publish &amp; Update Knowledge Base
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const qSearch = document.getElementById('qSearchInput');
        const cards = document.querySelectorAll('.question-card-col');
        if (qSearch) {
            qSearch.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                cards.forEach(card => {
                    const text = card.getAttribute('data-text') || '';
                    card.style.display = (!q || text.includes(q)) ? '' : 'none';
                });
            });
        }
    });
    </script>
<?php endif; ?>

<?php include_once __DIR__ . '/../../admin/includes/footer.php'; ?>
