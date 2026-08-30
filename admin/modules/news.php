<?php
$page_title = "News Review & Management Center";
include_once __DIR__ . '/../includes/header.php';
Auth::requireLogin();

// Users with approve_news or submit_news can view this section
if (!Auth::hasPermission('approve_news') && !Auth::hasPermission('submit_news')) {
    setFlash('danger', 'Access denied.');
    redirect(APP_URL . '/admin/dashboard.php');
}

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$news = null;

if (in_array($action, ['review', 'edit']) && isset($_GET['id'])) {
    $news = $db->fetchOne(
        "SELECT n.*, u.full_name as user_author_name 
         FROM news n JOIN users u ON n.author_id = u.id 
         WHERE n.id = ?",
        [(int)$_GET['id']]
    );
    
    // Change status to under_review if currently submitted
    if ($news && $news['status'] === 'submitted' && Auth::hasPermission('approve_news')) {
        $db->update('news', ['status' => 'under_review', 'reviewer_id' => Session::get('user_id')], 'id = ?', [$news['id']]);
        $news['status'] = 'under_review';
    }
}

// Handle Admin Review Decision
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review_decision'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $news_id = (int)$_POST['news_id'];
    $decision = $_POST['decision']; // published, returned, rejected
    $remarks = trim($_POST['reviewer_remarks']);

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($decision)) {
        setFlash('danger', 'Please choose a valid review decision.');
    } else {
        $published_at = ($decision === 'published') ? date('Y-m-d H:i:s') : null;
        $db->update('news', [
            'status' => $decision,
            'reviewer_id' => Session::get('user_id'),
            'review_remarks' => $remarks,
            'published_at' => $published_at,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$news_id]);

        // Send Email Notification to Submitter
        $newsItem = $db->fetchOne("SELECT n.*, u.email as user_email, u.full_name as user_full_name FROM news n LEFT JOIN users u ON n.author_id = u.id WHERE n.id = ?", [$news_id]);
        $recipientEmail = $newsItem['author_email'] ?? $newsItem['user_email'] ?? '';
        $recipientName = $newsItem['author_name'] ?? $newsItem['user_full_name'] ?? 'Contributor';

        if (!empty($recipientEmail)) {
            Mailer::sendReviewNotification($recipientEmail, $recipientName, 'News Article', $newsItem['title'] ?? 'News Article', $decision, $remarks);
        }

        setFlash('success', 'News review decision logged: ' . ucfirst($decision) . '. Notification sent.');
        redirect('news.php');
    }
}

// Handle Complete Edit / Revision of News Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_news_edit'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $news_id = (int)$_POST['news_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $author_name = trim($_POST['author_name'] ?? '');
    $author_email = trim($_POST['author_email'] ?? '');
    $status = $_POST['status'] ?? 'submitted';
    $remarks = trim($_POST['review_remarks'] ?? '');

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($content)) {
        setFlash('danger', 'Headline and Content cannot be blank.');
    } else {
        $existing = $db->fetchOne("SELECT * FROM news WHERE id = ?", [$news_id]);
        $uploaded_image = $existing['image_path'] ?? null;

        // Check if new cover image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $errors = Security::validateUpload($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if (!empty($errors)) {
                setFlash('danger', implode(' ', $errors));
            } else {
                $new_image = Security::uploadFile($_FILES['image'], 'news', 'news_');
                if ($new_image) {
                    $uploaded_image = $new_image;
                }
            }
        }

        $updateData = [
            'title' => $title,
            'content' => $content,
            'author_name' => $author_name ?: ($existing['author_name'] ?? 'Reporter'),
            'author_email' => $author_email ?: ($existing['author_email'] ?? ''),
            'image_path' => $uploaded_image,
            'status' => $status,
            'review_remarks' => $remarks,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'published' && empty($existing['published_at'])) {
            $updateData['published_at'] = date('Y-m-d H:i:s');
            $updateData['reviewer_id'] = Session::get('user_id');
        }

        $db->update('news', $updateData, 'id = ?', [$news_id]);

        setFlash('success', 'News article updated and saved successfully.');
        redirect('news.php');
    }
}

