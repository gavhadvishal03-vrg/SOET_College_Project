<?php
/**
 * Website Global Settings & CMS Content Manager (Admin Panel)
 *
 * Provides comprehensive content management control over the public portal, including:
 * 1. General & Institutional Identity: College name, affiliation, contact info, maps, footers.
 * 2. Branding & Assets: Favicons, logos, and custom hero banners across all public pages.
 * 3. Homepage & About Sections: Hero badges, welcome copy, vision/mission statements, leadership messages.
 * 4. Admission & Placement Statistics: Live admissions year, seat quotas, compensation LPA stats.
 * 5. Social & Communications: Social media handles and default chatbot greetings.
 *
 * @package SOET_College_Project
 * @subpackage Admin_Modules
 */

$page_title = "Website Settings & Content Manager";
include_once __DIR__ . '/../includes/header.php';

// Super Admin authorization check for system configuration
Auth::requirePermission('backup_restore');

$db = Database::getInstance();

// -------------------------------------------------------------------------
// POST: Save All Settings & Media Uploads
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all_settings'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        // List of all editable text setting keys across all tabs
        $keys = [
            // General & Institutional
            'college_name', 
            'college_short', 
            'college_tagline',
            'college_email', 
            'college_phone', 
            'college_address', 
            'university_name',
            'admission_website',
            'college_website',
            'maps_link',
            'google_maps_iframe',
            'footer_about_text',
            'footer_copyright_text',
            'chatbot_greeting',

            // Social Media
            'social_facebook',
            'social_instagram',
            'social_linkedin',
            'social_twitter',
            'social_youtube',
            'social_whatsapp',

            // Homepage Hero & Sections
            'home_hero_badge',
            'home_hero_badge_sub',
            'home_hero_title',
            'home_hero_subtitle',
            'home_hero_btn1_text',
            'home_hero_btn1_url',
            'home_hero_btn2_text',
            'home_hero_btn2_url',
            'home_welcome_badge',
            'home_welcome_title',
            'home_welcome_subtitle',
            'home_welcome_content',
            'home_stat1_val',
            'home_stat1_label',
            'home_stat2_val',
            'home_stat2_label',
            'home_stat3_val',
            'home_stat3_label',
            'home_highlight_badge',
            'home_highlight_title',
            'home_highlight_subtitle',
            'home_highlight1_title',
            'home_highlight1_sub',
            'home_highlight1_icon',
            'home_highlight2_title',
            'home_highlight2_sub',
            'home_highlight2_icon',
            'home_highlight3_title',
            'home_highlight3_sub',
            'home_highlight3_icon',
            'home_highlight4_title',
            'home_highlight4_sub',
            'home_highlight4_icon',

            // About SOET
            'about_hero_title',
            'about_hero_tagline',
            'about_hero_subtitle',
            'about_overview_lead',
            'about_overview_p1',
            'about_overview_p2',
            'about_overview_p3',
            'about_vision',
            'about_mission_1',
            'about_mission_2',
            'about_mission_3',
            'about_mission_4',
            'about_mgmu_title',
            'about_mgmu_subtitle',
            'about_mgmu_text',
            'about_mgmu_ugc',
            'established_year',
            'trust_legacy_years',

            // Admissions & Intake
            'admission_status',
            'admission_year',
            'total_intake_ay2026_27',
            'admission_instructions',

            // Placement Statistics
            'placements_stat1_val',
            'placements_stat1_label',
            'placements_stat2_val',
            'placements_stat2_label',
            'placements_stat3_val',
            'placements_stat3_label',
            'placements_stat4_val',
            'placements_stat4_label',

            // Director Profile
            'director_name',
            'director_designation',
            'director_message'
        ];

        // Upsert each posted key into the site_settings database table
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                $exists = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
                if ($exists) {
                    $db->update('site_settings', ['setting_value' => $val], 'setting_key = ?', [$key]);
                } else {
                    $db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $val]);
                }
            }
        }

        // Handle Image Uploads for branding and hero banners with validation
        $images = [
            'site_logo'              => 'site_logo_file',
            'site_favicon'           => 'site_favicon_file',
            'home_hero_image'        => 'home_hero',
            'home_welcome_image'     => 'home_welcome',
            'about_hero_image'       => 'about_hero',
            'director_image'         => 'director_photo',
            'departments_hero_image' => 'departments_hero',
            'courses_hero_image'     => 'courses_hero',
            'faculty_hero_image'     => 'faculty_hero',
            'admissions_hero_image'  => 'admissions_hero',
            'placements_hero_image'  => 'placements_hero',
            'events_hero_image'      => 'events_hero',
            'blogs_hero_image'       => 'blogs_hero',
            'news_hero_image'        => 'news_hero',
            'gallery_hero_image'     => 'gallery_hero',
            'contact_hero_image'     => 'contact_hero'
        ];

        $uploadedCount = 0;
        foreach ($images as $setting_key => $file_input) {
            if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] === UPLOAD_ERR_OK) {
                $file_errors = Security::validateUpload($_FILES[$file_input], ALLOWED_IMAGE_TYPES);
                if (empty($file_errors)) {
                    $filename = Security::uploadFile($_FILES[$file_input], 'settings', $file_input . '_');
                    if ($filename) {
                        $exists = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = ?", [$setting_key]);
                        if ($exists) {
                            $db->update('site_settings', ['setting_value' => $filename], 'setting_key = ?', [$setting_key]);
                        } else {
                            $db->insert('site_settings', ['setting_key' => $setting_key, 'setting_value' => $filename]);
                        }
                        $uploadedCount++;
                    }
                } else {
                    setFlash('danger', 'Error in ' . $file_input . ': ' . implode(' ', $file_errors));
                }
            }
        }

        $active_tab = $_POST['active_tab'] ?? 'general';
        setFlash('success', 'Website settings and content ' . ($uploadedCount > 0 ? "with {$uploadedCount} image(s)" : '') . ' saved successfully.');
        redirect('settings.php?tab=' . urlencode($active_tab));
    }
}

