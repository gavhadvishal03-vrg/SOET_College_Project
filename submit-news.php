<?php
/**
 * Public News Bulletin & Press Submission Controller & View
 *
 * Enables departmental coordinators, event organizers, and student reporters to submit
 * news bulletins and press releases for administrator verification and publication.
 */
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
<div class="page-hero mb-5">
    <div class="container position-relative z-1">
        <h1 class="font-display fw-extrabold mb-2 text-white">Submit Campus News</h1>
        <p class="lead opacity-80 mb-3">Report campus developments, student accolades, competitions, and department achievements</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="news.php" class="text-white opacity-75 text-decoration-none">News Portal</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Submit News</li>
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
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 font-semibold">Campus Reporting Policies</span>
                </div>
                <h5 class="font-display fw-bold text-dark mb-3">News Submission Guidelines</h5>
                <ul class="small text-muted ps-3 d-flex flex-column gap-2 mb-0">
                    <li>Submissions are accepted from students, faculty members, and student council reporters.</li>
                    <li>Coverage should pertain to official college activities, research publications, hackathons, sports championships, or placements.</li>
                    <li>Upload clear landscape banner photos (PNG, JPG, WEBP under 5MB).</li>
                    <li>All submitted news is reviewed by the media coordinator before public release.</li>
                </ul>
            </div>

            <div class="card border-0 shadow-sm p-4 text-white text-center rounded-4" style="background: linear-gradient(135deg, var(--primary), var(--primary-600));">
                <div class="stat-icon-wrapper bg-white bg-opacity-10 text-warning mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-bullhorn fa-xl"></i>
                </div>
                <h5 class="font-display fw-bold mb-1">Direct Campus Reporting</h5>
                <p class="small text-white opacity-75 mb-0">No login required! Enter your details and press release to submit directly for campus publishing.</p>
            </div>
        </div>

        <!-- Submission Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-semibold">Press Release Form</span>
                </div>
                <h4 class="font-display fw-bold text-dark mb-4">News Announcement Details</h4>
                
                <form method="POST" action="submit-news.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Your Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="author_name" class="form-control rounded-3 py-2.5" value="<?php echo Session::get('full_name') ?? ''; ?>" placeholder="e.g. Rahul Sharma (Student - CSE)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Your Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="author_email" class="form-control rounded-3 py-2.5" value="<?php echo Session::get('email') ?? ''; ?>" placeholder="e.g. rahul.sharma@mgmu.ac.in" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-semibold text-dark">News Headline / Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control rounded-3 py-2.5" placeholder="e.g. SOET Students Win National Robotics Championship 2026" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-semibold text-dark">Banner Image (JPEG / PNG / WEBP)</label>
                        <input type="file" name="image" class="form-control rounded-3 py-2" accept="image/*">
                        <small class="text-muted">Optional landscape cover photo for the news headline.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-semibold text-dark">News Details & Description <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control rounded-3" rows="10" placeholder="Write full news article content, event details, achievements, or press release here..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                        <a href="news.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="fa-solid fa-arrow-left me-1"></i> Back to News</a>
                        <button type="submit" name="submit_news" class="btn btn-warning text-dark font-semibold rounded-pill px-4 py-2.5 shadow-sm"><i class="fa-solid fa-paper-plane me-1"></i> Submit News Article</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
