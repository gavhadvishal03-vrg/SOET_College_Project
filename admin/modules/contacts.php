<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('view_contacts');

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$msg = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT name, email, subject, message, status, reply_content, created_at FROM contact_messages ORDER BY created_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Contact_Inquiries_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Sender Name', 'Email Address', 'Subject', 'Message Content', 'Status', 'Reply Sent', 'Received At']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['name'],
            $r['email'],
            $r['subject'],
            $r['message'],
            ucfirst($r['status']),
            $r['reply_content'] ?: 'None',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Contact Messages";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'view' && isset($_GET['id'])) {
    $msg_id = (int)$_GET['id'];
    $msg = $db->fetchOne("SELECT * FROM contact_messages WHERE id = ?", [$msg_id]);
    
    // Mark as Read if unread
    if ($msg && $msg['status'] === 'unread') {
        $db->update('contact_messages', ['status' => 'read'], 'id = ?', [$msg_id]);
        $msg['status'] = 'read';
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id']) && (Auth::hasPermission('manage_users') || Auth::hasRole('Super Admin'))) {
    $msg_id = (int)$_GET['id'];
    $db->delete('contact_messages', 'id = ?', [$msg_id]);
    setFlash('success', 'Message removed successfully.');
    redirect('contacts.php');
}

// Handle reply logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $msg_id = (int)$_POST['msg_id'];
    $reply_content = trim($_POST['reply_content']);

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($reply_content)) {
        setFlash('danger', 'Reply description cannot be empty.');
    } else {
        $db->update('contact_messages', [
            'status' => 'replied',
            'replied_by' => Session::get('user_id'),
            'reply_content' => $reply_content
        ], 'id = ?', [$msg_id]);

        // Send Email Notification to Submitter
        $contactMsg = $db->fetchOne("SELECT * FROM contact_messages WHERE id = ?", [$msg_id]);
        if ($contactMsg && !empty($contactMsg['email'])) {
            Mailer::sendReviewNotification(
                $contactMsg['email'],
                $contactMsg['name'] ?? 'Visitor',
                'Contact Form Inquiry',
                $contactMsg['subject'] ?? 'Website Inquiry',
                'Replied',
                $reply_content
            );
        }

        setFlash('success', 'Logged reply details successfully & sent email notification to submitter (' . htmlspecialchars($contactMsg['email'] ?? '') . ').');
        redirect('contacts.php');
    }
}

