<?php
/**
 * Chatbot Conversation Transcripts Manager (Admin Panel)
 *
 * Enables administrators to audit live visitor chat sessions, view complete
 * back-and-forth message dialogues, review detected query intents and RAG sources,
 * export chat archives to CSV, and delete single sessions or purge all history.
 *
 * @package SOET_College_Project
 * @subpackage Admin_AI_Chatbot
 */

require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : null;

// -------------------------------------------------------------------------
// CSV Data Export: Chat Transcripts
// -------------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="CampusAI_Chat_Transcripts_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Message ID', 'Session ID', 'Sender', 'Message Content', 'Response Source', 'Detected Intent', 'Timestamp']);

    $rows = $db->fetchAll("SELECT id, session_id, sender, message, source, intent, created_at FROM chat_messages ORDER BY id DESC LIMIT 500");
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['session_id'],
            $r['sender'] === 'user' ? 'Visitor' : 'CampusAI',
            strip_tags($r['message']),
            $r['source'],
            $r['intent'],
            $r['created_at']
        ]);
    }
    fclose($out);
    exit;
}

// -------------------------------------------------------------------------
// POST: Delete Single Chat Session
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_session'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $sid = (int)$_POST['session_id'];
    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $db->delete('chat_messages', 'session_id = ?', [$sid]);
        $db->delete('chat_sessions', 'id = ?', [$sid]);
        setFlash('success', "Chat session #{$sid} deleted successfully.");
        redirect('conversations.php');
    }
}

// -------------------------------------------------------------------------
// POST: Clear All Chat History & Sessions
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all_chats'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $db->query("DELETE FROM chat_messages");
        $db->query("DELETE FROM chat_sessions");
        setFlash('success', 'All chatbot conversation logs cleared successfully.');
        redirect('conversations.php');
    }
}

$page_title = "Chatbot Conversation Transcripts";
include_once __DIR__ . '/../../admin/includes/header.php';

$sessions = $db->fetchAll(
    "SELECT s.*, u.full_name as user_name, COUNT(m.id) as msg_count 
     FROM chat_sessions s 
     LEFT JOIN users u ON s.user_id = u.id 
     LEFT JOIN chat_messages m ON s.id = m.session_id 
     GROUP BY s.id 
     ORDER BY s.id DESC LIMIT 100"
);

$activeMessages = [];
if ($sessionId) {
    $activeMessages = $db->fetchAll("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY id ASC", [$sessionId]);
}
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Chatbot Transcripts</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Conversation Transcripts</h2>
        <p class="text-muted small mb-0">Inspect live visitor interactions, AI response accuracy, and query intents</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if (!empty($sessions)): ?>
            <a href="conversations.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs font-semibold">
                <i class="fa-solid fa-file-excel me-1 text-success"></i> Export CSV
            </a>
            <form method="POST" action="conversations.php" onsubmit="return confirm('WARNING: Are you sure you want to permanently clear ALL chatbot transcripts?');">
                <?php echo Security::csrfField(); ?>
                <button type="submit" name="clear_all_chats" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-xs font-semibold">
                    <i class="fa-solid fa-trash-can me-1"></i> Clear All Logs
                </button>
            </form>
        <?php endif; ?>
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
        <li class="nav-item"><a class="nav-link active rounded-pill font-semibold px-3 py-2 text-sm" href="conversations.php"><i class="fa-solid fa-comments me-1.5"></i> Conversations</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1.5 text-warning"></i> Feedback</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="settings.php"><i class="fa-solid fa-gears me-1.5 text-muted"></i> AI Config</a></li>
    </ul>
</div>

