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

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-sliders text-warning me-2"></i>Website Settings & Image Manager</h1>
        <small class="text-muted">Upload and update website hero banners, logos, and page headers dynamically</small>
    </div>
    <span class="badge bg-warning text-dark px-3 py-2 fs-6"><i class="fa-solid fa-gears me-1"></i> Global Configuration</span>
</div>

<div class="row g-4">
    <!-- Text & Contact Information -->
    <div class="col-lg-6">
        <!-- Form 1: General Institutional Info -->
        <form method="POST" action="settings.php">
            <?php echo Security::csrfField(); ?>
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Institutional Information</h5>
                
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label font-semibold small">College Full Name *</label>
                        <input type="text" name="college_name" class="form-control" required value="<?php echo htmlspecialchars($settings['college_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-semibold small">Short Name *</label>
                        <input type="text" name="college_short" class="form-control" required value="<?php echo htmlspecialchars($settings['college_short'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label font-semibold small">Institutional Email *</label>
                        <input type="email" name="college_email" class="form-control" required value="<?php echo htmlspecialchars($settings['college_email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-semibold small">Contact Number *</label>
                        <input type="text" name="college_phone" class="form-control" required value="<?php echo htmlspecialchars($settings['college_phone'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label font-semibold small">Campus Location Address *</label>
                        <textarea name="college_address" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['college_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small">Admissions Status</label>
                        <select name="admission_status" class="form-select">
                            <option value="open" <?php echo ($settings['admission_status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="closed" <?php echo ($settings['admission_status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-semibold small">Admission Instructions</label>
                        <textarea name="admission_instructions" class="form-control" rows="3"><?php echo htmlspecialchars($settings['admission_instructions'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-semibold small">Chatbot Welcome Greeting</label>
                        <textarea name="chatbot_greeting" class="form-control" rows="2"><?php echo htmlspecialchars($settings['chatbot_greeting'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-3">
                        <button type="submit" name="save_settings" class="btn btn-primary w-100 py-2 font-semibold"><i class="fa-solid fa-floppy-disk me-1"></i> Save General Info</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form 2: Dean & Director Leadership Profile -->
        <form method="POST" action="settings.php" enctype="multipart/form-data">
            <?php echo Security::csrfField(); ?>
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-top: 4px solid var(--secondary-color) !important;">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2">
                    <i class="fa-solid fa-user-tie text-warning me-2"></i>Dean & Director Leadership Profile
                </h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-semibold small">Director / Dean Profile Photo</label>
                        <input type="file" name="director_photo" class="form-control mb-2" accept="image/*">
                        <?php if (!empty($settings['director_image'])): ?>
                            <div class="d-flex align-items-center gap-3 p-2 bg-light rounded border mb-2">
                                <img src="<?php echo uploadUrl('settings', $settings['director_image']); ?>" alt="Director Photo" class="img-thumbnail rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                <small class="text-muted font-semibold">Current Profile Photo Preview</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-semibold small">Director Full Name *</label>
                        <input type="text" name="director_name" class="form-control" value="<?php echo htmlspecialchars($settings['director_name'] ?? 'Dr. Rajesh Kumar Sen'); ?>" placeholder="e.g. Dr. Rajesh Kumar Sen" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-semibold small">Director Designation *</label>
                        <input type="text" name="director_designation" class="form-control" value="<?php echo htmlspecialchars($settings['director_designation'] ?? 'Dean & Director, SOET'); ?>" placeholder="e.g. Dean & Director, SOET" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label font-semibold small">Director Welcome Quote / Message *</label>
                        <textarea name="director_message" class="form-control" rows="4" placeholder="Enter Director welcome quote..." required><?php echo htmlspecialchars($settings['director_message'] ?? "SOET is not just a college; it's an ecosystem of tech innovation. We welcome you to experience an education that shapes you into a global leader. We continuously push the boundaries of classroom training to bring hands-on project excellence to our engineering departments."); ?></textarea>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" name="save_director_profile" class="btn btn-warning text-dark fw-bold w-100 py-2.5 shadow-sm">
                            <i class="fa-solid fa-user-check me-1"></i> Save Director Profile
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
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-images text-warning me-2"></i>Global Branding & Page Banners</h5>
                
                <ul class="nav nav-pills mb-3" id="bannerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-2 px-3 me-2 font-semibold small" id="home-tab" data-bs-toggle="pill" data-bs-target="#home-banners" type="button" role="tab">Homepage & Logo</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 px-3 me-2 font-semibold small" id="headers-tab" data-bs-toggle="pill" data-bs-target="#headers-banners" type="button" role="tab">Page Banners</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 px-3 font-semibold small" id="about-tab" data-bs-toggle="pill" data-bs-target="#about-banners" type="button" role="tab">About Banner</button>
                    </li>
                </ul>

                <div class="tab-content pt-2" id="bannerTabsContent">
                    <!-- Tab 1: Homepage & Logo -->
                    <div class="tab-pane fade show active" id="home-banners" role="tabpanel">
                        <!-- Site Logo -->
                        <div class="mb-3 p-3 bg-light rounded border">
                            <label class="form-label font-semibold small text-primary-color"><i class="fa-solid fa-shield-halved me-1"></i> Custom Navbar Logo</label>
                            <input type="file" name="site_logo_file" class="form-control mb-2" accept="image/*">
                            <?php if (!empty($settings['site_logo'])): ?>
                                <div class="text-center p-2 bg-white rounded border">
                                    <img src="<?php echo uploadUrl('settings', $settings['site_logo']); ?>" alt="Navbar Logo" style="max-height: 50px; object-fit: contain;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Home Hero Banner -->
                        <div class="mb-3 p-3 bg-light rounded border">
                            <label class="form-label font-semibold small text-primary-color"><i class="fa-solid fa-panorama me-1"></i> Homepage Hero Slider Background</label>
                            <input type="file" name="home_hero" class="form-control mb-2" accept="image/*">
                            <small class="text-muted d-block mb-2">Main banner displayed on homepage hero section (1600x800px).</small>
                            <?php if (!empty($settings['home_hero_image'])): ?>
                                <div class="text-center p-2 bg-white rounded border">
                                    <img src="<?php echo uploadUrl('settings', $settings['home_hero_image']); ?>" alt="Home Hero" class="img-fluid rounded" style="max-height: 100px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Home Welcome Side Image -->
                        <div class="mb-3 p-3 bg-light rounded border">
                            <label class="form-label font-semibold small text-primary-color"><i class="fa-solid fa-building-columns me-1"></i> Homepage Welcome Side Image</label>
                            <input type="file" name="home_welcome" class="form-control mb-2" accept="image/*">
                            <?php if (!empty($settings['home_welcome_image'])): ?>
                                <div class="text-center p-2 bg-white rounded border">
                                    <img src="<?php echo uploadUrl('settings', $settings['home_welcome_image']); ?>" alt="Home Welcome" class="img-fluid rounded" style="max-height: 100px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tab 2: Page Header Banners -->
                    <div class="tab-pane fade" id="headers-banners" role="tabpanel">
                        <div class="row g-3" style="max-height: 380px; overflow-y: auto;">
                            <!-- Departments Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Departments Page Banner</label>
                                    <input type="file" name="departments_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['departments_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['departments_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Courses Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Courses Page Banner</label>
                                    <input type="file" name="courses_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['courses_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['courses_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Faculty Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Faculty Directory Banner</label>
                                    <input type="file" name="faculty_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['faculty_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['faculty_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Admissions Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Admissions Page Banner</label>
                                    <input type="file" name="admissions_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['admissions_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['admissions_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Placements Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Placements Page Banner</label>
                                    <input type="file" name="placements_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['placements_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['placements_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Events Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Events Page Banner</label>
                                    <input type="file" name="events_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['events_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['events_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Blogs Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Blogs Page Banner</label>
                                    <input type="file" name="blogs_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['blogs_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['blogs_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- News Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">News Page Banner</label>
                                    <input type="file" name="news_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['news_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['news_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Gallery Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Gallery Page Banner</label>
                                    <input type="file" name="gallery_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['gallery_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['gallery_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Contact Header -->
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <label class="form-label font-semibold small mb-1">Contact Page Banner</label>
                                    <input type="file" name="contact_hero" class="form-control form-control-sm mb-1" accept="image/*">
                                    <?php if (!empty($settings['contact_hero_image'])): ?>
                                        <img src="<?php echo uploadUrl('settings', $settings['contact_hero_image']); ?>" class="img-thumbnail w-100" style="height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: About Page Banner -->
                    <div class="tab-pane fade" id="about-banners" role="tabpanel">
                        <!-- About Hero Banner -->
                        <div class="mb-3 p-3 bg-light rounded border">
                            <label class="form-label font-semibold small text-primary-color"><i class="fa-solid fa-circle-info me-1"></i> About Us Page Main Header Banner</label>
                            <input type="file" name="about_hero" class="form-control mb-2" accept="image/*">
                            <?php if (!empty($settings['about_hero_image'])): ?>
                                <div class="text-center p-2 bg-white rounded border">
                                    <img src="<?php echo uploadUrl('settings', $settings['about_hero_image']); ?>" alt="About Hero" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="save_settings" class="btn btn-primary w-100 py-3 fw-bold shadow-sm"><i class="fa-solid fa-floppy-disk me-1"></i> Save Banners & Branding</button>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
