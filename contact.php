<?php
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
$contact_bg = !empty($settings['contact_hero_image']) ? uploadUrl('settings', $settings['contact_hero_image']) : null;
?>

<!-- Breadcrumb Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $contact_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$contact_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">Contact Us</h1>
        <p class="lead opacity-75 mb-3">Get in touch with our admissions desk, administration, or campus office</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Contact Us</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-5">
        <!-- Contact details -->
        <div class="col-lg-5">
            <h3 class="fw-bold text-primary-color mb-4">Get In Touch</h3>
            <p class="text-muted mb-4">Have questions about admissions, fee structures, courses, or events? Fill out the contact form and our counselors will respond within 24 working hours.</p>
            
            <div class="d-flex flex-column gap-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 rounded-3 shadow-xs d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 50px; height: 50px; background: linear-gradient(135deg, #0d233a, #163654); border: 2px solid #bfa15f;">
                        <i class="fa-solid fa-location-dot fa-lg text-warning"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-primary-color mb-1">Campus Location</h6>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($col_addr); ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 rounded-3 shadow-xs d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 50px; height: 50px; background: linear-gradient(135deg, #0d233a, #163654); border: 2px solid #bfa15f;">
                        <i class="fa-solid fa-phone-volume fa-lg text-warning"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-primary-color mb-1">Contact Desk</h6>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($col_phone); ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 rounded-3 shadow-xs d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 50px; height: 50px; background: linear-gradient(135deg, #0d233a, #163654); border: 2px solid #bfa15f;">
                        <i class="fa-solid fa-envelope-open-text fa-lg text-warning"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-primary-color mb-1">Email Helpline</h6>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($col_email); ?></p>
                    </div>
                </div>
            </div>

            <!-- Official Social Media Channels -->
            <div class="mt-4 pt-4 border-top">
                <h6 class="fw-bold text-primary-color mb-3"><i class="fa-solid fa-share-nodes text-warning me-2"></i>Official Social Media Channels</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="https://www.linkedin.com/company/mgmu-school-of-engineering-and-technology/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-xs">
                        <i class="fa-brands fa-linkedin-in me-1 text-primary"></i> LinkedIn
                    </a>
                    <a href="https://www.instagram.com/mgmu.soet?igsh=MWcxZ3A2emMxZnE5ZA==" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-xs">
                        <i class="fa-brands fa-instagram me-1 text-danger"></i> Instagram
                    </a>
                    <a href="https://www.facebook.com/share/14hsTNNRa6q/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs">
                        <i class="fa-brands fa-facebook-f me-1 text-primary"></i> Facebook
                    </a>
                </div>
            </div>

            <!-- Official Google Maps Embed -->
            <div class="card border-0 shadow-sm mt-4 overflow-hidden position-relative rounded-3" style="height: 280px;">
                <iframe 
                    src="https://maps.google.com/maps?q=MGMU+School+of+Engineering+and+Technology,+CIDCO,+Chhatrapati+Sambhajinagar&t=&z=16&ie=UTF8&iwloc=&output=embed" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
                <div class="position-absolute bottom-0 end-0 p-2 z-1">
                    <a href="https://maps.app.goo.gl/NUzWW6mAcYVX5Tg96" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-dark bg-opacity-90 shadow-sm">
                        <i class="fa-solid fa-map-pin text-warning me-1"></i> Open in Google Maps
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-envelope-open-text text-warning me-2"></i>Send Message</h4>
                <form method="POST" action="contact.php" class="needs-validation" novalidate>
                    <?php echo Security::csrfField(); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Your Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Email Address *</label>
                            <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold">Subject *</label>
                            <input type="text" name="subject" class="form-control" placeholder="Query regarding admissions/fees" required>
                            <div class="invalid-feedback">Please enter a subject.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold">Message *</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Write details of your query here..." required></textarea>
                            <div class="invalid-feedback">Please write your message.</div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="submit_contact" class="btn btn-primary px-4 py-2"><i class="fa-solid fa-paper-plane me-1"></i> Send Query</button>
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