// Handle Delete News
if ($action === 'delete' && isset($_GET['id']) && Auth::hasPermission('approve_news')) {
    $news_id = (int)$_GET['id'];
    $db->delete('news', 'id = ?', [$news_id]);
    setFlash('success', 'News headline deleted successfully.');
    redirect('news.php');
}

// Fetch lists depending on user permissions
$query = "SELECT n.*, u.full_name as user_full_name, r.full_name as reviewer_name
          FROM news n 
          JOIN users u ON n.author_id = u.id 
          LEFT JOIN users r ON n.reviewer_id = r.id";

$params = [];
if (!Auth::hasPermission('approve_news')) {
    $query .= " WHERE n.author_id = ?";
    $params[] = Session::get('user_id');
}
$query .= " ORDER BY n.created_at DESC";
$news_list = $db->fetchAll($query, $params);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-newspaper text-warning me-2"></i>News Review &amp; Management Queue</h1>
        <small class="text-muted">Review, verify, edit, and publish press releases and campus announcements</small>
    </div>
    <?php if ($action !== 'list'): ?>
        <a href="news.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to Queue</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-list-check text-warning me-2"></i>Submitted News Reports</h5>
            <input type="text" id="newsFilterInput" class="form-control form-control-sm" placeholder="Search news title, author..." style="width: 220px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="newsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Headline</th>
                            <th>Author Name</th>
                            <th>Date Submitted</th>
                            <th>Reviewer</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($news_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No news submissions found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($news_list as $n): 
                                $authorDisplayName = $n['author_name'] ?: $n['user_full_name'];
                            ?>
                                <tr>
                                    <td class="fw-bold text-primary-color"><?php echo htmlspecialchars($n['title']); ?></td>
                                    <td><?php echo htmlspecialchars($authorDisplayName); ?></td>
                                    <td><?php echo formatDate($n['created_at']); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($n['reviewer_name'] ?: '-'); ?></small></td>
                                    <td class="text-center"><?php echo statusBadge($n['status']); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <?php if (Auth::hasPermission('approve_news')): ?>
                                                <a href="news.php?action=review&id=<?php echo $n['id']; ?>" class="btn btn-sm btn-primary" title="Review Headline"><i class="fa-solid fa-file-signature me-1"></i> Review</a>
                                            <?php endif; ?>
                                            
                                            <!-- Edit Option for Reviewers and Authors -->
                                            <?php if (Auth::hasPermission('approve_news') || $n['author_id'] == Session::get('user_id')): ?>
                                                <a href="news.php?action=edit&id=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit News Content"><i class="fa-solid fa-pen-to-square me-1"></i> Edit</a>
                                            <?php endif; ?>

                                            <?php if (Auth::hasPermission('approve_news') || Auth::hasRole('Super Admin')): ?>
                                                <a href="news.php?action=delete&id=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete News" onclick="return confirm('Are you sure you want to delete this news article?');"><i class="fa-solid fa-trash"></i></a>
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
        const filterInput = document.getElementById('newsFilterInput');
        const table = document.getElementById('newsTable');
        if (filterInput && table) {
            filterInput.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                table.querySelectorAll('tbody tr').forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = (!q || text.includes(q)) ? '' : 'none';
                });
            });
        }
    });
    </script>

