<?php
require_once __DIR__ . '/core/bootstrap.php';

$cms = new ContentManager();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['submit_news'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $author_name = Security::sanitize($_POST['author_name'] ?? '');
    $author_email = Security::sanitize($_POST['author_email'] ?? '');
    $title = Security::sanitize($_POST['title']);
    $content = Security::sanitize($_POST['content']);
    $author_id = Session::get('user_id') ?: 1;

    if (!Security::checkRateLimit('submit_news', 5, 300)) {
        setFlash('danger', 'Too many news submissions. Please wait a few minutes before submitting another.');
    } elseif (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'Invalid security token. Please try again.');
    } elseif (empty($title) || empty($content) || empty($author_name) || empty($author_email)) {
        setFlash('danger', 'Please fill in all required fields including your Name and Email.');
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
                $uploaded_image = Security::uploadFile($_FILES['image'], 'news', 'news_');
                if (!$uploaded_image) {
                    setFlash('danger', 'Failed to upload news cover image.');
                    $valid_upload = false;
                }
            }
        }

        if ($valid_upload) {
            $slug = slugify($title) . '-' . time();
            $formatted_content = (!Auth::check() ? "Reported By: {$author_name} ({$author_email})\n\n" : "") . $content;
            
            $news_id = $cms->db->insert('news', [
                'title' => $title,
                'slug' => $slug,
                'content' => $formatted_content,
                'author_id' => $author_id,
                'author_name' => $author_name,
                'author_email' => $author_email,
                'image_path' => $uploaded_image,
                'status' => 'submitted', // Triggers approval workflow: Submitted -> Admin review -> Published
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($news_id) {
                setFlash('success', 'News article submitted successfully for verification! The editorial team will review and publish it.');
                redirect('news.php');
                exit;
            } else {
                setFlash('danger', 'Error saving news submission. Please try again.');
            }
        }
    }
}

$page_title = "Submit Campus News";
include_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb Header -->
<div class="bg-light py-4 border-bottom mb-5">
    <div class="container">
        <h1 class="fw-bold mb-1 text-primary-color"><i class="fa-solid fa-paper-plane text-warning me-2"></i>Submit Campus News Update</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="news.php" class="text-decoration-none">News Portal</a></li>
                <li class="breadcrumb-item active" aria-current="page">Submit News</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Guidelines column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 bg-light mb-4" style="border-top: 4px solid var(--secondary-color);">
                <h5 class="fw-bold text-primary-color mb-3"><i class="fa-solid fa-circle-exclamation text-warning me-2"></i>News Submission Policies</h5>
                <ul class="small text-muted ps-3 d-flex flex-column gap-2 mb-0">
                    <li>News submissions can be made directly by students, faculty members, and campus reporters.</li>
                    <li>Coverage must strictly concern official college announcements, research achievements, placement highlights, sports meets, or faculty advancements.</li>
                    <li>Ensure high-definition banner images are uploaded where possible (JPG, PNG, WEBP max size 5MB).</li>
                    <li>All submitted news goes to the review queue and will be published upon admin verification.</li>
                </ul>
            </div>

            <div class="card border-0 shadow-sm p-4 bg-primary text-white text-center">
                <i class="fa-solid fa-newspaper fa-3x text-warning mb-3"></i>
                <h5 class="fw-bold mb-1">Direct Campus Reporting</h5>
                <p class="small text-white-50 mb-0">No login required! Enter your details and article text to submit directly for campus publishing.</p>
            </div>
        </div>

        <!-- Submission Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-square-rss text-warning me-2"></i>Campus News Submission Form</h4>
                
                <form method="POST" action="submit-news.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Your Full Name *</label>
                            <input type="text" name="author_name" class="form-control" value="<?php echo Session::get('full_name') ?? ''; ?>" placeholder="e.g. Rahul Sharma (Student - CSE)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Your Email Address *</label>
                            <input type="email" name="author_email" class="form-control" value="<?php echo Session::get('email') ?? ''; ?>" placeholder="e.g. rahul.sharma@mgmu.ac.in" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold">News Headline / Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. SOET Students Win National Robotics Championship 2026" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold">Banner Image (JPEG / PNG / WEBP)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Optional cover photo for the news headline.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-semibold">News Details & Description *</label>
                        <textarea name="content" class="form-control" rows="10" placeholder="Write full news article content, event details, achievements, or press release here..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="news.php" class="btn btn-outline-secondary px-3"><i class="fa-solid fa-arrow-left me-1"></i> Back to News</a>
                        <button type="submit" name="submit_news" class="btn btn-warning text-dark fw-bold px-4 shadow-sm"><i class="fa-solid fa-paper-plane me-1"></i> Submit News Article</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
