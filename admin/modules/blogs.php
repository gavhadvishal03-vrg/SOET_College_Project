<?php
$page_title = "Blogs Review & Management Center";
include_once __DIR__ . '/../includes/header.php';
Auth::requireLogin();

// Users with approve_blogs or submit_blogs can view this section
if (!Auth::hasPermission('approve_blogs') && !Auth::hasPermission('submit_blogs')) {
    setFlash('danger', 'Access denied.');
    redirect(APP_URL . '/admin/dashboard.php');
}

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$blog = null;
$departments = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name ASC");

if (in_array($action, ['review', 'edit']) && isset($_GET['id'])) {
    $blog = $db->fetchOne(
        "SELECT b.*, u.full_name as user_author_name, d.name as department_name 
         FROM blogs b JOIN users u ON b.author_id = u.id 
         LEFT JOIN departments d ON b.department_id = d.id 
         WHERE b.id = ?",
        [(int)$_GET['id']]
    );
    
    // Change status to under_review if currently submitted
    if ($blog && $blog['status'] === 'submitted' && Auth::hasPermission('approve_blogs')) {
        $db->update('blogs', ['status' => 'under_review', 'reviewer_id' => Session::get('user_id')], 'id = ?', [$blog['id']]);
        $blog['status'] = 'under_review';
    }
}

// Handle Admin Review Decision
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review_decision'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $blog_id = (int)$_POST['blog_id'];
    $decision = $_POST['decision']; // published, returned, rejected
    $remarks = trim($_POST['reviewer_remarks']);

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($decision)) {
        setFlash('danger', 'Please choose a valid review decision.');
    } else {
        $published_at = ($decision === 'published') ? date('Y-m-d H:i:s') : null;
        $db->update('blogs', [
            'status' => $decision,
            'reviewer_id' => Session::get('user_id'),
            'review_remarks' => $remarks,
            'published_at' => $published_at,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$blog_id]);

        // Send Email Notification to Author
        $blogItem = $db->fetchOne("SELECT b.*, u.email as user_email, u.full_name as user_full_name FROM blogs b LEFT JOIN users u ON b.author_id = u.id WHERE b.id = ?", [$blog_id]);
        $recipientEmail = $blogItem['author_email'] ?? $blogItem['user_email'] ?? '';
        $recipientName = $blogItem['author_name'] ?? $blogItem['user_full_name'] ?? 'Author';

        if (!empty($recipientEmail)) {
            Mailer::sendReviewNotification($recipientEmail, $recipientName, 'Blog Article', $blogItem['title'] ?? 'Blog Article', $decision, $remarks);
        }

        setFlash('success', 'Review decision logged: ' . ucfirst($decision) . '. Notification sent to author.');
        redirect('blogs.php');
    }
}

// Handle Complete Edit / Revision of Blog
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_blog_edit'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $blog_id = (int)$_POST['blog_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $author_name = trim($_POST['author_name'] ?? '');
    $author_email = trim($_POST['author_email'] ?? '');
    $dept_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $status = $_POST['status'] ?? 'submitted';
    $remarks = trim($_POST['review_remarks'] ?? '');

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($content)) {
        setFlash('danger', 'Title and Content cannot be blank.');
    } else {
        $existing = $db->fetchOne("SELECT * FROM blogs WHERE id = ?", [$blog_id]);
        $uploaded_image = $existing['image_path'] ?? null;

        // Check if new image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $errors = Security::validateUpload($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if (!empty($errors)) {
                setFlash('danger', implode(' ', $errors));
            } else {
                $new_image = Security::uploadFile($_FILES['image'], 'blogs', 'blog_');
                if ($new_image) {
                    $uploaded_image = $new_image;
                }
            }
        }

        $updateData = [
            'title' => $title,
            'content' => $content,
            'author_name' => $author_name ?: ($existing['author_name'] ?? 'Author'),
            'author_email' => $author_email ?: ($existing['author_email'] ?? ''),
            'department_id' => $dept_id,
            'image_path' => $uploaded_image,
            'status' => $status,
            'review_remarks' => $remarks,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'published' && empty($existing['published_at'])) {
            $updateData['published_at'] = date('Y-m-d H:i:s');
            $updateData['reviewer_id'] = Session::get('user_id');
        }

        $db->update('blogs', $updateData, 'id = ?', [$blog_id]);

        setFlash('success', 'Blog article updated and saved successfully.');
        redirect('blogs.php');
    }
}