// Fetch all messages
$messages = $db->fetchAll("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">Communication Hub</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Contact Inquiries</h2>
        <p class="text-muted small mb-0">Review, manage, and respond to public visitor questions and admissions helpdesk messages</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="contacts.php?action=export_csv" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs">
                <i class="fa-solid fa-file-excel me-1 text-success"></i> Export CSV
            </a>
            <?php 
            $unreadCount = $db->count('contact_messages', "status = 'unread'");
            if ($unreadCount > 0): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 fw-semibold">
                    <i class="fa-solid fa-bell me-1"></i> <?php echo $unreadCount; ?> Unread
                </span>
            <?php else: ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-semibold">
                    <i class="fa-solid fa-circle-check me-1"></i> All Caught Up
                </span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <a href="contacts.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fa-solid fa-angle-left me-1"></i> Back to Inquiries</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-inbox"></i>
                </div>
                <h5 class="fw-bold mb-0 text-dark">Inquiries Inbox</h5>
            </div>
            <div class="input-group input-group-sm" style="max-width: 300px;">
                <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="contactSearchInput" class="form-control bg-light border-start-0 rounded-end-pill pe-3" placeholder="Search sender, email, subject...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="contactsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Sender Name</th>
                            <th>Email Address</th>
                            <th>Subject</th>
                            <th>Received Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa-regular fa-envelope-open display-6 text-secondary opacity-40 mb-3 d-block"></i>
                                    No inquiry messages found in the database.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($m['name']); ?></div>
                                        <?php if ($m['status'] === 'unread'): ?>
                                            <span class="badge bg-danger rounded-pill px-2 py-0.5" style="font-size: 10px;">NEW</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?php echo htmlspecialchars($m['email']); ?></span></td>
                                    <td>
                                        <span class="fw-medium text-dark text-truncate d-inline-block" style="max-width: 260px;">
                                            <?php echo htmlspecialchars($m['subject']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?php echo formatDateTime($m['created_at']); ?></td>
                                    <td class="text-center">
                                        <?php 
                                        $st = $m['status'];
                                        if ($st === 'unread'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">Unread</span>
                                        <?php elseif ($st === 'replied'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1"><i class="fa-solid fa-check me-1"></i> Replied</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1">Read</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="contacts.php?action=view&id=<?php echo $m['id']; ?>" class="btn btn-outline-primary rounded-start-pill px-2.5" title="Read Message">
                                                <i class="fa-solid fa-envelope-open-text me-1"></i> Read
                                            </a>
                                            <?php if (Auth::hasPermission('manage_users') || Auth::hasRole('Super Admin')): ?>
                                                <a href="contacts.php?action=delete&id=<?php echo $m['id']; ?>" class="btn btn-outline-danger rounded-end-pill px-2.5" onclick="return confirm('Delete this inquiry message permanently?');" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
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
        const searchInput = document.getElementById('contactSearchInput');
        const table = document.getElementById('contactsTable');
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
<?php else: ?>
    <!-- Detailed inquiry reading and reply logging -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-circle-info text-primary me-2"></i>Inquiry Details</h5>
                    <div>
                        <?php 
                        $st = $msg['status'];
                        if ($st === 'unread'): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">Unread</span>
                        <?php elseif ($st === 'replied'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1"><i class="fa-solid fa-check me-1"></i> Replied</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1">Read</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex flex-column gap-2.5 small text-secondary mb-4 bg-light p-3.5 rounded-3 border border-light-subtle">
                    <div class="d-flex justify-content-between">
                        <strong class="text-dark">From:</strong>
                        <span class="text-dark fw-medium"><?php echo htmlspecialchars($msg['name']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong class="text-dark">Email:</strong>
                        <span class="font-monospace text-primary"><?php echo htmlspecialchars($msg['email']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong class="text-dark">Received At:</strong>
                        <span><?php echo formatDateTime($msg['created_at']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong class="text-dark">Subject:</strong>
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($msg['subject']); ?></span>
                    </div>
                </div>

                <label class="form-label font-semibold text-muted small mb-2">Message Body</label>
                <div class="bg-light p-3.5 rounded-3 text-dark mb-4 border border-light-subtle" style="line-height: 1.8; font-size: 14px;">
                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                </div>

                <?php if ($msg['status'] === 'replied'): ?>
                    <div class="alert alert-success-subtle border border-success-subtle rounded-3 p-3.5 mb-0">
                        <div class="fw-bold text-success mb-1">
                            <i class="fa-solid fa-circle-check me-1"></i> Official Reply Dispatched:
                        </div>
                        <div class="text-dark small" style="line-height: 1.7;">
                            <?php echo nl2br(htmlspecialchars($msg['reply_content'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-reply text-warning me-2"></i>Send Official Response</h5>
                </div>
                <form method="POST" action="contacts.php">
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">

                    <div class="mb-4">
                        <label class="form-label font-semibold text-dark">Reply Content (Dispatched to <?php echo htmlspecialchars($msg['email']); ?>) <span class="text-danger">*</span></label>
                        <textarea name="reply_content" class="form-control rounded-3" rows="9" placeholder="Type your official administrative reply or counseling response here..." required><?php echo htmlspecialchars($msg['reply_content'] ?? ''); ?></textarea>
                        <div class="form-text text-muted mt-1.5"><i class="fa-solid fa-envelope me-1"></i> An automated email notification with this reply will be logged and dispatched.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="send_reply" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold flex-grow-1 shadow-sm hover-lift">
                            <i class="fa-solid fa-paper-plane me-1.5"></i> Send & Log Reply
                        </button>
                        <a href="contacts.php" class="btn btn-outline-secondary rounded-pill px-3 py-2.5">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