// Fetch all settings into associative key-value array
$settings_raw = $db->fetchAll("SELECT * FROM site_settings");
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$current_tab = $_GET['tab'] ?? 'general';
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Admin CMS Suite</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Website Settings &amp; Content Management</h2>
        <p class="text-muted small mb-0">Control all public portal text copy, hero banners, statistics, institutional information, and social links</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-semibold">
            <i class="fa-solid fa-check-double me-1 text-success"></i> Real-Time Synced
        </span>
    </div>
</div>

<form method="POST" action="settings.php" enctype="multipart/form-data" id="settingsForm">
    <?php echo Security::csrfField(); ?>
    <input type="hidden" name="active_tab" id="activeTabInput" value="<?php echo htmlspecialchars($current_tab); ?>">

    <div class="row g-4">
        <!-- Vertical Tab Navigation Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white sticky-top" style="top: 85px;">
                <div class="nav flex-column nav-pills gap-1.5" id="settingsNavPills" role="tablist">
                    <button class="nav-link text-start rounded-3 py-2.5 px-3 font-semibold small d-flex align-items-center gap-2.5 <?php echo $current_tab === 'general' ? 'active' : ''; ?>" id="tab-btn-general" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='general'">
                        <i class="fa-solid fa-building-columns text-primary" style="width: 18px;"></i>
                        <span>Institutional &amp; Contact</span>
                    </button>
                    <button class="nav-link text-start rounded-3 py-2.5 px-3 font-semibold small d-flex align-items-center gap-2.5 <?php echo $current_tab === 'home' ? 'active' : ''; ?>" id="tab-btn-home" data-bs-toggle="pill" data-bs-target="#tab-home" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='home'">
                        <i class="fa-solid fa-house-chimney text-warning" style="width: 18px;"></i>
                        <span>Homepage Content</span>
                    </button>
                    <button class="nav-link text-start rounded-3 py-2.5 px-3 font-semibold small d-flex align-items-center gap-2.5 <?php echo $current_tab === 'about' ? 'active' : ''; ?>" id="tab-btn-about" data-bs-toggle="pill" data-bs-target="#tab-about" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='about'">
                        <i class="fa-solid fa-circle-info text-info" style="width: 18px;"></i>
                        <span>About SOET &amp; Vision</span>
                    </button>
                    <button class="nav-link text-start rounded-3 py-2.5 px-3 font-semibold small d-flex align-items-center gap-2.5 <?php echo $current_tab === 'admissions' ? 'active' : ''; ?>" id="tab-btn-admissions" data-bs-toggle="pill" data-bs-target="#tab-admissions" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='admissions'">
                        <i class="fa-solid fa-id-card text-success" style="width: 18px;"></i>
                        <span>Admissions &amp; Intake</span>
                    </button>
                    <button class="nav-link text-start rounded-3 py-2.5 px-3 font-semibold small d-flex align-items-center gap-2.5 <?php echo $current_tab === 'placements' ? 'active' : ''; ?>" id="tab-btn-placements" data-bs-toggle="pill" data-bs-target="#tab-placements" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='placements'">
                        <i class="fa-solid fa-briefcase text-secondary" style="width: 18px;"></i>
                        <span>Placement Statistics</span>
                    </button>
                    <button class="nav-link text-start rounded-3 py-2.5 px-3 font-semibold small d-flex align-items-center gap-2.5 <?php echo $current_tab === 'director' ? 'active' : ''; ?>" id="tab-btn-director" data-bs-toggle="pill" data-bs-target="#tab-director" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='director'">
                        <i class="fa-solid fa-user-tie text-danger" style="width: 18px;"></i>
                        <span>Dean &amp; Leadership</span>
                    </button>
                    <button class="nav-link text-start rounded-3 py-2.5 px-3 font-semibold small d-flex align-items-center gap-2.5 <?php echo $current_tab === 'social' ? 'active' : ''; ?>" id="tab-btn-social" data-bs-toggle="pill" data-bs-target="#tab-social" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='social'">
                        <i class="fa-solid fa-share-nodes text-primary" style="width: 18px;"></i>
                        <span>Social &amp; Footer</span>
                    </button>
                    <button class="nav-link text-start rounded-3 py-2.5 px-3 font-semibold small d-flex align-items-center gap-2.5 <?php echo $current_tab === 'media' ? 'active' : ''; ?>" id="tab-btn-media" data-bs-toggle="pill" data-bs-target="#tab-media" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='media'">
                        <i class="fa-solid fa-images text-warning" style="width: 18px;"></i>
                        <span>Media &amp; Page Banners</span>
                    </button>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" name="save_all_settings" class="btn btn-primary rounded-pill w-100 py-2.5 fw-bold shadow-sm hover-lift">
                        <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Content Main Area -->
        <div class="col-lg-9">
            <div class="tab-content" id="settingsTabContent">
                
                <!-- TAB 1: Institutional & Contact -->
                <div class="tab-pane fade <?php echo $current_tab === 'general' ? 'show active' : ''; ?>" id="tab-general" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                            <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-building-columns"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Institutional Information &amp; Contacts</h5>
                                <small class="text-muted">Primary institution metadata, email, phone numbers, and physical location</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label font-semibold text-dark small">College Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="college_name" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['college_name'] ?? 'School of Engineering and Technology (SOET)'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Short Name / Code <span class="text-danger">*</span></label>
                                <input type="text" name="college_short" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['college_short'] ?? 'SOET MGM University'); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">University Name</label>
                                <input type="text" name="university_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['university_name'] ?? 'MGM University, Chhatrapati Sambhajinagar'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Institutional Tagline</label>
                                <input type="text" name="college_tagline" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['college_tagline'] ?? 'Where Technology Meets Tomorrow'); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Official Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="college_email" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['college_email'] ?? 'soet@mgmu.ac.in'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Official Contact Phone / Helpdesk <span class="text-danger">*</span></label>
                                <input type="text" name="college_phone" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['college_phone'] ?? '+91 9371714253'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Campus Location Address <span class="text-danger">*</span></label>
                                <textarea name="college_address" class="form-control rounded-3" rows="2" required><?php echo htmlspecialchars($settings['college_address'] ?? 'Mahatma Gandhi Mission University, MGM Campus, N-6 CIDCO, Chhatrapati Sambhajinagar (Aurangabad) - 431003, Maharashtra, India'); ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Admissions Portal Direct URL</label>
                                <input type="url" name="admission_website" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['admission_website'] ?? 'https://admissions.mgmu.ac.in'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Official College Web URL</label>
                                <input type="url" name="college_website" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['college_website'] ?? 'https://soet.mgmu.ac.in'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Google Maps Share / Navigation Link</label>
                                <input type="url" name="maps_link" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['maps_link'] ?? 'https://maps.app.goo.gl/NUzWW6mAcYVX5Tg96'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Google Maps Embed URL (for Contact page iframe)</label>
                                <input type="text" name="google_maps_iframe" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['google_maps_iframe'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3752.193234909787!2d75.35246731491374!3d19.873919986634502!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bdba299c8f00001%3A0x6b6bb7bc0b0d39e3!2sMGM%20University!5e0!3m2!1sen!2sin!4v1628000000000'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Chatbot Welcome Greeting Message</label>
                                <textarea name="chatbot_greeting" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['chatbot_greeting'] ?? 'Welcome to SOET College Assistant! I can help you with admissions, fee structure, course curriculums, eligibility, faculty, and campus placements. How can I assist you today?'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Homepage Content Editor -->
                <div class="tab-pane fade <?php echo $current_tab === 'home' ? 'show active' : ''; ?>" id="tab-home" role="tabpanel">
                    <!-- Hero Section -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-panorama"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Homepage Hero Banner Content</h5>
                                <small class="text-muted">Top header badges, titles, subtitles, and call-to-action buttons</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Hero Badge Title</label>
                                <input type="text" name="home_hero_badge" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_hero_badge'] ?? 'ADMISSIONS OPEN'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Hero Badge Sub-Text</label>
                                <input type="text" name="home_hero_badge_sub" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_hero_badge_sub'] ?? 'Academic Session 2026-27 • 380 Seats'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Hero Main Title (Heading 1) <span class="text-danger">*</span></label>
                                <input type="text" name="home_hero_title" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['home_hero_title'] ?? 'Where Technology Meets Tomorrow'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Hero Subtitle Paragraph</label>
                                <textarea name="home_hero_subtitle" class="form-control rounded-3" rows="3"><?php echo htmlspecialchars($settings['home_hero_subtitle'] ?? "You're not just studying engineering. You're engineering the future. Empowering innovators through cutting-edge laboratories, NEP 2020 aligned curricula, and a 42-year legacy of excellence under MGM Trust."); ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Primary Button (Button 1) Text</label>
                                <input type="text" name="home_hero_btn1_text" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_hero_btn1_text'] ?? 'Apply for AY 2026-27'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Primary Button (Button 1) Link URL</label>
                                <input type="text" name="home_hero_btn1_url" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_hero_btn1_url'] ?? 'admissions.php'); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Secondary Button (Button 2) Text</label>
                                <input type="text" name="home_hero_btn2_text" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_hero_btn2_text'] ?? 'Explore Programs & Booklets'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Secondary Button (Button 2) Link URL</label>
                                <input type="text" name="home_hero_btn2_url" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_hero_btn2_url'] ?? 'courses.php'); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Welcome & Statistics Section -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-info-subtle text-info rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Welcome Section &amp; Key Stats</h5>
                                <small class="text-muted">Welcome overview paragraph and 3 stat counters</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Welcome Badge</label>
                                <input type="text" name="home_welcome_badge" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_welcome_badge'] ?? 'MGM University • UGC 2(f) Recognized'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Welcome Heading</label>
                                <input type="text" name="home_welcome_title" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_welcome_title'] ?? 'Welcome to School of Engineering & Technology'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Welcome Subtitle</label>
                                <input type="text" name="home_welcome_subtitle" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_welcome_subtitle'] ?? 'Pioneering B.Tech (4-Year after HSC) & Integrated B.Tech (6-Year after SSC) Programs'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Welcome Comprehensive Paragraph</label>
                                <textarea name="home_welcome_content" class="form-control rounded-3" rows="4"><?php echo htmlspecialchars($settings['home_welcome_content'] ?? "Students at SOET MGM University have access to some of the most advanced facilities in the region. From building intelligent machines in the Industrial Robotics Lab to exploring sustainable mobility in the Electric Vehicle Lab, every space is designed to spark innovation and hands-on learning. Whether you're prototyping drones, designing Industry 4.0 systems, or launching your startup at the Innovation Incubation and Research Center, you'll find the tools and mentorship to go beyond the classroom."); ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Stat Counter 1 (Value)</label>
                                <input type="text" name="home_stat1_val" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_stat1_val'] ?? '42+ Yrs'); ?>">
                                <label class="form-label font-semibold text-muted small mt-1">Stat 1 Label</label>
                                <input type="text" name="home_stat1_label" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_stat1_label'] ?? 'MGM Trust Legacy'); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Stat Counter 2 (Value)</label>
                                <input type="text" name="home_stat2_val" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_stat2_val'] ?? '380'); ?>">
                                <label class="form-label font-semibold text-muted small mt-1">Stat 2 Label</label>
                                <input type="text" name="home_stat2_label" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_stat2_label'] ?? 'AY 2026-27 Intake'); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Stat Counter 3 (Value)</label>
                                <input type="text" name="home_stat3_val" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_stat3_val'] ?? '15+ MoUs'); ?>">
                                <label class="form-label font-semibold text-muted small mt-1">Stat 3 Label</label>
                                <input type="text" name="home_stat3_label" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_stat3_label'] ?? 'Industry Partners'); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Academic Highlights Cards -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Academic Highlights (4 Cards)</h5>
                                <small class="text-muted">Highlights grid rendered under the hero section</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Highlights Badge</label>
                                <input type="text" name="home_highlight_badge" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight_badge'] ?? 'ACADEMIC EXCELLENCE'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Highlights Heading</label>
                                <input type="text" name="home_highlight_title" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight_title'] ?? 'Future-Ready Education'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Highlights Subtitle</label>
                                <input type="text" name="home_highlight_subtitle" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight_subtitle'] ?? 'Empowering student engineers through innovation, research, and ethics.'); ?>">
                            </div>

                            <!-- Card 1 -->
                            <div class="col-md-6 p-3 bg-light rounded-3 border border-light-subtle">
                                <h6 class="fw-bold text-dark font-display mb-2">Card 1</h6>
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-0">Title</label>
                                    <input type="text" name="home_highlight1_title" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight1_title'] ?? 'NEP 2020 Aligned'); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-0">Subtitle</label>
                                    <input type="text" name="home_highlight1_sub" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight1_sub'] ?? 'Flexible Curriculum'); ?>">
                                </div>
                                <div>
                                    <label class="form-label small text-muted mb-0">FontAwesome Icon</label>
                                    <input type="text" name="home_highlight1_icon" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight1_icon'] ?? 'fa-solid fa-graduation-cap'); ?>">
                                </div>
                            </div>

                            <!-- Card 2 -->
                            <div class="col-md-6 p-3 bg-light rounded-3 border border-light-subtle">
                                <h6 class="fw-bold text-dark font-display mb-2">Card 2</h6>
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-0">Title</label>
                                    <input type="text" name="home_highlight2_title" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight2_title'] ?? 'Project-Based (PBL)'); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-0">Subtitle</label>
                                    <input type="text" name="home_highlight2_sub" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight2_sub'] ?? 'Experiential Learning'); ?>">
                                </div>
                                <div>
                                    <label class="form-label small text-muted mb-0">FontAwesome Icon</label>
                                    <input type="text" name="home_highlight2_icon" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight2_icon'] ?? 'fa-solid fa-diagram-project'); ?>">
                                </div>
                            </div>

                            <!-- Card 3 -->
                            <div class="col-md-6 p-3 bg-light rounded-3 border border-light-subtle">
                                <h6 class="fw-bold text-dark font-display mb-2">Card 3</h6>
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-0">Title</label>
                                    <input type="text" name="home_highlight3_title" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight3_title'] ?? 'Robotics & EV Labs'); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-0">Subtitle</label>
                                    <input type="text" name="home_highlight3_sub" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight3_sub'] ?? 'Mechatronics 4.0'); ?>">
                                </div>
                                <div>
                                    <label class="form-label small text-muted mb-0">FontAwesome Icon</label>
                                    <input type="text" name="home_highlight3_icon" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight3_icon'] ?? 'fa-solid fa-robot'); ?>">
                                </div>
                            </div>

                            <!-- Card 4 -->
                            <div class="col-md-6 p-3 bg-light rounded-3 border border-light-subtle">
                                <h6 class="fw-bold text-dark font-display mb-2">Card 4</h6>
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-0">Title</label>
                                    <input type="text" name="home_highlight4_title" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight4_title'] ?? 'Startup & Incubation'); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-0">Subtitle</label>
                                    <input type="text" name="home_highlight4_sub" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight4_sub'] ?? 'IIRC Research Center'); ?>">
                                </div>
                                <div>
                                    <label class="form-label small text-muted mb-0">FontAwesome Icon</label>
                                    <input type="text" name="home_highlight4_icon" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['home_highlight4_icon'] ?? 'fa-solid fa-rocket'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: About SOET & Vision -->
                <div class="tab-pane fade <?php echo $current_tab === 'about' ? 'show active' : ''; ?>" id="tab-about" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-info-subtle text-info rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">About SOET Overview &amp; Narrative</h5>
                                <small class="text-muted">Header titles, 3 overview paragraphs, and legacy details</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">About Page Hero Title</label>
                                <input type="text" name="about_hero_title" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['about_hero_title'] ?? 'About SOET'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">About Page Tagline</label>
                                <input type="text" name="about_hero_tagline" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['about_hero_tagline'] ?? 'Where Technology Meets Tomorrow'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">About Page Hero Subtitle</label>
                                <textarea name="about_hero_subtitle" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['about_hero_subtitle'] ?? 'Empowering students to engineer the future through cutting-edge laboratories, future-ready curricula, and a 42-year legacy of educational innovation.'); ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Overview Lead Heading</label>
                                <input type="text" name="about_overview_lead" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['about_overview_lead'] ?? "You're not just studying engineering. You're engineering the future."); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Overview Paragraph 1 (Integrated B.Tech Advantage)</label>
                                <textarea name="about_overview_p1" class="form-control rounded-3" rows="4"><?php echo htmlspecialchars($settings['about_overview_p1'] ?? "The School of Engineering and Technology (SOET) at MGM University is a pioneer in offering Integrated Six-Year B.Tech. Programs (after SSC / Class 10) and Four-Year B.Tech. Programs (after HSC / Class 12) in the Marathwada region. The Integrated program allows students to begin their engineering education right after Class 10, offering an early advantage and extended time for hands-on learning, innovation, and deep exploration of engineering fundamentals. This structure empowers young aspirants to build a strong foundation and stay ahead in the competitive landscape. SOET's integrated curriculum is designed to nurture curiosity, develop critical thinking, and prepare students for advanced industry demands from an early stage."); ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Overview Paragraph 2 (4-Year Professional B.Tech)</label>
                                <textarea name="about_overview_p2" class="form-control rounded-3" rows="4"><?php echo htmlspecialchars($settings['about_overview_p2'] ?? "At the same time, SOET offers focused Four-Year B.Tech. programs after Class 12, catering to students who aspire to pursue professional engineering education immediately after HSC. These programs are aligned with the latest industry trends and include emerging domains such as Computer Science and Engineering (IoT, Cyber Security & Blockchain), Cyber Security, Electronics Engineering (VLSI Design & Technology), Robotics & Automation, and Electrical Engineering. The teaching and learning process is Project-Based backed by Research, and is powered by Collaborative methods."); ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Overview Paragraph 3 (Placements & MGM Trust Legacy)</label>
                                <textarea name="about_overview_p3" class="form-control rounded-3" rows="3"><?php echo htmlspecialchars($settings['about_overview_p3'] ?? "Students benefit from internship and placement opportunities through active collaborations with industries. With a legacy of over 42 years of MGM Trust, SOET is committed to producing future-ready engineers through holistic education, modern infrastructure, and a strong emphasis on innovation and ethics."); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Vision & Mission -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-success-subtle text-success rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-bullseye"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Vision &amp; Mission Statements</h5>
                                <small class="text-muted">Core guiding principles of the institution</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Vision Statement <span class="text-danger">*</span></label>
                                <textarea name="about_vision" class="form-control rounded-3" rows="3" required><?php echo htmlspecialchars($settings['about_vision'] ?? 'To empower students as leaders of tomorrow by fostering a culture of innovation, skill development, and adaptability, bridging the gap between academic excellence and industry readiness in an ever-evolving global economy.'); ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Mission Point 1</label>
                                <textarea name="about_mission_1" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['about_mission_1'] ?? 'To cultivate future-ready professionals through transformative training and experiential learning.'); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Mission Point 2</label>
                                <textarea name="about_mission_2" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['about_mission_2'] ?? 'To inspire students to embrace emerging trends and technologies, building versatile and sustainable career paths.'); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Mission Point 3</label>
                                <textarea name="about_mission_3" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['about_mission_3'] ?? 'To provide personalized career mentorship and foster meaningful collaborations with leading industries.'); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Mission Point 4</label>
                                <textarea name="about_mission_4" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['about_mission_4'] ?? 'To create an ecosystem that nurtures talent, values diversity, and encourages lifelong learning for global success.'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- MGM University Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-landmark"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">MGM University Overview</h5>
                                <small class="text-muted">University act, UGC status, and foundation years</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Card Title</label>
                                <input type="text" name="about_mgmu_title" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['about_mgmu_title'] ?? 'About MGM University'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Card Subtitle / Legacy</label>
                                <input type="text" name="about_mgmu_subtitle" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['about_mgmu_subtitle'] ?? '42+ Years of Educational Legacy'); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">University Foundation Summary</label>
                                <textarea name="about_mgmu_text" class="form-control rounded-3" rows="3"><?php echo htmlspecialchars($settings['about_mgmu_text'] ?? 'MGM University, established by the revered Mahatma Gandhi Mission Trust, a pioneer with four decades of remarkable legacy in education, research, and service, is a State Private University, approved by Government of Maharashtra MGM University Act No. XXVI of 2019.'); ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">UGC Recognition Statement</label>
                                <input type="text" name="about_mgmu_ugc" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['about_mgmu_ugc'] ?? 'Granted by University Grants Commission (UGC) of India Under Section 2(f).'); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">MGM Trust Established Year</label>
                                <input type="text" name="established_year" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['established_year'] ?? '1982'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Trust Legacy Years Count</label>
                                <input type="text" name="trust_legacy_years" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['trust_legacy_years'] ?? '42+'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: Admissions & Intake -->
                <div class="tab-pane fade <?php echo $current_tab === 'admissions' ? 'show active' : ''; ?>" id="tab-admissions" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-success-subtle text-success rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-id-card"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Admissions Session &amp; Total Intake Settings</h5>
                                <small class="text-muted">Control application status, academic year, and eligibility summary</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Admissions Status</label>
                                <select name="admission_status" class="form-select rounded-3">
                                    <option value="open" <?php echo ($settings['admission_status'] ?? 'open') === 'open' ? 'selected' : ''; ?>>Open (Accepting Applications)</option>
                                    <option value="closed" <?php echo ($settings['admission_status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed (Admissions Finished)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Academic Session Year</label>
                                <input type="text" name="admission_year" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['admission_year'] ?? '2026-27'); ?>" placeholder="e.g. 2026-27">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold text-dark small">Total Approved Seats Intake</label>
                                <input type="text" name="total_intake_ay2026_27" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['total_intake_ay2026_27'] ?? '380'); ?>" placeholder="e.g. 380">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Admission Instructions &amp; Eligibility Guidelines</label>
                                <textarea name="admission_instructions" class="form-control rounded-3" rows="4"><?php echo htmlspecialchars($settings['admission_instructions'] ?? 'UG 4-Year B.Tech: HSC Passed (min 45% Open, min 40% Reserved) + Non-zero score in JEE(Mains)/MHT-CET/PERA-CET/MGMU-CET. Lateral Entry: Diploma / B.Sc. / D.Voc. | B.Tech Integrated (6 Years): SSC Passed (min 50% Open, min 45% Reserved) + MGMU-CET.'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: Placement Statistics -->
                <div class="tab-pane fade <?php echo $current_tab === 'placements' ? 'show active' : ''; ?>" id="tab-placements" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-secondary-subtle text-secondary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-chart-simple"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Placements Page Statistics (4 Counters)</h5>
                                <small class="text-muted">Headline numbers displayed on the placements portal and hero counters</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                    <label class="form-label font-semibold text-dark small">Placement Counter 1 (Value)</label>
                                    <input type="text" name="placements_stat1_val" class="form-control rounded-3 mb-2" value="<?php echo htmlspecialchars($settings['placements_stat1_val'] ?? '12+'); ?>">
                                    <label class="form-label font-semibold text-muted small">Counter 1 Label</label>
                                    <input type="text" name="placements_stat1_label" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['placements_stat1_label'] ?? '2026 Batch Placed'); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                    <label class="form-label font-semibold text-dark small">Placement Counter 2 (Value)</label>
                                    <input type="text" name="placements_stat2_val" class="form-control rounded-3 mb-2" value="<?php echo htmlspecialchars($settings['placements_stat2_val'] ?? '7+'); ?>">
                                    <label class="form-label font-semibold text-muted small">Counter 2 Label</label>
                                    <input type="text" name="placements_stat2_label" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['placements_stat2_label'] ?? 'Active Recruiters (2026)'); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                    <label class="form-label font-semibold text-dark small">Placement Counter 3 (Value)</label>
                                    <input type="text" name="placements_stat3_val" class="form-control rounded-3 mb-2" value="<?php echo htmlspecialchars($settings['placements_stat3_val'] ?? '15+'); ?>">
                                    <label class="form-label font-semibold text-muted small">Counter 3 Label</label>
                                    <input type="text" name="placements_stat3_label" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['placements_stat3_label'] ?? 'Industry MoUs & Partners'); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                    <label class="form-label font-semibold text-dark small">Placement Counter 4 (Value)</label>
                                    <input type="text" name="placements_stat4_val" class="form-control rounded-3 mb-2" value="<?php echo htmlspecialchars($settings['placements_stat4_val'] ?? '100%'); ?>">
                                    <label class="form-label font-semibold text-muted small">Counter 4 Label</label>
                                    <input type="text" name="placements_stat4_label" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($settings['placements_stat4_label'] ?? 'Verified Placement Track'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: Dean & Director Profile -->
                <div class="tab-pane fade <?php echo $current_tab === 'director' ? 'show active' : ''; ?>" id="tab-director" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-danger-subtle text-danger rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Dean &amp; Director Leadership Profile</h5>
                                <small class="text-muted">Executive leadership quote, name, and photograph</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Director / Dean Profile Photo</label>
                                <input type="file" name="director_photo" class="form-control rounded-3 mb-2" accept="image/*">
                                <?php if (!empty($settings['director_image'])): ?>
                                    <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-3 border border-light-subtle mb-2">
                                        <img src="<?php echo uploadUrl('settings', $settings['director_image']); ?>" alt="Director Photo" class="img-thumbnail rounded-circle shadow-xs" style="width: 54px; height: 54px; object-fit: cover;">
                                        <div>
                                            <div class="text-dark fw-semibold small">Active Leadership Photograph</div>
                                            <small class="text-muted" style="font-size: 14px;">Displayed on About Us leadership card</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Director Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="director_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['director_name'] ?? 'Dr. Parminder Kaur Dhingra'); ?>" placeholder="e.g. Dr. Parminder Kaur Dhingra" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small">Director Designation <span class="text-danger">*</span></label>
                                <input type="text" name="director_designation" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['director_designation'] ?? 'Dean & Director, SOET MGM University'); ?>" placeholder="e.g. Dean & Director, SOET MGM University" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Director Welcome Quote / Leadership Message <span class="text-danger">*</span></label>
                                <textarea name="director_message" class="form-control rounded-3" rows="4" placeholder="Enter Director welcome quote..." required><?php echo htmlspecialchars($settings['director_message'] ?? 'At SOET MGM University, we are committed to providing top-tier technological education with modern laboratories, expert faculty, and high-paying industry placements. Our mission is to produce innovative, ethically driven engineering professionals.'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 7: Social & Footer -->
                <div class="tab-pane fade <?php echo $current_tab === 'social' ? 'show active' : ''; ?>" id="tab-social" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-share-nodes"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Social Media Links &amp; Global Footer Copy</h5>
                                <small class="text-muted">Official social channels and global footer copyright texts</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small"><i class="fa-brands fa-facebook text-primary me-1"></i> Facebook Profile URL</label>
                                <input type="url" name="social_facebook" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? 'https://www.facebook.com/share/14hsTNNRa6q/?mibextid=wwXIfr'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small"><i class="fa-brands fa-instagram text-danger me-1"></i> Instagram Profile URL</label>
                                <input type="url" name="social_instagram" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? 'https://www.instagram.com/mgmu.soet?igsh=MWcxZ3A2emMxZnE5ZA=='); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small"><i class="fa-brands fa-linkedin text-info me-1"></i> LinkedIn Page URL</label>
                                <input type="url" name="social_linkedin" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? 'https://www.linkedin.com/company/mgmu-school-of-engineering-and-technology/'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small"><i class="fa-brands fa-x-twitter text-dark me-1"></i> Twitter / X Profile URL</label>
                                <input type="url" name="social_twitter" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small"><i class="fa-brands fa-youtube text-danger me-1"></i> YouTube Channel URL</label>
                                <input type="url" name="social_youtube" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['social_youtube'] ?? ''); ?>" placeholder="https://youtube.com/...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-dark small"><i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp Support Number / Helpdesk</label>
                                <input type="text" name="social_whatsapp" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['social_whatsapp'] ?? '+919371714253'); ?>" placeholder="+919371714253">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Footer About Summary Text</label>
                                <textarea name="footer_about_text" class="form-control rounded-3" rows="3"><?php echo htmlspecialchars($settings['footer_about_text'] ?? 'School of Engineering and Technology (SOET), MGM University is a premier engineering institution at Chhatrapati Sambhajinagar (Aurangabad) dedicated to academic excellence, state-of-the-art labs, and shaping engineering careers.'); ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label font-semibold text-dark small">Footer Copyright Statement</label>
                                <input type="text" name="footer_copyright_text" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['footer_copyright_text'] ?? 'School of Engineering and Technology (SOET), MGM University. All Rights Reserved.'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 8: Media & Page Banners -->
                <div class="tab-pane fade <?php echo $current_tab === 'media' ? 'show active' : ''; ?>" id="tab-media" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                            <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-images"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 font-display">Branding Assets &amp; Global Page Banners</h5>
                                <small class="text-muted">Logos, favicons, homepage backgrounds, and page hero banners</small>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Logo & Favicon -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                                    <label class="form-label font-semibold text-dark small"><i class="fa-solid fa-shield-halved me-1 text-primary"></i> Site Navbar Logo</label>
                                    <input type="file" name="site_logo_file" class="form-control rounded-3 mb-2" accept="image/*">
                                    <?php if (!empty($settings['site_logo'])): ?>
                                        <div class="text-center p-2.5 bg-white rounded-3 border border-light-subtle">
                                            <img src="<?php echo uploadUrl('settings', $settings['site_logo']); ?>" alt="Navbar Logo" style="max-height: 48px; object-fit: contain;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                                    <label class="form-label font-semibold text-dark small"><i class="fa-solid fa-globe me-1 text-primary"></i> Favicon Icon</label>
                                    <input type="file" name="site_favicon_file" class="form-control rounded-3 mb-2" accept="image/*">
                                    <?php if (!empty($settings['site_favicon'])): ?>
                                        <div class="text-center p-2.5 bg-white rounded-3 border border-light-subtle">
                                            <img src="<?php echo uploadUrl('settings', $settings['site_favicon']); ?>" alt="Favicon" style="max-height: 32px; object-fit: contain;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Homepage Hero Background -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                                    <label class="form-label font-semibold text-dark small"><i class="fa-solid fa-panorama me-1 text-primary"></i> Homepage Hero Background</label>
                                    <input type="file" name="home_hero" class="form-control rounded-3 mb-2" accept="image/*">
                                    <?php if (!empty($settings['home_hero_image'])): ?>
                                        <div class="text-center p-2.5 bg-white rounded-3 border border-light-subtle">
                                            <img src="<?php echo uploadUrl('settings', $settings['home_hero_image']); ?>" alt="Home Hero" class="img-fluid rounded-3 shadow-xs" style="max-height: 80px; object-fit: cover;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Homepage Welcome Image -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                                    <label class="form-label font-semibold text-dark small"><i class="fa-solid fa-building-columns me-1 text-primary"></i> Homepage Welcome Side Image</label>
                                    <input type="file" name="home_welcome" class="form-control rounded-3 mb-2" accept="image/*">
                                    <?php if (!empty($settings['home_welcome_image'])): ?>
                                        <div class="text-center p-2.5 bg-white rounded-3 border border-light-subtle">
                                            <img src="<?php echo uploadUrl('settings', $settings['home_welcome_image']); ?>" alt="Home Welcome" class="img-fluid rounded-3 shadow-xs" style="max-height: 80px; object-fit: cover;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Page Hero Banners Grid -->
                            <div class="col-12">
                                <h6 class="fw-bold text-dark font-display mb-3 border-bottom pb-2">Page Header Banners</h6>
                                <div class="row g-3">
                                    <!-- About Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">About Us Page Banner</label>
                                            <input type="file" name="about_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['about_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['about_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Departments Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">Departments Page Banner</label>
                                            <input type="file" name="departments_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['departments_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['departments_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Courses Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">Courses Page Banner</label>
                                            <input type="file" name="courses_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['courses_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['courses_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Faculty Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">Faculty Page Banner</label>
                                            <input type="file" name="faculty_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['faculty_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['faculty_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Admissions Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">Admissions Page Banner</label>
                                            <input type="file" name="admissions_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['admissions_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['admissions_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Placements Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">Placements Page Banner</label>
                                            <input type="file" name="placements_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['placements_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['placements_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Events Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">Events Page Banner</label>
                                            <input type="file" name="events_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['events_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['events_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Gallery Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">Gallery Page Banner</label>
                                            <input type="file" name="gallery_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['gallery_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['gallery_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Contact Hero -->
                                    <div class="col-md-4">
                                        <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                            <label class="form-label font-semibold text-dark small mb-1">Contact Page Banner</label>
                                            <input type="file" name="contact_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                            <?php if (!empty($settings['contact_hero_image'])): ?>
                                                <img src="<?php echo uploadUrl('settings', $settings['contact_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 48px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Sticky Save Bar -->
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-row justify-content-between align-items-center">
                <span class="text-muted small">Make sure to save before navigating away to ensure all changes persist.</span>
                <button type="submit" name="save_all_settings" class="btn btn-primary rounded-pill px-4 py-2 font-semibold shadow-sm hover-lift">
                    <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Settings
                </button>
            </div>
        </div>
    </div>
</form>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