// Handle Delete Blog
if ($action === 'delete' && isset($_GET['id']) && Auth::hasPermission('approve_blogs')) {
    $blog_id = (int)$_GET['id'];
    $db->delete('blogs', 'id = ?', [$blog_id]);
    setFlash('success', 'Blog article deleted successfully.');
    redirect('blogs.php');
}

// Fetch lists depending on user permissions
$query = "SELECT b.*, u.full_name as user_full_name, d.name as department_name, r.full_name as reviewer_name
          FROM blogs b 
          JOIN users u ON b.author_id = u.id 
          LEFT JOIN departments d ON b.department_id = d.id 
          LEFT JOIN users r ON b.reviewer_id = r.id";

$params = [];
if (!Auth::hasPermission('approve_blogs')) {
    $query .= " WHERE b.author_id = ?";
    $params[] = Session::get('user_id');
} else {
    if (Auth::hasRole('HOD')) {
        $query .= " WHERE b.department_id = ? OR b.author_id = ?";
        $params[] = Session::get('department_id');
        $params[] = Session::get('user_id');
    }
}
$query .= " ORDER BY b.created_at DESC";
$blogs = $db->fetchAll($query, $params);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-pen-nib text-warning me-2"></i>Blog Review &amp; Management Queue</h1>
        <small class="text-muted">Review, verify, edit, and publish technical articles and student blog submissions</small>
    </div>
    <?php if ($action !== 'list'): ?>
        <a href="blogs.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to Queue</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-list-check text-warning me-2"></i>Submitted Blog Articles</h5>
            <input type="text" id="blogFilterInput" class="form-control form-control-sm" placeholder="Search blog title, author..." style="width: 220px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="blogsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Blog Title</th>
                            <th>Author Name</th>
                            <th>Department</th>
                            <th>Date Submitted</th>
                            <th>Reviewer</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blogs)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">No blog articles found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($blogs as $b): 
                                $authorDisplayName = $b['author_name'] ?: $b['user_full_name'];
                            ?>
                                <tr>
                                    <td class="fw-bold text-primary-color"><?php echo htmlspecialchars($b['title']); ?></td>
                                    <td><?php echo htmlspecialchars($authorDisplayName); ?></td>
                                    <td><span class="small font-semibold text-secondary"><?php echo htmlspecialchars($b['department_name'] ?: 'General'); ?></span></td>
                                    <td><?php echo formatDate($b['created_at']); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($b['reviewer_name'] ?: '-'); ?></small></td>
                                    <td class="text-center"><?php echo statusBadge($b['status']); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <?php if (Auth::hasPermission('approve_blogs')): ?>
                                                <a href="blogs.php?action=review&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-primary" title="Review Submission"><i class="fa-solid fa-file-signature me-1"></i> Review</a>
                                            <?php endif; ?>
                                            
                                            <!-- Edit Option for Reviewers and Authors -->
                                            <?php if (Auth::hasPermission('approve_blogs') || $b['author_id'] == Session::get('user_id')): ?>
                                                <a href="blogs.php?action=edit&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit Article Content"><i class="fa-solid fa-pen-to-square me-1"></i> Edit</a>
                                            <?php endif; ?>

                                            <?php if (Auth::hasPermission('approve_blogs') || Auth::hasRole('Super Admin')): ?>
                                                <a href="blogs.php?action=delete&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Blog" onclick="return confirm('Are you sure you want to delete this blog article?');"><i class="fa-solid fa-trash"></i></a>
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
        const filterInput = document.getElementById('blogFilterInput');
        const table = document.getElementById('blogsTable');
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

