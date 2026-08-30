<?php
require_once 'C:/xampp/htdocs/project/core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : null;

// Handle Export CSV
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

// Handle Single Session Deletion
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

// Handle Clear All Chat Logs
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

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-comments text-warning me-2"></i>Conversation Transcripts</h1>
        <small class="text-muted">Inspect live visitor interactions, AI response accuracy, and query intents</small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if (!empty($sessions)): ?>
            <a href="conversations.php?export=csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export Transcripts CSV
            </a>
            <form method="POST" action="conversations.php" onsubmit="return confirm('WARNING: Are you sure you want to permanently clear ALL chatbot transcripts?');">
                <?php echo Security::csrfField(); ?>
                <button type="submit" name="clear_all_chats" class="btn btn-sm btn-outline-danger">
                    <i class="fa-solid fa-trash-can me-1"></i> Clear All Logs
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1"></i> Doc Upload &amp; Text Extractor</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link active font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<div class="row g-4">
    <!-- Sessions Table List -->
    <div class="<?php echo $sessionId ? 'col-lg-6' : 'col-12'; ?>">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-list text-warning me-2"></i>Recorded Sessions (<?php echo count($sessions); ?>)</h5>
                <input type="text" id="sessionSearchInput" class="form-control form-control-sm" placeholder="Search session token, IP..." style="width: 220px;">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small" id="sessionsTable">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>Session ID</th>
                                <th>Visitor / Role</th>
                                <th>IP Address</th>
                                <th class="text-center">Messages</th>
                                <th>Started At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No chat conversations recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $s): ?>
                                    <tr class="<?php echo $sessionId === (int)$s['id'] ? 'table-primary font-bold' : ''; ?>">
                                        <td><code>#<?php echo $s['id']; ?></code></td>
                                        <td>
                                            <span class="fw-bold text-primary-color"><?php echo htmlspecialchars($s['user_name'] ?: 'Public Visitor'); ?></span>
                                            <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 9px;"><?php echo htmlspecialchars($s['language'] ?? 'en'); ?></span>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($s['visitor_id']); ?></code></td>
                                        <td class="text-center"><span class="badge bg-info text-dark"><?php echo $s['msg_count']; ?> msgs</span></td>
                                        <td><?php echo formatDateTime($s['started_at']); ?></td>
                                        <td class="text-end text-nowrap">
                                            <a href="conversations.php?session_id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="View Thread"><i class="fa-solid fa-eye"></i></a>
                                            <form method="POST" action="conversations.php" class="d-inline" onsubmit="return confirm('Delete this conversation log?');">
                                                <?php echo Security::csrfField(); ?>
                                                <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
                                                <button type="submit" name="delete_session" class="btn btn-sm btn-outline-danger" title="Delete Session"><i class="fa-solid fa-trash"></i></button>
                                            </form>
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
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-white"><i class="fa-solid fa-comments text-warning me-2"></i>Session #<?php echo $sessionId; ?> Transcript</h5>
                    <a href="conversations.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-xmark"></i> Close</a>
                </div>
                <div class="card-body p-3 bg-light" style="max-height: 600px; overflow-y: auto;">
                    <?php if (empty($activeMessages)): ?>
                        <p class="text-muted text-center py-4">No messages found in this session.</p>
                    <?php else: ?>
                        <?php foreach ($activeMessages as $m): ?>
                            <div class="mb-3 d-flex flex-column <?php echo $m['sender'] === 'user' ? 'align-items-end' : 'align-items-start'; ?>">
                                <div class="small text-muted mb-1">
                                    <strong><?php echo $m['sender'] === 'user' ? '👤 Visitor' : '🤖 CampusAI'; ?></strong>
                                    <span class="ms-1" style="font-size: 10px;"><?php echo formatDateTime($m['created_at']); ?></span>
                                    <?php if (!empty($m['intent'])): ?>
                                        <span class="badge bg-secondary ms-1" style="font-size: 9px;"><?php echo htmlspecialchars($m['intent']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 rounded shadow-xs <?php echo $m['sender'] === 'user' ? 'bg-primary text-white' : 'bg-white border text-dark'; ?>" style="max-width: 90%; font-size: 13px;">
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
