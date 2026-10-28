<?php
/**
 * Public Portal Footer Component
 *
 * Renders global site footer with dynamic institution profile, social media links,
 * quick navigation links, active department links, campus contact info,
 * copyright notice, and real-time visitor statistics counter.
 */

$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$col_name = $settings['college_name'] ?? 'School of Engineering and Technology';
$col_phone = $settings['college_phone'] ?? '+91-9371714253 / 0240-6481000 (Ext. 2801)';
$col_email = $settings['college_email'] ?? 'admissionsoet@mgmu.ac.in';
$col_addr = $settings['college_address'] ?? 'School of Engineering & Technology, MGM Campus, N-6, CIDCO, Chhatrapati Sambhajinagar (Aurangabad) - 431003, Maharashtra, India';
$footer_about = $settings['footer_about_text'] ?? 'School of Engineering and Technology (SOET), MGM University is a premier engineering institution at Chhatrapati Sambhajinagar (Aurangabad) dedicated to academic excellence, state-of-the-art labs, and shaping engineering careers.';
$footer_copy = $settings['footer_copyright_text'] ?? 'School of Engineering and Technology (SOET), MGM University. All Rights Reserved.';

$footer_fb = $settings['social_facebook'] ?? 'https://www.facebook.com/share/14hsTNNRa6q/?mibextid=wwXIfr';
$footer_li = $settings['social_linkedin'] ?? 'https://www.linkedin.com/company/mgmu-school-of-engineering-and-technology/';
$footer_ig = $settings['social_instagram'] ?? 'https://www.instagram.com/mgmu.soet?igsh=MWcxZ3A2emMxZnE5ZA==';
$footer_tw = $settings['social_twitter'] ?? '';
$footer_yt = $settings['social_youtube'] ?? '';
$footer_wa = $settings['social_whatsapp'] ?? '';

// Fetch active departments for footer directory
$footer_depts = $cms->getDepartments(true);
$logo_src = !empty($settings['site_logo']) ? uploadUrl('settings', $settings['site_logo']) : APP_URL . '/assets/images/logo.svg';
?>
<footer class="main-footer">
    <div class="container">
        <div class="row g-4">
            <!-- About Section -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center mb-3 brand-highlight-link">
                    <div class="brand-logo-frame me-3">
                        <img src="<?php echo $logo_src; ?>" alt="MGM University SOET" class="brand-logo-img">
                    </div>
                    <div class="brand-text-block">
                        <span class="d-block text-uppercase lh-1 brand-acronym">SOET</span>
                        <span class="d-block text-white brand-college-name">MGM University</span>
                    </div>
                </div>
                <p class="mb-4 text-light opacity-90"><?php echo htmlspecialchars($footer_about); ?></p>
                <div class="social-links d-flex align-items-center gap-2 flex-wrap">
                    <?php if (!empty($footer_fb)): ?>
                        <a href="<?php echo htmlspecialchars($footer_fb); ?>" target="_blank" rel="noopener noreferrer" class="social-btn social-fb" title="Facebook" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($footer_li)): ?>
                        <a href="<?php echo htmlspecialchars($footer_li); ?>" target="_blank" rel="noopener noreferrer" class="social-btn social-li" title="LinkedIn" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($footer_ig)): ?>
                        <a href="<?php echo htmlspecialchars($footer_ig); ?>" target="_blank" rel="noopener noreferrer" class="social-btn social-ig" title="Instagram" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($footer_tw)): ?>
                        <a href="<?php echo htmlspecialchars($footer_tw); ?>" target="_blank" rel="noopener noreferrer" class="social-btn social-tw" title="Twitter / X" aria-label="Twitter / X"><i class="fa-brands fa-x-twitter"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($footer_yt)): ?>
                        <a href="<?php echo htmlspecialchars($footer_yt); ?>" target="_blank" rel="noopener noreferrer" class="social-btn social-yt" title="YouTube" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($footer_wa)): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $footer_wa); ?>" target="_blank" rel="noopener noreferrer" class="social-btn social-wa" title="WhatsApp Support" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5>Quick Links</h5>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="<?php echo APP_URL; ?>/index.php">Home</a></li>
                    <li><a href="<?php echo APP_URL; ?>/about.php">About Us</a></li>
                    <li><a href="<?php echo APP_URL; ?>/courses.php">Programs Offered</a></li>
                    <li><a href="<?php echo APP_URL; ?>/admissions.php">Admissions Portal</a></li>
                    <li><a href="<?php echo APP_URL; ?>/placements.php">Placements Cell</a></li>
                    <li><a href="<?php echo APP_URL; ?>/contact.php">Contact Us</a></li>
                </ul>
            </div>

            <!-- Departments -->
            <div class="col-lg-3 col-md-6">
                <h5>Departments</h5>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <?php if (!empty($footer_depts)): ?>
                        <?php foreach ($footer_depts as $fdept): ?>
                            <li><a href="<?php echo APP_URL; ?>/departments.php#dept-<?php echo $fdept['id']; ?>"><?php echo htmlspecialchars($fdept['name']); ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="<?php echo APP_URL; ?>/departments.php">Civil Engineering</a></li>
                        <li><a href="<?php echo APP_URL; ?>/departments.php">Computer Science &amp; Engineering</a></li>
                        <li><a href="<?php echo APP_URL; ?>/departments.php">Electrical Engineering</a></li>
                        <li><a href="<?php echo APP_URL; ?>/departments.php">Electronics &amp; Telecommunication Engineering</a></li>
                        <li><a href="<?php echo APP_URL; ?>/departments.php">Fire Service &amp; Industrial Safety Engineering</a></li>
                        <li><a href="<?php echo APP_URL; ?>/departments.php">Mechanical Engineering</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact info -->
            <div class="col-lg-3 col-md-6">
                <h5>Campus Contacts</h5>
                <p class="mb-2"><i class="fa-solid fa-location-dot text-warning me-2"></i> <?php echo htmlspecialchars($col_addr); ?></p>
                <p class="mb-2"><i class="fa-solid fa-phone text-warning me-2"></i> <a href="tel:<?php echo htmlspecialchars($col_phone); ?>" class="text-white text-decoration-none"><?php echo htmlspecialchars($col_phone); ?></a></p>
                <p class="mb-0"><i class="fa-solid fa-envelope text-warning me-2"></i> <a href="mailto:<?php echo htmlspecialchars($col_email); ?>" class="text-white text-decoration-none" title="Send email to <?php echo htmlspecialchars($col_email); ?>"><?php echo htmlspecialchars($col_email); ?></a></p>
            </div>
        </div>

