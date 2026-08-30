<?php
$page_title = "AI Chatbot Engine Settings";
include_once __DIR__ . '/../../admin/includes/header.php';

Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $keys = [
            'openai_api_key',
            'openai_model',
            'openai_temperature',
            'max_tokens',
            'fallback_message',
            'enable_openai',
            'system_prompt'
        ];

        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $val = trim($_POST[$k]);
                $exists = $db->fetchOne("SELECT id FROM chatbot_settings WHERE setting_key = ?", [$k]);
                if ($exists) {
                    $db->update('chatbot_settings', ['setting_value' => $val], 'setting_key = ?', [$k]);
                } else {
                    $db->insert('chatbot_settings', ['setting_key' => $k, 'setting_value' => $val]);
                }
            }
        }

        setFlash('success', 'AI Chatbot & OpenAI engine configurations updated successfully.');
        redirect('settings.php');
    }
}

// Fetch all settings
$settingsRaw = $db->fetchAll("SELECT * FROM chatbot_settings");
$settings = [];
foreach ($settingsRaw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-gears text-warning me-2"></i>AI Engine Configuration</h1>
    <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="fa-solid fa-shield-halved me-1"></i> 0% Client Key Exposure</span>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1"></i> Doc Upload & Text Extractor</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link active font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<form method="POST" action="settings.php">
    <?php echo Security::csrfField(); ?>
    <div class="row g-4">
        <!-- OpenAI API Credentials -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-key text-warning me-2"></i>OpenAI Server Credentials</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-success border-0 shadow-xs mb-3 d-flex align-items-center gap-3">
                            <i class="fa-solid fa-circle-check text-success fa-2x"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-gift me-1"></i> Free AI Engine Active (Zero Cost)</h6>
                                <small class="text-muted">No API key required! 🤖 CampusAI automatically uses the built-in <strong>GeneralKnowledgeEngine</strong> for 100% free, instant technical and general educational responses. Entering an OpenAI key is optional.</small>
                            </div>
                        </div>
                        <label class="form-label font-semibold small">OpenAI Secret API Key (Optional)</label>
                        <input type="password" name="openai_api_key" class="form-control" value="<?php echo htmlspecialchars($settings['openai_api_key'] ?? ''); ?>" placeholder="sk-proj-... (Leave blank for Free AI Mode)">
                        <small class="text-muted d-block mt-1"><i class="fa-solid fa-lock text-success me-1"></i> Encrypted & stored server-side. Never sent to client-side JS.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small">AI Model Selection</label>
                        <select name="openai_model" class="form-select">
                            <option value="gpt-4o-mini" <?php echo ($settings['openai_model'] ?? '') === 'gpt-4o-mini' ? 'selected' : ''; ?>>gpt-4o-mini (Fast & Recommended)</option>
                            <option value="gpt-4o" <?php echo ($settings['openai_model'] ?? '') === 'gpt-4o' ? 'selected' : ''; ?>>gpt-4o (High Intelligence)</option>
                            <option value="gpt-3.5-turbo" <?php echo ($settings['openai_model'] ?? '') === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>gpt-3.5-turbo</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small">Enable OpenAI Integration</label>
                        <select name="enable_openai" class="form-select">
                            <option value="1" <?php echo ($settings['enable_openai'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled (Hybrid Routing)</option>
                            <option value="0" <?php echo ($settings['enable_openai'] ?? '') === '0' ? 'selected' : ''; ?>>Disabled (SOET DB Only)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small">Temperature (0.0 - 1.0)</label>
                        <input type="number" step="0.1" min="0" max="1" name="openai_temperature" class="form-control" value="<?php echo htmlspecialchars($settings['openai_temperature'] ?? '0.7'); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small">Max Response Tokens</label>
                        <input type="number" name="max_tokens" class="form-control" value="<?php echo htmlspecialchars($settings['max_tokens'] ?? '800'); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- System Prompts & Fallback Controls -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-sliders text-warning me-2"></i>System Instructions & Fallback Text</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-semibold small">System Prompt Instructions</label>
                        <textarea name="system_prompt" class="form-control" rows="4"><?php echo htmlspecialchars($settings['system_prompt'] ?? 'You are the official AI Assistant for SOET (School of Engineering & Technology), MGM University.'); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-semibold small">Fallback Response Text (When No Info Found)</label>
                        <textarea name="fallback_message" class="form-control" rows="3"><?php echo htmlspecialchars($settings['fallback_message'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" name="save_settings" class="btn btn-warning text-dark fw-bold px-4 shadow-sm"><i class="fa-solid fa-floppy-disk me-1"></i> Save Configurations</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include_once __DIR__ . '/../../admin/includes/footer.php'; ?>
