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

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">LLM &amp; Engine Core</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">AI Engine Configuration</h2>
        <p class="text-muted small mb-0">Configure language models, system hyperparameters, fallback routing, and server credentials</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fs-6 shadow-xs">
            <i class="fa-solid fa-shield-halved me-1.5"></i> 0% Client Key Exposure
        </span>
    </div>
</div>

<!-- Navigation Pills -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-2 mb-4">
    <ul class="nav nav-pills gap-1 flex-wrap" id="aiSubNav">
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="index.php"><i class="fa-solid fa-chart-line me-1.5 text-primary"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="knowledge-base.php"><i class="fa-solid fa-book me-1.5 text-primary"></i> Knowledge Base</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="faq.php"><i class="fa-solid fa-circle-question me-1.5 text-info"></i> FAQs</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1.5 text-warning"></i> Documents Extractor</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="unanswered.php"><i class="fa-solid fa-question me-1.5 text-danger"></i> Unanswered</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="conversations.php"><i class="fa-solid fa-comments me-1.5 text-success"></i> Conversations</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1.5 text-warning"></i> Feedback</a></li>
        <li class="nav-item"><a class="nav-link active rounded-pill font-semibold px-3 py-2 text-sm" href="settings.php"><i class="fa-solid fa-gears me-1.5"></i> AI Config</a></li>
    </ul>
</div>

<form method="POST" action="settings.php">
    <?php echo Security::csrfField(); ?>
    <div class="row g-4">
        <!-- OpenAI API Credentials -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h5 class="font-display fw-bold text-dark mb-0">
                        <i class="fa-solid fa-key text-primary me-2"></i>LLM Credentials &amp; Model Routing
                    </h5>
                    <span class="text-muted small">Server-Side Only</span>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-success border border-success-subtle bg-success-subtle rounded-4 mb-3 p-3 d-flex align-items-start gap-3">
                            <div class="w-10 h-10 rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0 mt-0.5" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-bolt"></i>
                            </div>
                            <div>
                                <h6 class="font-display fw-bold mb-1 text-success">Free AI Engine Active (Zero API Cost)</h6>
                                <p class="text-muted small mb-0" style="line-height: 1.5;">
                                    CampusAI automatically uses the built-in <strong>GeneralKnowledgeEngine &amp; RAG Vector Table</strong> for instant, zero-cost responses. Supplying an OpenAI API key is purely optional.
                                </p>
                            </div>
                        </div>

                        <label class="form-label font-semibold small text-secondary">OpenAI Secret API Key (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light rounded-start-3"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="openai_api_key" class="form-control rounded-end-3 font-mono text-sm" value="<?php echo htmlspecialchars($settings['openai_api_key'] ?? ''); ?>" placeholder="sk-proj-... (Leave blank for Free Local AI Mode)">
                        </div>
                        <div class="form-text text-xs text-muted mt-1">
                            <i class="fa-solid fa-shield-check text-success me-1"></i> Stored securely in database. Never exposed to browser or client scripts.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small text-secondary">Model Selection</label>
                        <select name="openai_model" class="form-select rounded-3 font-medium">
                            <option value="gpt-4o-mini" <?php echo ($settings['openai_model'] ?? '') === 'gpt-4o-mini' ? 'selected' : ''; ?>>gpt-4o-mini (Fast &amp; Recommended)</option>
                            <option value="gpt-4o" <?php echo ($settings['openai_model'] ?? '') === 'gpt-4o' ? 'selected' : ''; ?>>gpt-4o (High Intelligence)</option>
                            <option value="gpt-3.5-turbo" <?php echo ($settings['openai_model'] ?? '') === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>gpt-3.5-turbo</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small text-secondary">API Cloud Integration</label>
                        <select name="enable_openai" class="form-select rounded-3 font-medium">
                            <option value="1" <?php echo ($settings['enable_openai'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled (Hybrid AI Routing)</option>
                            <option value="0" <?php echo ($settings['enable_openai'] ?? '') === '0' ? 'selected' : ''; ?>>Disabled (Local DB &amp; Built-in Only)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small text-secondary">Temperature (0.0 - 1.0)</label>
                        <input type="number" step="0.1" min="0" max="1" name="openai_temperature" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['openai_temperature'] ?? '0.7'); ?>">
                        <div class="form-text text-xs">Lower is more deterministic, higher is more creative.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-semibold small text-secondary">Max Response Tokens</label>
                        <input type="number" name="max_tokens" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['max_tokens'] ?? '800'); ?>">
                        <div class="form-text text-xs">Upper limit on generation length per message.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Prompts & Fallback Controls -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h5 class="font-display fw-bold text-dark mb-0">
                        <i class="fa-solid fa-sliders text-warning me-2"></i>System Instructions &amp; Fallbacks
                    </h5>
                    <span class="text-muted small">Chatbot Persona</span>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-semibold small text-secondary">Master System Prompt Instructions</label>
                        <textarea name="system_prompt" class="form-control rounded-3 font-mono text-sm" rows="5"><?php echo htmlspecialchars($settings['system_prompt'] ?? 'You are the official AI Assistant for SOET (School of Engineering & Technology), MGM University.'); ?></textarea>
                        <div class="form-text text-xs">Defines personality, tone of voice, formatting guidelines, and institutional context.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-semibold small text-secondary">Default Fallback Response (When Query is Unknown)</label>
                        <textarea name="fallback_message" class="form-control rounded-3 text-sm" rows="3"><?php echo htmlspecialchars($settings['fallback_message'] ?? ''); ?></textarea>
                        <div class="form-text text-xs">Delivered to visitors when no confidence match is reached across local database and engines.</div>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" name="save_settings" class="btn btn-warning text-dark font-semibold rounded-pill px-4 shadow-sm hover-lift">
                            <i class="fa-solid fa-floppy-disk me-1.5"></i> Save AI Configurations
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include_once __DIR__ . '/../../admin/includes/footer.php'; ?>
