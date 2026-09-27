<?php
$page_title = "Website Settings & Comprehensive Image Manager";
include_once __DIR__ . '/../includes/header.php';

Auth::requirePermission('backup_restore'); // Super Admins only

$db = Database::getInstance();

// Handle General Settings & Banners update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        // Update text settings with UPSERT
        $keys = [
            'college_name', 
            'college_short', 
            'college_email', 
            'college_phone', 
            'college_address', 
            'admission_status', 
            'admission_instructions', 
            'chatbot_greeting',
            'director_name',
            'director_designation',
            'director_message'
        ];

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

        // Handle Image Uploads for all site banners and module headers
        $images = [
            'site_logo'              => 'site_logo_file',
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
                // Validate uploaded image
                $file_errors = Security::validateUpload($_FILES[$file_input], ALLOWED_IMAGE_TYPES);
                if (empty($file_errors)) {
                    // Upload file to settings directory
                    $filename = Security::uploadFile($_FILES[$file_input], 'settings', $file_input . '_');
                    if ($filename) {
                        // Check if key exists in site_settings
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

        setFlash('success', 'Website settings and ' . ($uploadedCount > 0 ? $uploadedCount . ' image(s)' : 'configurations') . ' updated successfully.');
        redirect('settings.php');
    }
}

// Handle Dedicated Director Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_director_profile'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $director_keys = ['director_name', 'director_designation', 'director_message'];
        foreach ($director_keys as $key) {
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

        if (isset($_FILES['director_photo']) && $_FILES['director_photo']['error'] === UPLOAD_ERR_OK) {
            $file_errors = Security::validateUpload($_FILES['director_photo'], ALLOWED_IMAGE_TYPES);
            if (empty($file_errors)) {
                $filename = Security::uploadFile($_FILES['director_photo'], 'settings', 'director_photo_');
                if ($filename) {
                    $exists = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = 'director_image'");
                    if ($exists) {
                        $db->update('site_settings', ['setting_value' => $filename], "setting_key = 'director_image'");
                    } else {
                        $db->insert('site_settings', ['setting_key' => 'director_image', 'setting_value' => $filename]);
                    }
                }
            } else {
                setFlash('danger', 'Error in director photo: ' . implode(' ', $file_errors));
            }
        }

        setFlash('success', 'Dean & Director Leadership Profile updated successfully.');
        redirect('settings.php');
    }
}

// Fetch all settings
$settings_raw = $db->fetchAll("SELECT * FROM site_settings");
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">Site Configuration</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Website Settings &amp; Media</h2>
        <p class="text-muted small mb-0">Upload and update website hero banners, branding logos, and page headers dynamically</p>
    </div>
    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2 fw-semibold">
        <i class="fa-solid fa-gears me-1 text-warning"></i> Global Configuration
    </span>
</div>