<?php elseif ($action === 'review' && $blog): ?>
    <!-- Review Center -->
    <div class="row">
        <!-- Draft Content Preview -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-primary-color mb-0">Draft Content Preview</h5>
                    <a href="blogs.php?action=edit&id=<?php echo $blog['id']; ?>" class="btn btn-sm btn-warning text-dark font-semibold">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit &amp; Revise Article
                    </a>
                </div>
                <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($blog['title']); ?></h4>
                <div class="text-muted small mb-4">
                    <span>By: <?php echo htmlspecialchars($blog['author_name'] ?: $blog['user_author_name']); ?></span> | 
                    <span>Department: <?php echo htmlspecialchars($blog['department_name'] ?: 'General'); ?></span> |
                    <span>Submitted: <?php echo formatDate($blog['created_at']); ?></span>
                </div>
                <?php if ($blog['image_path']): ?>
                    <img src="<?php echo uploadUrl('blogs', $blog['image_path']); ?>" alt="Cover" class="img-fluid rounded mb-3 shadow-xs" style="max-height: 250px; object-fit: cover;">
                <?php endif; ?>
                <div class="bg-light p-3 rounded small text-secondary" style="line-height: 1.7; min-height: 200px;">
                    <?php echo nl2br(htmlspecialchars($blog['content'])); ?>
                </div>
            </div>
        </div>

        <!-- Verification Actions Form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2">Review &amp; Verification Decision</h5>
                <form method="POST" action="blogs.php">
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
                    
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
                        <textarea name="reviewer_remarks" class="form-control" rows="4" placeholder="Mention why it is returned or rejected, or publish remarks..."><?php echo htmlspecialchars($blog['review_remarks'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review_decision" class="btn btn-primary w-100 py-2.5 fw-bold"><i class="fa-solid fa-check-double me-1"></i> Log Decision</button>
                </form>
            </div>
        </div>
    </div>

<?php elseif ($action === 'edit' && $blog): ?>
    <!-- Full Edit / Revise Blog -->
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h4 class="fw-bold text-primary-color mb-0"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Edit &amp; Revise Blog Article</h4>
                    <span class="badge bg-secondary"><?php echo statusBadge($blog['status']); ?></span>
                </div>
                
                <?php if (!empty($blog['review_remarks'])): ?>
                    <div class="alert alert-warning small mb-4">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <strong>Reviewer Feedback:</strong><br>
                        <?php echo nl2br(htmlspecialchars($blog['review_remarks'])); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="blogs.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font-semibold">Blog Article Title *</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($blog['title']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Author Display Name</label>
                            <input type="text" name="author_name" class="form-control" value="<?php echo htmlspecialchars($blog['author_name'] ?: $blog['user_author_name']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Author Email</label>
                            <input type="email" name="author_email" class="form-control" value="<?php echo htmlspecialchars($blog['author_email'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Academic Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- General / Multi-disciplinary --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ($blog['department_id'] == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (Auth::hasPermission('approve_blogs')): ?>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Publication Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="under_review" <?php echo $blog['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="published" <?php echo $blog['status'] === 'published' ? 'selected' : ''; ?>>Published Live</option>
                                    <option value="returned" <?php echo $blog['status'] === 'returned' ? 'selected' : ''; ?>>Returned for Correction</option>
                                    <option value="rejected" <?php echo $blog['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="draft" <?php echo $blog['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="status" value="submitted">
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label font-semibold">Article Content Body *</label>
                            <textarea name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($blog['content']); ?></textarea>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label font-semibold">Replace Cover Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <?php if (!empty($blog['image_path'])): ?>
                            <div class="col-md-4">
                                <label class="form-label font-semibold d-block">Current Cover</label>
                                <img src="<?php echo uploadUrl('blogs', $blog['image_path']); ?>" alt="Current" class="img-thumbnail" style="height: 60px; object-fit: cover;">
                            </div>
                        <?php endif; ?>

                        <?php if (Auth::hasPermission('approve_blogs')): ?>
                            <div class="col-12">
                                <label class="form-label font-semibold">Reviewer Notes &amp; Editorial Feedback</label>
                                <textarea name="review_remarks" class="form-control" rows="2" placeholder="Editorial feedback or notes..."><?php echo htmlspecialchars($blog['review_remarks'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>

                        <div class="col-12 text-end mt-4">
                            <a href="blogs.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" name="save_blog_edit" class="btn btn-primary px-4 py-2 font-semibold">
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
