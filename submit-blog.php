<?php
/**
 * Public Blog Article Submission Controller & View
 *
 * Allows students, faculty, and research scholars to submit blog articles for editorial review:
 * - Validates author contact information, article body, and cover image upload (MIME verified)
 * - Assigns status 'submitted' to initiate the editorial review pipeline (Submitted -> Under Review -> Published)
 * - Generates unique URL slugs and applies rate limiting to prevent spam submissions
 */
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
<div class="page-hero mb-5">
    <div class="container position-relative z-1">
        <h1 class="font-display fw-extrabold mb-2 text-white">Submit a Blog Article</h1>
        <p class="lead opacity-80 mb-3">Share your research breakthroughs, tutorials, technical analyses, or campus innovations</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="blogs.php" class="text-white opacity-75 text-decoration-none">Blogs</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Submit Article</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Guidelines column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 bg-white mb-4 rounded-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 font-semibold">Editorial Guidelines</span>
                </div>
                <h5 class="font-display fw-bold text-dark mb-3">Submission Checklist</h5>
                <ul class="small text-muted ps-3 d-flex flex-column gap-2 mb-0">
                    <li>Open to all students, faculty members, alumni, and campus researchers.</li>
                    <li>Focus on engineering advancements, hands-on tutorials, software projects, or campus updates.</li>
                    <li>Plagiarism is strictly prohibited. Clearly attribute code repositories and references.</li>
                    <li>Include high-resolution cover photos (PNG, JPG, WEBP under 5MB).</li>
                    <li>Submitted drafts undergo editorial review before appearing on the public feed.</li>
                </ul>
            </div>
            
            <div class="card border-0 shadow-sm p-4 text-white rounded-4" style="background: linear-gradient(135deg, var(--primary), var(--primary-600));">
                <div class="stat-icon-wrapper bg-white bg-opacity-10 text-warning mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-timeline fa-xl"></i>
                </div>
                <h5 class="font-display fw-bold text-center mb-3">Submission Process</h5>
                <div class="d-flex flex-column gap-3 text-start small">
                    <div class="p-2.5 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                        <strong class="text-warning d-block mb-0.5">1. Write & Upload</strong>
                        <span class="text-white opacity-75" style="font-size: 14px;">Author writes article and uploads cover media.</span>
                    </div>
                    <div class="p-2.5 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                        <strong class="text-warning d-block mb-0.5">2. Editorial Review</strong>
                        <span class="text-white opacity-75" style="font-size: 14px;">Department coordinators verify technical accuracy.</span>
                    </div>
                    <div class="p-2.5 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                        <strong class="text-warning d-block mb-0.5">3. Live Publication</strong>
                        <span class="text-white opacity-75" style="font-size: 14px;">Approved article appears on the portal with author credit.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submission Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-semibold">Author Submission</span>
                </div>
                <h4 class="font-display fw-bold text-dark mb-4">Write Article Details</h4>
                
                <form method="POST" action="submit-blog.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Your Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="author_name" class="form-control rounded-3 py-2.5" value="<?php echo Session::get('full_name') ?? ''; ?>" placeholder="e.g. Prof. Sneha Patil (Faculty - CSE)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Your Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="author_email" class="form-control rounded-3 py-2.5" value="<?php echo Session::get('email') ?? ''; ?>" placeholder="e.g. sneha.patil@mgmu.ac.in" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-semibold text-dark">Blog Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control rounded-3 py-2.5" placeholder="Enter a descriptive and engaging title" required>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Related Department</label>
                            <select name="department_id" class="form-select rounded-3 py-2.5">
                                <option value="">General / Interdisciplinary</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Cover Image (JPEG / PNG / WEBP)</label>
                            <input type="file" name="image" class="form-control rounded-3 py-2" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-semibold text-dark">Article Content <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control rounded-3" rows="10" placeholder="Start writing your article details, code examples, or research findings here..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                        <a href="blogs.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="fa-solid fa-arrow-left me-1"></i> Back to Blogs</a>
                        <button type="submit" name="submit_blog" class="btn btn-warning text-dark font-semibold rounded-pill px-4 py-2.5 shadow-sm"><i class="fa-solid fa-paper-plane me-1"></i> Submit Article for Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
