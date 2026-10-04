<?php
/**
 * Public Contact & Helpdesk Controller & View
 *
 * Provides campus contact directories and an interactive inquiry form:
 * - Processes public inquiries with CSRF protection, input sanitization, and IP rate limiting
 * - Displays institutional phone numbers, email addresses, and campus postal address
 * - Features embedded Google Map location for MGM University campus, CIDCO N-6
 */
$page_title = "Contact Us";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['submit_contact'])) {
    $name = Security::sanitize($_POST['name']);
    $email = Security::sanitizeEmail($_POST['email']);
    $subject = Security::sanitize($_POST['subject']);
    $message = Security::sanitize($_POST['message']);
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!Security::checkRateLimit('contact_form', 5, 300)) {
        setFlash('danger', 'Too many requests. Please wait a few minutes before submitting another message.');
    } elseif (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'Invalid security token. Please try again.');
    } elseif (empty($name) || empty($email) || empty($subject) || empty($message)) {
        setFlash('danger', 'Please fill in all mandatory fields.');
    } elseif (!Security::validateEmail($email)) {
        setFlash('danger', 'Invalid email address.');
    } else {
        $saved = $cms->db->insert('contact_messages', [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'status' => 'unread',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($saved) {
            setFlash('success', 'Your message has been sent successfully! Our administrative team will reach out to you shortly.');
        } else {
            setFlash('danger', 'We are unable to log your message at the moment. Please try again later.');
        }
    }
    redirect('contact.php');
}

$settings = $cms->getSiteSettings();
$col_phone = $settings['college_phone'] ?? '+91-9371714253 / 0240-6481000 (Ext. 2801)';
$col_email = $settings['college_email'] ?? 'admissionsoet@mgmu.ac.in';
$col_addr = $settings['college_address'] ?? 'School of Engineering & Technology, MGM Campus, N-6, CIDCO, Chhatrapati Sambhajinagar (Aurangabad) - 431003, Maharashtra, India';
$contact_bg = !empty($settings['contact_hero_image']) ? uploadUrl('settings', $settings['contact_hero_image']) : uploadUrl('gallery', 'soet_campus_aerial.jpg');
?>

<!-- Breadcrumb Header -->
<div class="page-hero mb-5" style="<?php echo $contact_bg ? "background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{$contact_bg}') center/cover no-repeat;" : ""; ?>">
    <div class="container position-relative z-1">
        <h1 class="font-display fw-extrabold mb-2 text-white">Contact Campus Office</h1>
        <p class="lead opacity-80 mb-3">Get in touch with our admissions desk, administration office, or departmental counselors</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Contact Us</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-5">
        <!-- Contact details -->
        <div class="col-lg-5">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 font-semibold">Support & Assistance</span>
            </div>
            <h3 class="font-display fw-bold text-dark mb-3">Get In Touch With Us</h3>
            <p class="text-muted mb-4">Have questions regarding admissions, fee structures, courses, or hostel facilities? Submit your query and our counselors will respond promptly.</p>
            
            <div class="d-flex flex-column gap-3 mb-4">
                <div class="d-flex align-items-start gap-3 p-3 rounded-4 bg-white border shadow-xs">
                    <div class="stat-icon-wrapper bg-primary text-white rounded-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-location-dot fa-lg text-warning"></i>
                    </div>
                    <div>
                        <h6 class="font-display fw-bold text-dark mb-1">Campus Location</h6>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($col_addr); ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 p-3 rounded-4 bg-white border shadow-xs">
                    <div class="stat-icon-wrapper bg-primary text-white rounded-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-phone-volume fa-lg text-warning"></i>
                    </div>
                    <div>
                        <h6 class="font-display fw-bold text-dark mb-1">Contact Desk</h6>
                        <p class="text-muted small mb-0"><a href="tel:<?php echo htmlspecialchars($col_phone); ?>" class="text-decoration-none text-dark fw-semibold"><?php echo htmlspecialchars($col_phone); ?></a></p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 p-3 rounded-4 bg-white border shadow-xs">
                    <div class="stat-icon-wrapper bg-primary text-white rounded-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-envelope-open-text fa-lg text-warning"></i>
                    </div>
                    <div>
                        <h6 class="font-display fw-bold text-dark mb-1">Email Helpline</h6>
                        <p class="text-muted small mb-0"><a href="mailto:<?php echo htmlspecialchars($col_email); ?>" class="text-decoration-none text-muted" title="Send email to <?php echo htmlspecialchars($col_email); ?>"><?php echo htmlspecialchars($col_email); ?></a></p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 p-3 rounded-4 bg-white border shadow-xs">
                    <div class="stat-icon-wrapper bg-primary text-white rounded-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-globe fa-lg text-warning"></i>
                    </div>
                    <div>
                        <h6 class="font-display fw-bold text-dark mb-1">Official Portals</h6>
                        <p class="text-muted small mb-0">Website: <a href="<?php echo htmlspecialchars($settings['college_website'] ?? 'https://soet.mgmu.ac.in'); ?>" target="_blank" class="text-primary text-decoration-none">soet.mgmu.ac.in</a><br>Admissions: <a href="<?php echo htmlspecialchars($settings['admission_website'] ?? 'https://admissions.mgmu.ac.in'); ?>" target="_blank" class="text-primary text-decoration-none font-semibold">admissions.mgmu.ac.in</a></p>
                    </div>
                </div>
            </div>

            <!-- Official Social Media Channels -->
            <div class="p-4 rounded-4 bg-white border shadow-xs mb-4">
                <h6 class="font-display fw-bold text-dark mb-3"><i class="fa-solid fa-share-nodes text-warning me-2"></i>Official Social Media Channels</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (!empty($settings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_facebook']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 font-semibold d-inline-flex align-items-center">
                            <i class="fa-brands fa-facebook-f text-primary me-2"></i> Facebook
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_linkedin'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_linkedin']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 font-semibold d-inline-flex align-items-center">
                            <i class="fa-brands fa-linkedin-in text-primary me-2"></i> LinkedIn
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_instagram']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 font-semibold d-inline-flex align-items-center">
                            <i class="fa-brands fa-instagram text-danger me-2"></i> Instagram
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_twitter']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1.5 font-semibold d-inline-flex align-items-center">
                            <i class="fa-brands fa-x-twitter text-dark me-2"></i> Twitter / X
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_youtube'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_youtube']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 font-semibold d-inline-flex align-items-center">
                            <i class="fa-brands fa-youtube text-danger me-2"></i> YouTube
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_whatsapp'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['social_whatsapp']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1.5 font-semibold d-inline-flex align-items-center">
                            <i class="fa-brands fa-whatsapp text-success me-2"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Official Google Maps Embed -->
            <div class="card border-0 shadow-sm overflow-hidden position-relative rounded-4" style="height: 260px;">
                <iframe 
                    src="<?php echo htmlspecialchars($settings['google_maps_iframe'] ?? 'https://maps.google.com/maps?q=MGMU+School+of+Engineering+and+Technology,+CIDCO,+Chhatrapati+Sambhajinagar&t=&z=16&ie=UTF8&iwloc=&output=embed'); ?>" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
                <div class="position-absolute bottom-0 end-0 p-3 z-1">
                    <a href="<?php echo htmlspecialchars($settings['maps_link'] ?? 'https://maps.app.goo.gl/NUzWW6mAcYVX5Tg96'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-dark bg-opacity-90 rounded-pill px-3 shadow-sm">
                        <i class="fa-solid fa-map-pin text-warning me-1"></i> Open in Google Maps
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-semibold">Drop a Message</span>
                </div>
                <h4 class="font-display fw-bold text-dark mb-4">Send Us a Direct Message</h4>
                <form method="POST" action="contact.php" class="needs-validation" novalidate>
                    <?php echo Security::csrfField(); ?>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Your Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3 py-2.5" placeholder="John Doe" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-dark">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control rounded-3 py-2.5" placeholder="john@example.com" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold text-dark">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control rounded-3 py-2.5" placeholder="Query regarding admissions, syllabus, or fee details" required>
                            <div class="invalid-feedback">Please enter a subject.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold text-dark">Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control rounded-3" rows="5" placeholder="Write full details of your inquiry here..." required></textarea>
                            <div class="invalid-feedback">Please write your message.</div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="submit_contact" class="btn btn-primary px-4 py-2.5 rounded-pill font-semibold shadow-sm"><i class="fa-solid fa-paper-plane me-2 text-warning"></i> Send Query</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
