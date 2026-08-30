<?php
// Public Footer Component
$cms = new ContentManager();
$settings = $cms->getSiteSettings();
$col_name = $settings['college_name'] ?? 'School of Engineering and Technology';
$col_phone = $settings['college_phone'] ?? '+91-9371714253 / 0240-6481000 (Ext. 2801)';
$col_email = $settings['college_email'] ?? 'admissionsoet@mgmu.ac.in';
$col_addr = $settings['college_address'] ?? 'School of Engineering & Technology, MGM Campus, N-6, CIDCO, Chhatrapati Sambhajinagar (Aurangabad) - 431003, Maharashtra, India';
?>
<footer class="main-footer">
    <div class="container">
        <div class="row g-4">
            <!-- About Section -->
            <div class="col-lg-4 col-md-6">
                <h5 class="text-uppercase text-warning d-flex align-items-center mb-3">
                    <img src="<?php echo APP_URL; ?>/assets/images/logo.svg" alt="MGMU" class="me-2" style="height: 45px; object-fit: contain;">
                    <div>
                        <span class="d-block text-uppercase lh-1" style="font-size: 20px; font-weight: 800; color: var(--secondary-color); font-family: 'Outfit', sans-serif; letter-spacing: 0.5px;">SOET</span>
                        <span class="d-block text-white" style="font-size: 11px; font-weight: 700; font-family: 'Inter', sans-serif; margin-top: 2px; text-transform: none;">MGM University</span>
                    </div>
                </h5>
                <p class="mb-4">School of Engineering and Technology (SOET), MGM University is a premier engineering institution at Chhatrapati Sambhajinagar (Aurangabad) dedicated to academic excellence, state-of-the-art labs, and shaping engineering careers.</p>
                <div class="social-links d-flex gap-3">
                    <a href="https://www.facebook.com/share/14hsTNNRa6q/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light rounded-circle" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://www.linkedin.com/company/mgmu-school-of-engineering-and-technology/" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light rounded-circle" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="https://www.instagram.com/mgmu.soet?igsh=MWcxZ3A2emMxZnE5ZA==" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light rounded-circle" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5>Quick Links</h5>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="<?php echo APP_URL; ?>/index.php">Home</a></li>
                    <li><a href="<?php echo APP_URL; ?>/about.php">About Us</a></li>
                    <li><a href="<?php echo APP_URL; ?>/courses.php">Courses Offered</a></li>
                    <li><a href="<?php echo APP_URL; ?>/admissions.php">Admissions Portal</a></li>
                    <li><a href="<?php echo APP_URL; ?>/placements.php">Placements Cell</a></li>
                    <li><a href="<?php echo APP_URL; ?>/contact.php">Contact Us</a></li>
                </ul>
            </div>

            <!-- Departments -->
            <div class="col-lg-3 col-md-6">
                <h5>Departments</h5>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="<?php echo APP_URL; ?>/departments.php">Computer Science &amp; Eng.</a></li>
                    <li><a href="<?php echo APP_URL; ?>/departments.php">Electronics &amp; Comm. Eng.</a></li>
                    <li><a href="<?php echo APP_URL; ?>/departments.php">Mechanical Engineering</a></li>
                    <li><a href="<?php echo APP_URL; ?>/departments.php">Civil Engineering</a></li>
                </ul>
            </div>

            <!-- Contact info -->
            <div class="col-lg-3 col-md-6">
                <h5>Campus Contacts</h5>
                <p class="mb-2"><i class="fa-solid fa-location-dot text-warning me-2"></i> <?php echo htmlspecialchars($col_addr); ?></p>
                <p class="mb-2"><i class="fa-solid fa-phone text-warning me-2"></i> <?php echo htmlspecialchars($col_phone); ?></p>
                <p class="mb-0"><i class="fa-solid fa-envelope text-warning me-2"></i> <?php echo htmlspecialchars($col_email); ?></p>
            </div>
        </div>

<?php
$visitorTracker = new Visitor();
$totalVisitors = $visitorTracker->getTotalVisitors();
$todayVisitors = $visitorTracker->getTodayVisitors();
$totalViews = $visitorTracker->getTotalPageViews();
?>
        <div class="footer-bottom d-flex flex-column flex-md-row align-items-center justify-content-between pt-4 mt-4 border-top border-secondary border-opacity-25">
            <p class="mb-2 mb-md-0 text-white-50 small">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($col_name); ?>. All Rights Reserved.</p>
            
            <!-- Live Visitor Counter Badge -->
            <div class="visitor-counter-badge d-flex flex-wrap align-items-center justify-content-center gap-3 px-3 py-1.5 rounded-pill bg-dark border border-secondary border-opacity-50 text-white shadow-sm" style="font-size: 12px; background: rgba(15, 23, 42, 0.9) !important;">
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

<!-- Spotlight Command Palette Modal (Ctrl+K) -->
<div class="modal fade spotlight-modal" id="spotlightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px);">
            <div class="modal-header border-bottom border-secondary border-opacity-25 px-4 py-3">
                <div class="input-group input-group-lg border-0">
                    <span class="input-group-text bg-transparent border-0 text-warning ps-0"><i class="fa-solid fa-magnifying-glass fs-4"></i></span>
                    <input type="text" id="spotlightInput" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Type a course, faculty name, event, fee, or keyword..." autocomplete="off">
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" id="spotlightResults" style="max-height: 450px; overflow-y: auto;">
                <!-- Results rendered dynamically via JavaScript -->
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25 py-2 px-4 d-flex justify-content-between text-white-50 small">
                <span><kbd class="bg-dark text-white-50 border border-secondary border-opacity-50 px-1 rounded">ESC</kbd> to close</span>
                <span><kbd class="bg-dark text-white-50 border border-secondary border-opacity-50 px-1 rounded">↑</kbd> <kbd class="bg-dark text-white-50 border border-secondary border-opacity-50 px-1 rounded">↓</kbd> to navigate</span>
            </div>
        </div>
    </div>
</div>

<!-- Back-to-Top Floating Scroll Button -->
<button id="backToTopBtn" class="btn btn-warning rounded-circle shadow" title="Back to top">
    <i class="fa-solid fa-arrow-up text-dark"></i>
</button>

<!-- Include Chatbot UI overlay -->
<?php include_once __DIR__ . '/chatbot-ui.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
