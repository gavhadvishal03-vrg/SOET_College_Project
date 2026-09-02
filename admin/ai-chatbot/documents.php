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

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-file-pdf text-danger me-2"></i>Q&A Training Documents & Ingestion</h1>
        <small class="text-muted">Upload, extract, and publish Q&A PDF/DOCX guides directly into the CampusAI knowledge base and FAQ database.</small>
    </div>
    <span class="badge bg-primary fs-6 px-3 py-2"><i class="fa-solid fa-brain me-1"></i> <?php echo $kbCount; ?> KB Articles | <?php echo $faqCount; ?> FAQs Live</span>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link active font-semibold" href="documents.php"><i class="fa-solid fa-file-pdf me-1"></i> Q&A Training Docs</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<!-- 1. Master Q&A Training Guide Card -->
<div class="card border-0 shadow-sm p-4 mb-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); color: white;">
    <div class="row align-items-center">
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="d-flex align-items-center mb-2">
                <span class="badge bg-success me-2 px-3 py-1"><i class="fa-solid fa-circle-check me-1"></i> Active in Database</span>
                <span class="badge bg-light text-dark px-3 py-1">63 Canonical Q&As</span>
            </div>
            <h4 class="fw-bold text-white mb-1">CampusAI Chatbot Master Knowledge & QnA Reference Guide</h4>
            <p class="text-white-50 small mb-2">
                Official canonical training guide containing deduplicated, high-precision answers across AI, Programming, Systems, Programs, Admissions, Fees, and Real-Time Seat Availability.
            </p>
            <div class="small text-white-50">
                <i class="fa-solid fa-folder me-1 text-warning"></i> <code>chatbot/train/CampusAI_Chatbot_QnA_Master_Guide.pdf</code> (<?php echo $masterGuideExists ? round(filesize($masterGuideFile)/1024, 1) : '18.1'; ?> KB)
            </div>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2 justify-content-lg-end">
                <a href="<?php echo APP_URL; ?>/chatbot/train/CampusAI_Chatbot_QnA_Master_Guide.pdf" target="_blank" class="btn btn-light text-dark font-semibold btn-sm">
                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> Download PDF
                </a>
                <a href="<?php echo APP_URL; ?>/chatbot/train/CampusAI_Chatbot_QnA_Master_Guide.docx" target="_blank" class="btn btn-outline-light font-semibold btn-sm">
                    <i class="fa-solid fa-file-word text-primary me-1"></i> Download DOCX
                </a>
                <a href="documents.php?action=sync_master_guide" class="btn btn-warning text-dark font-semibold btn-sm" onclick="return confirm('Synchronize and publish all 63 Q&As into the database now?');">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> 1-Click Sync to Database
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 2. Upload New Q&A or Knowledge Document -->
<div class="card border-0 shadow-sm p-4 mb-4">
    <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2">
        <i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i>Add Question & Answer Document (PDF / DOCX / TXT)
    </h5>
    <form method="POST" action="documents.php" enctype="multipart/form-data">
        <?php echo Security::csrfField(); ?>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label font-semibold small">Choose PDF / DOCX File *</label>
                <input type="file" name="document_file" class="form-control" accept=".pdf,.docx,.txt" required>
                <small class="text-muted">Upload a question-answer document, syllabus PDF, prospectus, or departmental guide.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label font-semibold small">Default Category *</label>
                <select name="category" class="form-select">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold small">Ingestion Mode *</label>
                <select name="import_mode" class="form-select font-semibold">
                    <option value="qna">⚡ Auto-Extract Q&A Pairs & Publish</option>
                    <option value="article">📄 Full Text Manual Review</option>
                </select>
            </div>
            <div class="col-12 text-end">
                <button type="submit" name="upload_doc" class="btn btn-primary font-semibold px-4">
                    <i class="fa-solid fa-bolt me-1"></i> Upload & Ingest into Chatbot Database
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Review & Import Form Card (If in Review Mode) -->
<?php if ($reviewDoc): ?>
    <div class="card border-0 shadow-sm p-4 mb-4 bg-light border-start border-4 border-warning">
        <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-check-double text-success me-2"></i>Review Extracted Document Content</h5>
        <form method="POST" action="documents.php">
            <input type="hidden" name="doc_id" value="<?php echo $reviewDoc['id']; ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label font-semibold small">Article Title *</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($reviewDoc['original_name']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-semibold small">Category *</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold small">Extracted Text Content (Review & Edit) *</label>
                    <textarea name="content" class="form-control" rows="8" required><?php echo htmlspecialchars($reviewDoc['extracted_text']); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold small">Search Keywords (comma separated)</label>
                    <input type="text" name="keywords" class="form-control" placeholder="e.g. syllabus, lab, regulation">
                </div>
                <div class="col-12 text-end mt-3">
                    <a href="documents.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" name="import_kb" class="btn btn-success font-semibold px-4"><i class="fa-solid fa-file-import me-1"></i> Approve & Import to Knowledge Base</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- 3. Documents Queue & History Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-database text-primary me-2"></i>Training Documents & Knowledge Ingestion History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Document Name</th>
                        <th>Format & Size</th>
                        <th>Content / Extracted Snippet</th>
                        <th>Database Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($docs)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No training documents uploaded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($docs as $d): ?>
                            <tr>
                                <td><strong>#<?php echo $d['id']; ?></strong></td>
                                <td>
                                    <div class="font-bold text-dark">
                                        <?php if ($d['file_type'] === 'pdf'): ?>
                                            <i class="fa-solid fa-file-pdf text-danger me-1"></i>
                                        <?php elseif ($d['file_type'] === 'docx'): ?>
                                            <i class="fa-solid fa-file-word text-primary me-1"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-file-lines text-secondary me-1"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($d['original_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo strtoupper($d['file_type']); ?></span>
                                    <small class="text-muted ms-1"><?php echo round($d['file_size']/1024, 1); ?> KB</small>
                                </td>
                                <td class="small text-muted" style="max-width: 320px;">
                                    <?php echo htmlspecialchars(mb_substr($d['extracted_text'], 0, 95)) . '...'; ?>
                                </td>
                                <td>
                                    <?php if ($d['status'] === 'published'): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Published to DB</span>
                                    <?php elseif ($d['status'] === 'imported'): ?>
                                        <span class="badge bg-info text-dark">Imported</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Extracted</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($d['original_name'] === 'CampusAI_Chatbot_QnA_Master_Guide.pdf'): ?>
                                        <a href="documents.php?action=sync_master_guide" class="btn btn-sm btn-outline-success me-1" title="Re-Sync">
                                            <i class="fa-solid fa-arrows-rotate"></i> Sync
                                        </a>
                                    <?php endif; ?>
                                    <a href="documents.php?action=review&id=<?php echo $d['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fa-solid fa-eye"></i> Review
                                    </a>
                                    <a href="documents.php?action=delete&id=<?php echo $d['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this document record?');">
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
