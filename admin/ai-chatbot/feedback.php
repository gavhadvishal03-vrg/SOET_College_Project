<?php
/**
 * User Chat Feedback & Quality Review (Admin Panel)
 *
 * Displays visitor satisfaction ratings (thumbs up / thumbs down),
 * evaluated response snippets, engine source types (Database, OpenAI, Hybrid),
 * optional user comment explanations, and provides CSV export capabilities.
 *
 * @package SOET_College_Project
 * @subpackage Admin_AI_Chatbot
 */

require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();

// -------------------------------------------------------------------------
// CSV Data Export: Feedback Records
// -------------------------------------------------------------------------
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

// -------------------------------------------------------------------------
// Fetch Evaluated Feedback Records with Chat Context
// -------------------------------------------------------------------------
$feedbacks = $db->fetchAll(
    "SELECT f.*, m.message, m.sender, m.source, s.visitor_id 
     FROM chat_feedback f 
     JOIN chat_messages m ON f.message_id = m.id 
     JOIN chat_sessions s ON f.session_id = s.id 
     ORDER BY f.created_at DESC"
);
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Sentiment &amp; Telemetry</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">User Ratings &amp; Feedback</h2>
        <p class="text-muted small mb-0">Review visitor satisfaction metrics and individual response evaluations from CampusAI chats</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="feedback.php?action=export_csv" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs font-semibold">
            <i class="fa-solid fa-file-excel me-1.5 text-success"></i> Export CSV
        </a>
        <span class="badge bg-slate-900 text-white rounded-pill px-3 py-2 fs-6 shadow-xs">
            <i class="fa-solid fa-star text-warning me-1"></i> <?php echo count($feedbacks); ?> Ratings Recorded
        </span>
    </div>
</div>

<!-- Navigation Pills -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-2 mb-4">
    <ul class="nav nav-pills gap-1 flex-wrap" id="aiSubNav">
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="index.php"><i class="fa-solid fa-chart-line me-1.5 text-primary"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="knowledge-base.php"><i class="fa-solid fa-book me-1.5 text-primary"></i> Knowledge Base</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="faq.php"><i class="fa-solid fa-circle-question me-1.5 text-info"></i> FAQs</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1.5 text-warning"></i> Documents Extractor</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="unanswered.php"><i class="fa-solid fa-question me-1.5 text-danger"></i> Unanswered</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="conversations.php"><i class="fa-solid fa-comments me-1.5 text-success"></i> Conversations</a></li>
        <li class="nav-item"><a class="nav-link active rounded-pill font-semibold px-3 py-2 text-sm" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1.5"></i> Feedback</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="settings.php"><i class="fa-solid fa-gears me-1.5 text-muted"></i> AI Config</a></li>
    </ul>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="font-display fw-bold text-dark mb-0">
            <i class="fa-solid fa-star-half-stroke text-warning me-2"></i>Visitor Feedback Telemetry
        </h5>
        <div class="input-group input-group-sm" style="max-width: 250px;">
            <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
            <input type="text" id="feedbackSearchInput" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Search comments or message...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="feedbackTable">
                <thead class="table-light text-secondary text-uppercase text-xs tracking-wider">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Rating</th>
                        <th>Bot Response Evaluated</th>
                        <th>Engine Source</th>
                        <th>User Comments</th>
                        <th class="pe-4">Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($feedbacks)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No user ratings submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr>
                                <td class="ps-4 font-mono text-xs text-muted">#<?php echo $fb['id']; ?></td>
                                <td>
                                    <?php if ($fb['rating'] === 'positive'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 text-xs">
                                            <i class="fa-solid fa-thumbs-up me-1"></i> Helpful
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 text-xs">
                                            <i class="fa-solid fa-thumbs-down me-1"></i> Unhelpful
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 340px;">
                                    <div class="text-truncate small text-dark"><?php echo htmlspecialchars($fb['message']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-slate-900 text-white rounded-pill px-2.5 py-1 text-xs">
                                        <?php echo htmlspecialchars($fb['source'] ?: 'AI'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($fb['comments'])): ?>
                                        <span class="small text-secondary fw-medium"><?php echo htmlspecialchars($fb['comments']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small italic">No comments left</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4"><small class="text-muted text-xs"><?php echo formatDateTime($fb['created_at']); ?></small></td>
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