<?php
$visitorTracker = new Visitor();
$footerStats = $visitorTracker->getFooterStats();
$totalVisitors = $footerStats['total_visitors'];
$todayVisitors = $footerStats['today_visitors'];
$totalViews = $footerStats['total_views'];
?>
        <div class="footer-bottom d-flex flex-column flex-md-row align-items-center justify-content-between pt-4 mt-4 border-top border-secondary border-opacity-25">
            <p class="mb-2 mb-md-0 text-white-50 small">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($col_name); ?>. All Rights Reserved.</p>
            
            <!-- Live Visitor Counter Badge -->
            <div class="visitor-counter-badge d-flex flex-wrap align-items-center justify-content-center gap-3 px-3 py-1.5 rounded-pill bg-dark border border-secondary border-opacity-50 text-white shadow-sm" style="font-size: 14px; background: rgba(15, 23, 42, 0.9) !important;">
                <span class="d-flex align-items-center gap-1.5 text-warning font-semibold">
                    <i class="fa-solid fa-users-viewfinder text-warning"></i> Unique Visitors: <strong class="text-white ms-1"><?php echo number_format($totalVisitors); ?></strong>
                </span>
                <span class="text-white-50 d-none d-sm-inline">|</span>
                <span class="d-flex align-items-center gap-1.5 text-info font-semibold">
                    <i class="fa-solid fa-eye text-info"></i> Page Views: <strong class="text-white ms-1"><?php echo number_format($totalViews); ?></strong>
                </span>
                <span class="text-white-50 d-none d-sm-inline">|</span>
                <span class="d-flex align-items-center gap-1.5 text-success font-semibold">
                    <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;" role="status"></span> Today: <strong class="text-white ms-1"><?php echo number_format($todayVisitors); ?></strong>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Back-to-Top Floating Scroll Button -->
<button id="backToTopBtn" class="btn btn-warning rounded-circle shadow" title="Back to top">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- Include WhatsApp Live Support UI overlay -->
<?php include_once __DIR__ . '/whatsapp-ui.php'; ?>

<!-- Include Chatbot UI overlay -->
<?php include_once __DIR__ . '/chatbot-ui.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo APP_URL; ?>/assets/js/main.js" defer></script>
</body>
</html>
