<?php
require_once __DIR__ . '/core/bootstrap.php';

$cms = new ContentManager();
$departments = $cms->getDepartments();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['submit_blog'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $author_name = Security::sanitize($_POST['author_name'] ?? '');
    $author_email = Security::sanitize($_POST['author_email'] ?? '');
    $title = Security::sanitize($_POST['title']);
    $content = Security::sanitize($_POST['content']);
    $dept_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $author_id = Session::get('user_id') ?: 1;

    if (!Security::checkRateLimit('submit_blog', 5, 300)) {
        setFlash('danger', 'Too many blog submissions. Please wait a few minutes before submitting another.');
    } elseif (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'Invalid security token. Please try again.');
    } elseif (empty($title) || empty($content) || empty($author_name) || empty($author_email)) {
        setFlash('danger', 'Please enter your Name, Email, Title, and Article Content.');
    } elseif (!Security::validateEmail($author_email)) {
        setFlash('danger', 'Please provide a valid email address.');
    } else {
        $uploaded_image = null;
        $valid_upload = true;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $errors = Security::validateUpload($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if (!empty($errors)) {
                setFlash('danger', implode(' ', $errors));
                $valid_upload = false;
            } else {
                $uploaded_image = Security::uploadFile($_FILES['image'], 'blogs', 'blog_');
                if (!$uploaded_image) {
                    setFlash('danger', 'Failed to upload cover image.');
                    $valid_upload = false;
                }
            }
        }

        if ($valid_upload) {
            $slug = slugify($title) . '-' . time();
            $formatted_content = (!Auth::check() ? "Author: {$author_name} ({$author_email})\n\n" : "") . $content;
            
            $blog_id = $cms->db->insert('blogs', [
                'title' => $title,
                'slug' => $slug,
                'content' => $formatted_content,
                'author_id' => $author_id,
                'author_name' => $author_name,
                'author_email' => $author_email,
                'department_id' => $dept_id,
                'image_path' => $uploaded_image,
                'status' => 'submitted', // Triggers approval workflow: Author -> Submitted -> Reviewed -> Published
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($blog_id) {
                setFlash('success', 'Blog article submitted successfully for review! It will be verified by the admin before publishing.');
                redirect('blogs.php');
                exit;
            } else {
                setFlash('danger', 'Error submitting blog. Please try again.');
            }
        }
    }
}

$page_title = "Submit a Blog";
include_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb Header -->
<div class="bg-light py-4 border-bottom mb-5">
    <div class="container">
        <h1 class="fw-bold mb-1 text-primary-color"><i class="fa-solid fa-pen-nib text-warning me-2"></i>Submit a Blog Article</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="blogs.php" class="text-decoration-none">Blogs</a></li>
                <li class="breadcrumb-item active" aria-current="page">Submit Blog</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Guidelines column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 bg-light mb-4" style="border-top: 4px solid var(--secondary-color);">
                <h5 class="fw-bold text-primary-color mb-3"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Blog Submission Guidelines</h5>
                <ul class="small text-muted ps-3 d-flex flex-column gap-2">
                    <li>Public submissions are open to all students, alumni, faculty, and technical writers.</li>
                    <li>Articles should focus on engineering trends, programming guides, campus events, or research papers.</li>
                    <li>Plagiarism is strictly prohibited. Cite references when mentioning facts or studies.</li>
                    <li>Cover images should be clean and relevant (max size: 5MB).</li>
                    <li>All submitted blogs enter an admin review queue and will be published upon verification.</li>
                </ul>
            </div>
            
            <div class="card border-0 shadow-sm p-4 bg-dark text-white text-center">
                <i class="fa-solid fa-timeline fa-2x text-warning mb-3"></i>
                <h5 class="fw-bold">Public Submission Workflow</h5>
                <div class="text-start text-white-50 small mt-3">
                    <div class="workflow-step active">
                        <strong>Step 1: Write & Submit</strong>
                        <p class="mb-0 text-white-50" style="font-size: 11px;">Author submits details and article draft online.</p>
                    </div>
                    <div class="workflow-step">
                        <strong>Step 2: Admin Verification</strong>
                        <p class="mb-0 text-white-50" style="font-size: 11px;">Editorial team or HOD reviews draft details.</p>
                    </div>
                    <div class="workflow-step">
                        <strong>Step 3: Go Live</strong>
                        <p class="mb-0 text-white-50" style="font-size: 11px;">Approved blog goes live on the college feed.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submission Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-feather-pointed text-warning me-2"></i>Write Article Form</h4>
                
                <form method="POST" action="submit-blog.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Your Full Name *</label>
                            <input type="text" name="author_name" class="form-control" value="<?php echo Session::get('full_name') ?? ''; ?>" placeholder="e.g. Prof. Sneha Patil (Faculty - CSE)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Your Email Address *</label>
                            <input type="email" name="author_email" class="form-control" value="<?php echo Session::get('email') ?? ''; ?>" placeholder="e.g. sneha.patil@mgmu.ac.in" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold">Blog Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="Enter a descriptive and engaging title" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Related Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">General / Interdisciplinary</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Cover Image (JPEG / PNG / WEBP)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-semibold">Article Content *</label>
                        <textarea name="content" class="form-control" rows="10" placeholder="Start writing your article details, code examples, or research findings here..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="blogs.php" class="btn btn-outline-secondary px-3"><i class="fa-solid fa-arrow-left me-1"></i> Back to Blogs</a>
                        <button type="submit" name="submit_blog" class="btn btn-warning text-dark fw-bold px-4 shadow-sm"><i class="fa-solid fa-paper-plane me-1"></i> Submit Article for Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
