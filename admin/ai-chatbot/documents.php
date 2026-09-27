<?php
$page_title = "Q&A Training Documents & Ingestion";
include_once __DIR__ . '/../../admin/includes/header.php';
require_once __DIR__ . '/../../chatbot/services/DocumentExtractor.php';
require_once __DIR__ . '/../../chatbot/services/TrainingService.php';

Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();
$trainingService = new TrainingService();

// Handle 1-Click Master Guide Sync
if (isset($_GET['action']) && $_GET['action'] === 'sync_master_guide') {
    $res = $trainingService->publishMasterGuide();
    if ($res['success']) {
        setFlash('success', 'Master Q&A Document (CampusAI_Chatbot_QnA_Master_Guide.pdf) successfully synchronized! ' . $res['total_qna'] . ' canonical questions and answers published to Chatbot Database.');
    } else {
        setFlash('danger', 'Error synchronizing Master Guide: ' . ($res['message'] ?? 'Unknown error'));
    }
    redirect('documents.php');
}

// Handle Document Upload (Q&A or General Document)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $importMode = $_POST['import_mode'] ?? 'qna';
    $category = trim($_POST['category'] ?? 'general');

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['document_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['txt', 'pdf', 'docx'])) {
            setFlash('danger', 'Only .pdf, .docx, and .txt files are supported.');
        } else {
            $uploadDir = __DIR__ . '/../../chatbot/train/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $targetName = 'doc_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.]/', '', $file['name']);
            $targetPath = $uploadDir . $targetName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                if ($importMode === 'qna') {
                    $res = $trainingService->importUploadedQnADocument($targetPath, $file['name'], $category);
                    if ($res['success'] && $res['is_qna_document']) {
                        setFlash('success', "Q&A Document '{$file['name']}' uploaded and processed! Successfully extracted and published {$res['qna_count']} questions and answers directly into the Chatbot FAQ & Knowledge Base databases.");
                    } else {
                        setFlash('info', "Document '{$file['name']}' uploaded! No strict Q&A pattern detected; text has been extracted for review below.");
                        redirect('documents.php?action=review&id=' . ($res['doc_id'] ?? 0));
                    }
                } else {
                    $extractedText = DocumentExtractor::extractText($targetPath, $file['type']);
                    $docId = $db->insert('uploaded_documents', [
                        'filename' => $targetName,
                        'original_name' => $file['name'],
                        'file_type' => $ext,
                        'file_size' => $file['size'],
                        'extracted_text' => $extractedText,
                        'status' => 'extracted'
                    ]);
                    setFlash('success', 'Document text extracted! Review and import into Knowledge Base.');
                    redirect('documents.php?action=review&id=' . $docId);
                }
                redirect('documents.php');
            } else {
                setFlash('danger', 'Failed to save uploaded document.');
            }
        }
    }
}

// Handle Import of Manual Document into Knowledge Base
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_kb'])) {
    $docId = (int)$_POST['doc_id'];
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $content = trim($_POST['content']);
    $keywords = trim($_POST['keywords']);

    if (!empty($title) && !empty($content)) {
        $db->insert('knowledge_base', [
            'title' => $title,
            'category' => $category,
            'content' => $content,
            'keywords' => $keywords,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $db->update('uploaded_documents', ['status' => 'published'], 'id = ?', [$docId]);
        setFlash('success', 'Document text successfully imported into SOET Knowledge Base!');
        redirect('knowledge-base.php');
    }
}

// Handle Document Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $docId = (int)$_GET['id'];
    $db->delete('uploaded_documents', 'id = ?', [$docId]);
    setFlash('success', 'Document removed from queue.');
    redirect('documents.php');
}

$action = $_GET['action'] ?? 'list';
$reviewDoc = null;
if ($action === 'review' && isset($_GET['id'])) {
    $reviewDoc = $db->fetchOne("SELECT * FROM uploaded_documents WHERE id = ?", [(int)$_GET['id']]);
}

$docs = $db->fetchAll("SELECT * FROM uploaded_documents ORDER BY id DESC");
$categories = $db->fetchAll("SELECT * FROM chatbot_categories ORDER BY name");

// Master Guide Status
$masterGuideFile = __DIR__ . '/../../chatbot/train/CampusAI_Chatbot_QnA_Master_Guide.pdf';
$masterGuideDocx = __DIR__ . '/../../chatbot/train/CampusAI_Chatbot_QnA_Master_Guide.docx';
$masterGuideExists = file_exists($masterGuideFile);
$kbCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM knowledge_base WHERE status = 'active'")['cnt'];
$faqCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM faq WHERE status = 'active'")['cnt'];
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">Document Ingestion Pipeline</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Training Documents &amp; Ingestion</h2>
        <p class="text-muted small mb-0">Upload, parse, and publish Q&A PDF/DOCX guides directly into the CampusAI verified knowledge base</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <span class="badge bg-slate-900 text-white rounded-pill px-3 py-2 fs-6 shadow-xs">
            <i class="fa-solid fa-brain text-warning me-1.5"></i> <?php echo $kbCount; ?> Articles &bull; <?php echo $faqCount; ?> FAQs Live
        </span>
    </div>
