<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $rows = $db->fetchAll("SELECT question, category, status, confidence, admin_response, created_at FROM unanswered_questions ORDER BY created_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=CampusAI_Unanswered_Questions_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['User Question', 'Category', 'Status', 'Confidence', 'Admin Published Response', 'Logged Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['question'],
            $r['category'],
            ucfirst($r['status']),
            $r['confidence'],
            $r['admin_response'] ?: 'Pending Review',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Unanswered Questions Queue";
include_once __DIR__ . '/../../admin/includes/header.php';

// Handle Add Manual Question to Queue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_unanswered_question'])) {
    $question = trim($_POST['question']);
    $category = trim($_POST['category']);

    if (!empty($question)) {
        $db->insert('unanswered_questions', [
            'question' => $question,
            'category' => $category,
            'confidence' => 0.50,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        setFlash('success', 'Question added to Unanswered Queue for review.');
        redirect('unanswered.php');
    }
}

// Handle Publish Answer to KB & FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_answer'])) {
    $qId = (int)$_POST['question_id'];
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $category = trim($_POST['category']);

    if (!empty($question) && !empty($answer)) {
        // Insert into FAQ
        $db->insert('faq', [
            'question' => $question,
            'answer' => $answer,
            'category' => $category,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Insert into Knowledge Base as well for full indexing
        $db->insert('knowledge_base', [
            'title' => $question,
            'content' => $answer,
            'keywords' => strtolower($question . ' ' . $category),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Mark unanswered item as answered
        $db->update('unanswered_questions', [
            'status' => 'answered',
            'admin_response' => $answer
        ], 'id = ?', [$qId]);

        setFlash('success', 'Question answered and published to SOET Knowledge Base & FAQs!');
        redirect('unanswered.php');
    }
}

// Handle Dismiss / Delete
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
$categories = [
    ['name' => 'General / Campus', 'slug' => 'general'],
    ['name' => 'Admissions & Quotas', 'slug' => 'admission'],
    ['name' => 'Fee Structure & Scholarships', 'slug' => 'fee'],
    ['name' => 'Hostel & Facilities', 'slug' => 'facilities'],
    ['name' => 'Placements & Packages', 'slug' => 'placement'],
    ['name' => 'Courses & Curricula', 'slug' => 'courses']
];
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-question text-warning me-2"></i>Unanswered Questions Queue</h1>
        <small class="text-muted">Review visitor questions requiring verified administrative responses</small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="unanswered.php?action=export_csv" class="btn btn-sm btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Export CSV
        </a>
        <span class="badge bg-danger fs-6 px-3 py-2"><i class="fa-solid fa-clock me-1"></i> <?php echo $pendingCount; ?> Pending Review</span>
        <button type="button" class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddUnanswered">
            <i class="fa-solid fa-plus me-1"></i> Add Question to Queue
        </button>
    </div>
</div>

<!-- Modal: Add Manual Question -->
<div class="modal fade" id="modalAddUnanswered" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="unanswered.php">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-circle-question text-warning me-2"></i>Add Question to Unanswered Queue</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">User Question *</label>
                        <textarea name="question" class="form-control" rows="3" placeholder="e.g. Is hostellers laundry service free on campus?" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Category</label>
                        <select name="category" class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_unanswered_question" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Add to Queue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1"></i> Doc Upload &amp; Text Extractor</a></li>
    <li class="nav-item"><a class="nav-link active font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<!-- Filter Tabs -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="btn-group btn-group-sm">
        <a href="unanswered.php?status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-dark' : 'btn-outline-dark'; ?>"><i class="fa-solid fa-clock me-1"></i> Pending (<?php echo $pendingCount; ?>)</a>
        <a href="unanswered.php?status=answered" class="btn <?php echo $statusFilter === 'answered' ? 'btn-dark' : 'btn-outline-dark'; ?>"><i class="fa-solid fa-circle-check me-1"></i> Answered &amp; Published</a>
        <a href="unanswered.php?status=dismissed" class="btn <?php echo $statusFilter === 'dismissed' ? 'btn-dark' : 'btn-outline-dark'; ?>"><i class="fa-solid fa-eye-slash me-1"></i> Dismissed</a>
    </div>
    <input type="text" id="qSearchInput" class="form-control form-control-sm" placeholder="Filter questions..." style="width: 200px;">
</div>

<?php if (empty($questionsList)): ?>
    <div class="card border-0 shadow-sm p-5 text-center">
        <i class="fa-solid fa-circle-check text-success display-1 mb-3"></i>
        <h4 class="fw-bold">All Questions Resolved!</h4>
        <p class="text-muted">There are currently no <?php echo $statusFilter; ?> visitor questions requiring review.</p>
        <div class="mt-2">
            <button type="button" class="btn btn-outline-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddUnanswered">
                <i class="fa-solid fa-plus me-1"></i> Add Test Question
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4" id="questionsGrid">
        <?php foreach ($questionsList as $q): ?>
            <div class="col-md-6 question-card-col" data-text="<?php echo strtolower(htmlspecialchars($q['question'] . ' ' . $q['category'])); ?>">
                <div class="card border-0 shadow-sm p-4 h-100 border-top border-4 <?php echo $q['status'] === 'pending' ? 'border-warning' : ($q['status'] === 'answered' ? 'border-success' : 'border-secondary'); ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($q['category']); ?></span>
                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?php echo formatDate($q['created_at']); ?></small>
                    </div>

                    <h5 class="fw-bold text-primary-color mb-3">"<?php echo htmlspecialchars($q['question']); ?>"</h5>

                    <?php if ($q['status'] === 'answered'): ?>
                        <div class="bg-light p-3 rounded mb-3 border">
                            <strong class="text-success small"><i class="fa-solid fa-circle-check me-1"></i> Verified Admin Response:</strong>
                            <p class="small mb-0 mt-1"><?php echo nl2br(htmlspecialchars($q['admin_response'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalAnswer<?php echo $q['id']; ?>">
                            <i class="fa-solid fa-pen-to-square me-1"></i> <?php echo $q['status'] === 'answered' ? 'Update Answer' : 'Provide Answer & Publish'; ?>
                        </button>
                        <div>
                            <?php if ($q['status'] === 'pending'): ?>
                                <a href="unanswered.php?action=dismiss&id=<?php echo $q['id']; ?>" class="btn btn-outline-secondary btn-sm me-1" title="Dismiss"><i class="fa-solid fa-eye-slash"></i></a>
                            <?php endif; ?>
                            <a href="unanswered.php?action=delete&id=<?php echo $q['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this question?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Answer & Publish -->
            <div class="modal fade" id="modalAnswer<?php echo $q['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="unanswered.php">
                            <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($q['category']); ?>">
                            <div class="modal-header bg-dark text-white">
                                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-robot text-warning me-2"></i>Answer &amp; Publish to Knowledge Base</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label font-semibold">User Question Title</label>
                                    <input type="text" name="question" class="form-control fw-bold" value="<?php echo htmlspecialchars($q['question']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label font-semibold">Official Verified Answer *</label>
                                    <textarea name="answer" class="form-control" rows="5" placeholder="Write comprehensive, official response here..." required><?php echo htmlspecialchars($q['admin_response'] ?? ''); ?></textarea>
                                    <div class="form-text">This will automatically sync with the 🤖 CampusAI Knowledge Base and public FAQ list.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="publish_answer" class="btn btn-primary fw-bold"><i class="fa-solid fa-upload me-1"></i> Publish to Knowledge Base</button>
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