<div class="row g-4 mb-4">
    <!-- Sessions Table List -->
    <div class="<?php echo $sessionId ? 'col-lg-6' : 'col-12'; ?>">
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h5 class="fw-bold mb-0 text-dark">Recorded Sessions <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1" style="font-size: 14px;"><?php echo count($sessions); ?></span></h5>
                </div>
                <div class="input-group input-group-sm" style="max-width: 220px;">
                    <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="sessionSearchInput" class="form-control bg-light border-start-0 rounded-end-pill pe-3" placeholder="Search session...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small" id="sessionsTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-4">Session ID</th>
                                <th>Visitor / Role</th>
                                <th>IP Address</th>
                                <th class="text-center">Messages</th>
                                <th>Started At</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-comments display-6 text-secondary opacity-40 mb-3 d-block"></i>
                                        No chat conversations recorded.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $s): ?>
                                    <tr class="<?php echo $sessionId === (int)$s['id'] ? 'table-primary bg-primary-subtle' : ''; ?>">
                                        <td class="ps-4"><span class="badge bg-light text-dark border font-monospace">#<?php echo $s['id']; ?></span></td>
                                        <td>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($s['user_name'] ?: 'Public Visitor'); ?></span>
                                            <span class="badge bg-light text-muted border ms-1 text-uppercase" style="font-size: 14px;"><?php echo htmlspecialchars($s['language'] ?? 'en'); ?></span>
                                        </td>
                                        <td><code class="text-muted"><?php echo htmlspecialchars($s['visitor_id']); ?></code></td>
                                        <td class="text-center"><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 py-0.5"><?php echo $s['msg_count']; ?> msgs</span></td>
                                        <td class="text-muted"><?php echo formatDateTime($s['started_at']); ?></td>
                                        <td class="text-end pe-4 text-nowrap">
                                            <div class="btn-group btn-group-sm">
                                                <a href="conversations.php?session_id=<?php echo $s['id']; ?>" class="btn btn-outline-primary rounded-start-pill px-2.5" title="View Thread"><i class="fa-solid fa-eye"></i></a>
                                                <form method="POST" action="conversations.php" class="d-inline" onsubmit="return confirm('Delete this conversation log?');">
                                                    <?php echo Security::csrfField(); ?>
                                                    <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
                                                    <button type="submit" name="delete_session" class="btn btn-outline-danger rounded-end-pill px-2.5" title="Delete Session"><i class="fa-solid fa-trash"></i></button>
                                                </form>
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
    </div>

    <!-- Active Transcript Thread Inspector -->
    <?php if ($sessionId): ?>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden h-100">
                <div class="card-header bg-slate-900 text-white py-3 px-4 d-flex justify-content-between align-items-center" style="background: var(--dark-bg);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-comments text-warning"></i>
                        <h5 class="fw-bold mb-0 text-white font-display">Session #<?php echo $sessionId; ?> Transcript</h5>
                    </div>
                    <a href="conversations.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fa-solid fa-xmark me-1"></i> Close</a>
                </div>
                <div class="card-body p-4 bg-light" style="max-height: 560px; overflow-y: auto;">
                    <?php if (empty($activeMessages)): ?>
                        <p class="text-muted text-center py-4">No messages found in this session.</p>
                    <?php else: ?>
                        <?php foreach ($activeMessages as $m): ?>
                            <div class="mb-3 d-flex flex-column <?php echo $m['sender'] === 'user' ? 'align-items-end' : 'align-items-start'; ?>">
                                <div class="small text-muted mb-1 px-1">
                                    <strong><?php echo $m['sender'] === 'user' ? '👤 Visitor' : '🤖 CampusAI'; ?></strong>
                                    <span class="ms-1" style="font-size: 14px;"><?php echo formatDateTime($m['created_at']); ?></span>
                                    <?php if (!empty($m['intent'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill ms-1" style="font-size: 14px;"><?php echo htmlspecialchars($m['intent']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3.5 rounded-4 shadow-xs <?php echo $m['sender'] === 'user' ? 'bg-primary text-white' : 'bg-white border border-light-subtle text-dark'; ?>" style="max-width: 88%; font-size: 14px; line-height: 1.6;">
                                    <?php echo $m['sender'] === 'user' ? nl2br(htmlspecialchars($m['message'])) : $m['message']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('sessionSearchInput');
    const table = document.getElementById('sessionsTable');
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