<?php elseif ($action === 'review' && $news): ?>
    <!-- Review Center -->
    <div class="row">
        <!-- Draft Content Preview -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-primary-color mb-0">News Headline Preview</h5>
                    <a href="news.php?action=edit&id=<?php echo $news['id']; ?>" class="btn btn-sm btn-warning text-dark font-semibold">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit &amp; Revise News
                    </a>
                </div>
                <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($news['title']); ?></h4>
                <div class="text-muted small mb-4">
                    <span>Reported by: <?php echo htmlspecialchars($news['author_name'] ?: $news['user_author_name']); ?></span> |
                    <span>Submitted: <?php echo formatDate($news['created_at']); ?></span>
                </div>
                <?php if ($news['image_path']): ?>
                    <img src="<?php echo uploadUrl('news', $news['image_path']); ?>" alt="Cover" class="img-fluid rounded mb-3 shadow-xs" style="max-height: 250px; object-fit: cover;">
                <?php endif; ?>
                <div class="bg-light p-3 rounded small text-secondary" style="line-height: 1.7; min-height: 200px;">
                    <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                </div>
            </div>
        </div>

        <!-- Verification Actions Form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2">Review &amp; Verification Decision</h5>
                <form method="POST" action="news.php">
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label font-semibold">Verification Decision *</label>
                        <select name="decision" class="form-select" required>
                            <option value="">Select decision...</option>
                            <option value="published">Approve &amp; Publish Live</option>
                            <option value="returned">Return for Correction (Draft status)</option>
                            <option value="rejected">Reject Submission</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold">Reviewer Remarks / Feedback</label>
                        <textarea name="reviewer_remarks" class="form-control" rows="4" placeholder="Feedback or publication remarks..."><?php echo htmlspecialchars($news['review_remarks'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review_decision" class="btn btn-primary w-100 py-2.5 fw-bold"><i class="fa-solid fa-check-double me-1"></i> Log Decision</button>
                </form>
            </div>
        </div>
    </div>

<?php elseif ($action === 'edit' && $news): ?>
    <!-- Full Edit / Revise News -->
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h4 class="fw-bold text-primary-color mb-0"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Edit &amp; Revise News Article</h4>
                    <span class="badge bg-secondary"><?php echo statusBadge($news['status']); ?></span>
                </div>
                
                <?php if (!empty($news['review_remarks'])): ?>
                    <div class="alert alert-warning small mb-4">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <strong>Reviewer Feedback:</strong><br>
                        <?php echo nl2br(htmlspecialchars($news['review_remarks'])); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="news.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font-semibold">News Headline / Title *</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($news['title']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Reporter / Author Name</label>
                            <input type="text" name="author_name" class="form-control" value="<?php echo htmlspecialchars($news['author_name'] ?: $news['user_author_name']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Author Email</label>
                            <input type="email" name="author_email" class="form-control" value="<?php echo htmlspecialchars($news['author_email'] ?? ''); ?>">
                        </div>

                        <?php if (Auth::hasPermission('approve_news')): ?>
                            <div class="col-12">
                                <label class="form-label font-semibold">Publication Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="under_review" <?php echo $news['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="published" <?php echo $news['status'] === 'published' ? 'selected' : ''; ?>>Published Live</option>
                                    <option value="returned" <?php echo $news['status'] === 'returned' ? 'selected' : ''; ?>>Returned for Correction</option>
                                    <option value="rejected" <?php echo $news['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="draft" <?php echo $news['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="status" value="submitted">
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label font-semibold">News Content Body *</label>
                            <textarea name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($news['content']); ?></textarea>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label font-semibold">Replace Cover Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <?php if (!empty($news['image_path'])): ?>
                            <div class="col-md-4">
                                <label class="form-label font-semibold d-block">Current Cover</label>
                                <img src="<?php echo uploadUrl('news', $news['image_path']); ?>" alt="Current" class="img-thumbnail" style="height: 60px; object-fit: cover;">
                            </div>
                        <?php endif; ?>

                        <?php if (Auth::hasPermission('approve_news')): ?>
                            <div class="col-12">
                                <label class="form-label font-semibold">Reviewer Notes &amp; Editorial Remarks</label>
                                <textarea name="review_remarks" class="form-control" rows="2" placeholder="Editorial feedback or notes..."><?php echo htmlspecialchars($news['review_remarks'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>

                        <div class="col-12 text-end mt-4">
                            <a href="news.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" name="save_news_edit" class="btn btn-primary px-4 py-2 font-semibold">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