<div class="row g-4">
    <!-- Text & Contact Information -->
    <div class="col-lg-6">
        <!-- Form 1: General Institutional Info -->
        <form method="POST" action="settings.php">
            <?php echo Security::csrfField(); ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                    <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-0 font-display">Institutional Information</h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label font-semibold text-dark small">College Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="college_name" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['college_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-semibold text-dark small">Short Code <span class="text-danger">*</span></label>
                        <input type="text" name="college_short" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['college_short'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label font-semibold text-dark small">Institutional Email <span class="text-danger">*</span></label>
                        <input type="email" name="college_email" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['college_email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-semibold text-dark small">Helpdesk Phone <span class="text-danger">*</span></label>
                        <input type="text" name="college_phone" class="form-control rounded-3" required value="<?php echo htmlspecialchars($settings['college_phone'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label font-semibold text-dark small">Campus Location Address <span class="text-danger">*</span></label>
                        <textarea name="college_address" class="form-control rounded-3" rows="2" required><?php echo htmlspecialchars($settings['college_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold text-dark small">Admissions Status</label>
                        <select name="admission_status" class="form-select rounded-3">
                            <option value="open" <?php echo ($settings['admission_status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open (Accepting Applications)</option>
                            <option value="closed" <?php echo ($settings['admission_status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed (Admissions Finished)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-semibold text-dark small">Admission Instructions</label>
                        <textarea name="admission_instructions" class="form-control rounded-3" rows="3"><?php echo htmlspecialchars($settings['admission_instructions'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-semibold text-dark small">Chatbot Welcome Greeting</label>
                        <textarea name="chatbot_greeting" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['chatbot_greeting'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-3 pt-2">
                        <button type="submit" name="save_settings" class="btn btn-primary rounded-pill w-100 py-2.5 font-semibold shadow-sm hover-lift">
                            <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Institutional Info
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form 2: Dean & Director Leadership Profile -->
        <form method="POST" action="settings.php" enctype="multipart/form-data">
            <?php echo Security::csrfField(); ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                    <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-0 font-display">Dean &amp; Director Profile</h5>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-semibold text-dark small">Director / Dean Profile Photo</label>
                        <input type="file" name="director_photo" class="form-control rounded-3 mb-2" accept="image/*">
                        <?php if (!empty($settings['director_image'])): ?>
                            <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-3 border border-light-subtle mb-2">
                                <img src="<?php echo uploadUrl('settings', $settings['director_image']); ?>" alt="Director Photo" class="img-thumbnail rounded-circle shadow-xs" style="width: 54px; height: 54px; object-fit: cover;">
                                <div>
                                    <div class="text-dark fw-semibold small">Current Photo Active</div>
                                    <small class="text-muted" style="font-size: 11px;">Displayed on About &amp; Leadership section</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-semibold text-dark small">Director Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="director_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['director_name'] ?? 'Dr. Rajesh Kumar Sen'); ?>" placeholder="e.g. Dr. Rajesh Kumar Sen" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-semibold text-dark small">Director Designation <span class="text-danger">*</span></label>
                        <input type="text" name="director_designation" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['director_designation'] ?? 'Dean & Director, SOET'); ?>" placeholder="e.g. Dean & Director, SOET" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label font-semibold text-dark small">Director Welcome Quote / Message <span class="text-danger">*</span></label>
                        <textarea name="director_message" class="form-control rounded-3" rows="4" placeholder="Enter Director welcome quote..." required><?php echo htmlspecialchars($settings['director_message'] ?? "SOET is not just a college; it's an ecosystem of tech innovation. We welcome you to experience an education that shapes you into a global leader. We continuously push the boundaries of classroom training to bring hands-on project excellence to our engineering departments."); ?></textarea>
                    </div>
                    <div class="col-12 mt-3 pt-2">
                        <button type="submit" name="save_director_profile" class="btn btn-warning text-dark fw-bold rounded-pill w-100 py-2.5 shadow-sm hover-lift">
                            <i class="fa-solid fa-user-check me-1.5"></i> Save Director Profile
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Form 3: Global Site & Page Banner Images Uploads -->
    <div class="col-lg-6">
        <form method="POST" action="settings.php" enctype="multipart/form-data">
            <?php echo Security::csrfField(); ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3">
                    <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-images"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-0 font-display">Global Branding &amp; Page Banners</h5>
                </div>
                
                <ul class="nav nav-pills mb-3 gap-1 bg-light p-1.5 rounded-pill" id="bannerTabs" role="tablist">
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link active py-2 px-3 rounded-pill font-semibold small text-center w-100" id="home-tab" data-bs-toggle="pill" data-bs-target="#home-banners" type="button" role="tab">Homepage &amp; Logo</button>
                    </li>
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link py-2 px-3 rounded-pill font-semibold small text-center w-100" id="headers-tab" data-bs-toggle="pill" data-bs-target="#headers-banners" type="button" role="tab">Page Banners</button>
                    </li>
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link py-2 px-3 rounded-pill font-semibold small text-center w-100" id="about-tab" data-bs-toggle="pill" data-bs-target="#about-banners" type="button" role="tab">About Banner</button>
                    </li>
                </ul>

                <div class="tab-content pt-2" id="bannerTabsContent">
                    <!-- Tab 1: Homepage & Logo -->
                    <div class="tab-pane fade show active" id="home-banners" role="tabpanel">
                        <!-- Site Logo -->
                        <div class="mb-3 p-3.5 bg-light rounded-3 border border-light-subtle">
                            <label class="form-label font-semibold small text-dark"><i class="fa-solid fa-shield-halved me-1 text-primary"></i> Custom Navbar Logo</label>
                            <input type="file" name="site_logo_file" class="form-control rounded-3 mb-2" accept="image/*">
                            <?php if (!empty($settings['site_logo'])): ?>
                                <div class="text-center p-2.5 bg-white rounded-3 border border-light-subtle">
                                    <img src="<?php echo uploadUrl('settings', $settings['site_logo']); ?>" alt="Navbar Logo" style="max-height: 50px; object-fit: contain;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Home Hero Banner -->
                        <div class="mb-3 p-3.5 bg-light rounded-3 border border-light-subtle">
                            <label class="form-label font-semibold small text-dark"><i class="fa-solid fa-panorama me-1 text-primary"></i> Homepage Hero Slider Background</label>
                            <input type="file" name="home_hero" class="form-control rounded-3 mb-2" accept="image/*">
                            <small class="text-muted d-block mb-2" style="font-size: 11px;">Main banner displayed on homepage hero section (1600x800px).</small>
                            <?php if (!empty($settings['home_hero_image'])): ?>
                                <div class="text-center p-2.5 bg-white rounded-3 border border-light-subtle">
                                    <img src="<?php echo uploadUrl('settings', $settings['home_hero_image']); ?>" alt="Home Hero" class="img-fluid rounded-3 shadow-xs" style="max-height: 100px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Home Welcome Side Image -->
                        <div class="mb-3 p-3.5 bg-light rounded-3 border border-light-subtle">
                            <label class="form-label font-semibold small text-dark"><i class="fa-solid fa-building-columns me-1 text-primary"></i> Homepage Welcome Side Image</label>
                            <input type="file" name="home_welcome" class="form-control rounded-3 mb-2" accept="image/*">
                            <?php if (!empty($settings['home_welcome_image'])): ?>
                                <div class="text-center p-2.5 bg-white rounded-3 border border-light-subtle">
                                    <img src="<?php echo uploadUrl('settings', $settings['home_welcome_image']); ?>" alt="Home Welcome" class="img-fluid rounded-3 shadow-xs" style="max-height: 100px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tab 2: Page Header Banners -->
                    <div class="tab-pane fade" id="headers-banners" role="tabpanel">
                        <div class="row g-3" style="max-height: 380px; overflow-y: auto;">
                            <!-- Departments Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Departments Page Banner</label>
                                    <input type="file" name="departments_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['departments_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['departments_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Courses Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Courses Page Banner</label>
                                    <input type="file" name="courses_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['courses_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['courses_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Faculty Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Faculty Directory Banner</label>
                                    <input type="file" name="faculty_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['faculty_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['faculty_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Admissions Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Admissions Page Banner</label>
                                    <input type="file" name="admissions_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['admissions_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['admissions_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Placements Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Placements Page Banner</label>
                                    <input type="file" name="placements_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['placements_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['placements_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Events Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Events Page Banner</label>
                                    <input type="file" name="events_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['events_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['events_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Blogs Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Blogs Page Banner</label>
                                    <input type="file" name="blogs_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['blogs_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['blogs_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- News Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">News Page Banner</label>
                                    <input type="file" name="news_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['news_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['news_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Gallery Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Gallery Page Banner</label>
                                    <input type="file" name="gallery_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['gallery_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['gallery_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Contact Header -->
                            <div class="col-md-6">
                                <div class="p-2.5 border border-light-subtle rounded-3 bg-light">
                                    <label class="form-label font-semibold text-dark small mb-1">Contact Page Banner</label>
                                    <input type="file" name="contact_hero" class="form-control form-control-sm rounded-3 mb-1.5" accept="image/*">
                                    <?php if (!empty($settings['contact_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['contact_hero_image']); ?>" class="img-thumbnail rounded-3 w-100 shadow-xs" style="height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: About Page Banner -->
                    <div class="tab-pane fade" id="about-banners" role="tabpanel">
                        <!-- About Hero Banner -->
                        <div class="mb-3 p-3.5 bg-light rounded-3 border border-light-subtle">
                            <label class="form-label font-semibold small text-dark"><i class="fa-solid fa-circle-info me-1 text-primary"></i> About Us Page Main Header Banner</label>
                            <input type="file" name="about_hero" class="form-control rounded-3 mb-2" accept="image/*">
                            <?php if (!empty($settings['about_hero_image'])): ?>
                                <div class="text-center p-2.5 bg-white rounded-3 border border-light-subtle">
                                    <img src="<?php echo uploadUrl('settings', $settings['about_hero_image']); ?>" alt="About Hero" class="img-fluid rounded-3 shadow-xs" style="max-height: 120px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="save_settings" class="btn btn-primary rounded-pill w-100 py-3 fw-bold shadow-sm hover-lift"><i class="fa-solid fa-floppy-disk me-1.5"></i> Save Banners &amp; Branding</button>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