</div>

<!-- Navigation Pills -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-2 mb-4">
    <ul class="nav nav-pills gap-1 flex-wrap" id="aiSubNav">
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="index.php"><i class="fa-solid fa-chart-line me-1.5 text-primary"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="knowledge-base.php"><i class="fa-solid fa-book me-1.5 text-primary"></i> Knowledge Base</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="faq.php"><i class="fa-solid fa-circle-question me-1.5 text-info"></i> FAQs</a></li>
        <li class="nav-item"><a class="nav-link active rounded-pill font-semibold px-3 py-2 text-sm" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1.5"></i> Documents Extractor</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="unanswered.php"><i class="fa-solid fa-question me-1.5 text-danger"></i> Unanswered</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="conversations.php"><i class="fa-solid fa-comments me-1.5 text-success"></i> Conversations</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1.5 text-warning"></i> Feedback</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="settings.php"><i class="fa-solid fa-gears me-1.5 text-muted"></i> AI Config</a></li>
    </ul>
</div>

<!-- 1. Master Q&A Training Guide Hero Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #09121f 100%);">
    <div class="position-absolute end-0 bottom-0 opacity-10 pe-4 pb-2 d-none d-md-block pointer-events-none">
        <i class="fa-solid fa-file-pdf" style="font-size: 140px; color: #fff;"></i>
    </div>
    <div class="row align-items-center position-relative">
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 font-semibold text-xs">
                    <i class="fa-solid fa-circle-check me-1"></i> Active Canonical Sync
                </span>
                <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2.5 py-1 font-semibold text-xs">63 Canonical Q&As</span>
            </div>
            <h3 class="font-display fw-extrabold text-white mb-1.5">CampusAI Master Q&A Training Guide</h3>
            <p class="text-white text-opacity-75 small mb-3" style="max-width: 650px; line-height: 1.6;">
                Official canonical reference dataset covering Engineering Programs, Admissions, Fees, Faculty, Campus Facilities, and Real-Time Seat Availability matrices.
            </p>
            <div class="d-flex align-items-center gap-2 text-white-50 text-xs font-mono bg-black bg-opacity-30 rounded-3 px-3 py-1.5 d-inline-flex border border-white border-opacity-10">
                <i class="fa-solid fa-folder-open text-warning"></i>
                <span>chatbot/train/CampusAI_Chatbot_QnA_Master_Guide.pdf</span>
                <span class="text-white-50">(<?php echo $masterGuideExists ? round(filesize($masterGuideFile)/1024, 1) : '18.1'; ?> KB)</span>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="d-flex flex-column gap-2 justify-content-lg-end">
                <div class="d-flex gap-2 justify-content-lg-end">
                    <a href="<?php echo APP_URL; ?>/chatbot/train/CampusAI_Chatbot_QnA_Master_Guide.pdf" target="_blank" class="btn btn-light btn-sm rounded-pill font-semibold px-3 shadow-xs hover-lift flex-grow-1 flex-lg-grow-0">
                        <i class="fa-solid fa-file-pdf text-danger me-1.5"></i> PDF
                    </a>
                    <a href="<?php echo APP_URL; ?>/chatbot/train/CampusAI_Chatbot_QnA_Master_Guide.docx" target="_blank" class="btn btn-outline-light btn-sm rounded-pill font-semibold px-3 hover-lift flex-grow-1 flex-lg-grow-0">
                        <i class="fa-solid fa-file-word text-info me-1.5"></i> DOCX
                    </a>
                </div>
                <a href="documents.php?action=sync_master_guide" class="btn btn-warning btn-sm rounded-pill font-semibold text-dark px-3.5 shadow-sm hover-lift" onclick="return confirm('Synchronize and publish all 63 Q&As into the live database now?');">
                    <i class="fa-solid fa-arrows-rotate me-1.5"></i> 1-Click Sync to Database
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 2. Upload New Q&A or Knowledge Document -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
        <h5 class="font-display fw-bold text-dark mb-0">
            <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>Ingest New Training Document
        </h5>
        <span class="text-muted small">Supports .pdf, .docx, and .txt up to 10MB</span>
    </div>
    <form method="POST" action="documents.php" enctype="multipart/form-data">
        <?php echo Security::csrfField(); ?>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label font-semibold small text-secondary">Document File <span class="text-danger">*</span></label>
                <input type="file" name="document_file" class="form-control rounded-3" accept=".pdf,.docx,.txt" required>
                <div class="form-text text-xs">Syllabus PDF, prospectus, brochure, or departmental policy document.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label font-semibold small text-secondary">Primary Category <span class="text-danger">*</span></label>
                <select name="category" class="form-select rounded-3">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold small text-secondary">Ingestion Mode <span class="text-danger">*</span></label>
                <select name="import_mode" class="form-select rounded-3 font-semibold">
                    <option value="qna">⚡ Auto-Extract Q&A Pairs</option>
                    <option value="article">📄 Full Text Review</option>
                </select>
            </div>
            <div class="col-12 text-end pt-2">
                <button type="submit" name="upload_doc" class="btn btn-primary rounded-pill font-semibold px-4 shadow-sm hover-lift">
                    <i class="fa-solid fa-bolt me-1.5 text-warning"></i> Upload &amp; Ingest into Database
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Review & Import Form Card (If in Review Mode) -->
<?php if ($reviewDoc): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-light border-start border-4 border-warning">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <h5 class="font-display fw-bold text-dark mb-0">
                <i class="fa-solid fa-check-double text-success me-2"></i>Review Extracted Document Content
            </h5>
            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1">Pending Approval</span>
        </div>
        <form method="POST" action="documents.php">
            <input type="hidden" name="doc_id" value="<?php echo $reviewDoc['id']; ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label font-semibold small text-secondary">Article Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control rounded-3" value="<?php echo htmlspecialchars($reviewDoc['original_name']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-semibold small text-secondary">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select rounded-3">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold small text-secondary">Extracted Text Content (Edit if needed) <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control rounded-3 font-mono text-sm" rows="8" required><?php echo htmlspecialchars($reviewDoc['extracted_text']); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold small text-secondary">Search Keywords (Comma separated)</label>
                    <input type="text" name="keywords" class="form-control rounded-3" placeholder="e.g. syllabus, lab, regulation, eligibility">
                </div>
                <div class="col-12 text-end mt-3">
                    <a href="documents.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2">Cancel</a>
                    <button type="submit" name="import_kb" class="btn btn-success btn-sm rounded-pill font-semibold px-4 shadow-sm hover-lift">
                        <i class="fa-solid fa-file-import me-1.5"></i> Approve &amp; Import to Knowledge Base
                    </button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- 3. Documents Queue & History Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="font-display fw-bold text-dark mb-0">
            <i class="fa-solid fa-database text-primary me-2"></i>Training Documents &amp; Ingestion History
        </h5>
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1 font-semibold text-xs">
            <?php echo count($docs); ?> Uploads Logged
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary text-uppercase text-xs tracking-wider">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Document Name</th>
                        <th>Format &amp; Size</th>
                        <th>Extracted Snippet</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($docs)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No training documents uploaded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($docs as $d): ?>
                            <tr>
                                <td class="ps-4 text-muted font-mono text-xs">#<?php echo $d['id']; ?></td>
                                <td>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        <?php if ($d['file_type'] === 'pdf'): ?>
                                            <i class="fa-solid fa-file-pdf text-danger fa-lg"></i>
                                        <?php elseif ($d['file_type'] === 'docx'): ?>
                                            <i class="fa-solid fa-file-word text-primary fa-lg"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-file-lines text-secondary fa-lg"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($d['original_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-slate-900 text-white rounded-pill px-2 py-0.5 text-xs"><?php echo strtoupper($d['file_type']); ?></span>
                                    <small class="text-muted ms-1 text-xs"><?php echo round($d['file_size']/1024, 1); ?> KB</small>
                                </td>
                                <td class="small text-muted" style="max-width: 300px;">
                                    <?php echo htmlspecialchars(mb_substr($d['extracted_text'], 0, 85)) . '...'; ?>
                                </td>
                                <td>
                                    <?php if ($d['status'] === 'published'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 text-xs">
                                            <i class="fa-solid fa-circle-check me-1"></i> Published
                                        </span>
                                    <?php elseif ($d['status'] === 'imported'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 text-xs">Imported</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 text-xs">Extracted</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    <?php if ($d['original_name'] === 'CampusAI_Chatbot_QnA_Master_Guide.pdf'): ?>
                                        <a href="documents.php?action=sync_master_guide" class="btn btn-xs btn-outline-success rounded-pill px-2.5 py-1 me-1 shadow-xs" title="Re-Sync">
                                            <i class="fa-solid fa-arrows-rotate me-1"></i> Sync
                                        </a>
                                    <?php endif; ?>
                                    <a href="documents.php?action=review&id=<?php echo $d['id']; ?>" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 me-1 shadow-xs" title="Review Content">
                                        <i class="fa-solid fa-eye me-1"></i> Review
                                    </a>
                                    <a href="documents.php?action=delete&id=<?php echo $d['id']; ?>" class="btn btn-xs btn-outline-danger rounded-pill px-2.5 py-1" onclick="return confirm('Remove this document record?');" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../admin/includes/footer.php'; ?>
