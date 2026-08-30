<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT f.id, f.rating, f.comments, m.message, m.source, f.created_at 
         FROM chat_feedback f 
         JOIN chat_messages m ON f.message_id = m.id 
         JOIN chat_sessions s ON f.session_id = s.id 
         ORDER BY f.created_at DESC"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=CampusAI_Chat_Feedback_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Feedback ID', 'Rating', 'Evaluated Response Snippet', 'Response Source', 'User Comments', 'Submitted Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            '#' . $r['id'],
            ucfirst($r['rating']),
            $r['message'],
            $r['source'],
            $r['comments'] ?: 'None',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "User Chat Feedback Review";
include_once __DIR__ . '/../../admin/includes/header.php';

$feedbacks = $db->fetchAll(
    "SELECT f.*, m.message, m.sender, m.source, s.visitor_id 
     FROM chat_feedback f 
     JOIN chat_messages m ON f.message_id = m.id 
     JOIN chat_sessions s ON f.session_id = s.id 
     ORDER BY f.created_at DESC"
);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-thumbs-up text-warning me-2"></i>User Ratings &amp; Feedback</h1>
        <small class="text-muted">Review visitor satisfaction and AI response quality feedback</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="feedback.php?action=export_csv" class="btn btn-sm btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Export CSV
        </a>
        <span class="badge bg-dark fs-6 px-3 py-2"><?php echo count($feedbacks); ?> Ratings Recorded</span>
    </div>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1"></i> Doc Upload &amp; Text Extractor</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link active font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-star-half-stroke text-warning me-2"></i>Visitor Feedback Log</h5>
        <input type="text" id="feedbackSearchInput" class="form-control form-control-sm" placeholder="Search comments or message..." style="width: 250px;">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0" id="feedbackTable">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Rating</th>
                        <th>Bot Response Evaluated</th>
                        <th>Response Source</th>
                        <th>User Comments</th>
                        <th>Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedbacks)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No user ratings submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr>
                                <td><strong>#<?php echo $fb['id']; ?></strong></td>
                                <td>
                                    <?php if ($fb['rating'] === 'positive'): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-thumbs-up me-1"></i> Helpful</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fa-solid fa-thumbs-down me-1"></i> Unhelpful</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 350px;">
                                    <div class="text-truncate small"><?php echo htmlspecialchars($fb['message']); ?></div>
                                </td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($fb['source'] ?: 'AI'); ?></span></td>
                                <td>
                                    <?php if (!empty($fb['comments'])): ?>
                                        <span class="small text-secondary"><?php echo htmlspecialchars($fb['comments']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">No comments left</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo formatDateTime($fb['created_at']); ?></small></td>
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
    const searchInput = document.getElementById('feedbackSearchInput');
    const table = document.getElementById('feedbackTable');
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
