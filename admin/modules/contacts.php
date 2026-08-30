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

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-envelope-open-text text-warning me-2"></i>Contact Us Inquiries</h1>
        <small class="text-muted">Review, manage, and respond to public visitor questions and admissions helpdesk messages</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="contacts.php?action=export_csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <span class="badge bg-danger p-2"><?php echo $db->count('contact_messages', "status = 'unread'"); ?> Unread Messages</span>
        </div>
    <?php else: ?>
        <a href="contacts.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-inbox text-warning me-2"></i>Inquiries Inbox</h5>
            <input type="text" id="contactSearchInput" class="form-control form-control-sm" placeholder="Search sender, email, subject..." style="width: 240px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="contactsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Sender Name</th>
                            <th>Email Address</th>
                            <th>Subject</th>
                            <th>Received Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No query messages logged in the database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td class="fw-bold text-primary-color"><?php echo htmlspecialchars($m['name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($m['email']); ?></code></td>
                                    <td><?php echo htmlspecialchars($m['subject']); ?></td>
                                    <td><?php echo formatDateTime($m['created_at']); ?></td>
                                    <td class="text-center"><?php echo statusBadge($m['status']); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="contacts.php?action=view&id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-envelope-open-text me-1"></i> Read</a>
                                            <?php if (Auth::hasPermission('manage_users') || Auth::hasRole('Super Admin')): ?>
                                                <a href="contacts.php?action=delete&id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this inquiry message?');"><i class="fa-solid fa-trash"></i></a>
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
    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-circle-info text-warning me-2"></i>Inquiry Details</h5>
                
                <div class="d-flex flex-column gap-2 small text-secondary mb-4">
                    <p class="mb-0"><strong>From:</strong> <?php echo htmlspecialchars($msg['name']); ?> (<code><?php echo htmlspecialchars($msg['email']); ?></code>)</p>
                    <p class="mb-0"><strong>Received Date:</strong> <?php echo formatDateTime($msg['created_at']); ?></p>
                    <p class="mb-0"><strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?></p>
                </div>

                <div class="bg-light p-3 rounded text-dark mb-4 border" style="line-height: 1.7;">
                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                </div>

                <?php if ($msg['status'] === 'replied'): ?>
                    <div class="alert alert-success border-0 small">
                        <strong><i class="fa-solid fa-circle-check me-1"></i> Replied Message Log:</strong><br>
                        <?php echo nl2br(htmlspecialchars($msg['reply_content'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-reply text-warning me-2"></i>Respond to Sender</h5>
                <form method="POST" action="contacts.php">
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label font-semibold">Reply Content (Sent via College Email Desk) *</label>
                        <textarea name="reply_content" class="form-control" rows="8" placeholder="Type your official administrative reply or counseling advice here..." required><?php echo htmlspecialchars($msg['reply_content'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" name="send_reply" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send Official Reply
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
