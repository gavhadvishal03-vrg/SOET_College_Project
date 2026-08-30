<?php
$page_title = "Document Upload & Text Extractor";
include_once __DIR__ . '/../../admin/includes/header.php';
require_once __DIR__ . '/../../chatbot/services/DocumentExtractor.php';

Auth::requirePermission('manage_chatbot_kb');

$db = Database::getInstance();

// Handle Document Upload & Text Extraction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['document_file'];
        $allowed = ['text/plain', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['txt', 'pdf', 'docx'])) {
            setFlash('danger', 'Only .txt, .pdf, and .docx documents are supported.');
        } else {
            $uploadDir = __DIR__ . '/../../chatbot/knowledge/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $targetName = 'doc_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.]/', '', $file['name']);
            $targetPath = $uploadDir . $targetName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $extractedText = DocumentExtractor::extractText($targetPath, $file['type']);
                
                $docId = $db->insert('uploaded_documents', [
                    'filename' => $targetName,
                    'original_name' => $file['name'],
                    'file_type' => $ext,
                    'file_size' => $file['size'],
                    'extracted_text' => $extractedText,
                    'status' => 'extracted'
                ]);

                setFlash('success', 'Document uploaded and text extracted! Review content below before publishing into Knowledge Base.');
                redirect('documents.php?action=review&id=' . $docId);
            } else {
                setFlash('danger', 'Failed to move uploaded document file.');
            }
        }
    }
}

// Handle Import into Knowledge Base
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
            'status' => 'active'
        ]);

        $db->update('uploaded_documents', ['status' => 'imported'], 'id = ?', [$docId]);
        setFlash('success', 'Document text successfully imported into SOET Knowledge Base!');
        redirect('knowledge-base.php');
    }
}

$action = $_GET['action'] ?? 'list';
$reviewDoc = null;

if ($action === 'review' && isset($_GET['id'])) {
    $reviewDoc = $db->fetchOne("SELECT * FROM uploaded_documents WHERE id = ?", [(int)$_GET['id']]);
}

$docs = $db->fetchAll("SELECT * FROM uploaded_documents ORDER BY id DESC");
$categories = $db->fetchAll("SELECT * FROM chatbot_categories ORDER BY name");
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-file-arrow-up text-warning me-2"></i>Document Text Extractor</h1>
    <span class="badge bg-primary fs-6 px-3 py-2">Supports PDF, DOCX, TXT</span>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link active font-semibold" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1"></i> Doc Upload & Text Extractor</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<!-- Upload Box -->
<div class="card border-0 shadow-sm p-4 mb-4">
    <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i>Upload College Document / Policy</h5>
    <form method="POST" action="documents.php" enctype="multipart/form-data">
        <?php echo Security::csrfField(); ?>
        <div class="row g-3 align-items-center">
            <div class="col-md-9">
                <input type="file" name="document_file" class="form-control" accept=".txt,.pdf,.docx" required>
                <small class="text-muted">Extract text from syllabus PDFs, prospectus, lab manuals, or policy files.</small>
            </div>
            <div class="col-md-3">
                <button type="submit" name="upload_doc" class="btn btn-warning text-dark font-semibold w-100"><i class="fa-solid fa-gear me-1"></i> Upload & Extract Text</button>
            </div>
        </div>
    </form>
</div>

<!-- Review & Import Form Modal / Card -->
<?php if ($reviewDoc): ?>
    <div class="card border-0 shadow-sm p-4 mb-4 bg-light">
        <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-check-double text-success me-2"></i>Review & Approve Extracted Document Content</h5>
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
                    <label class="form-label font-semibold small">Keywords (comma separated)</label>
                    <input type="text" name="keywords" class="form-control" placeholder="e.g. syllabus, lab, regulation">
                </div>
                <div class="col-12 text-end mt-3">
                    <a href="documents.php" class="btn btn-outline-secondary me-2">Skip</a>
                    <button type="submit" name="import_kb" class="btn btn-success font-semibold px-4"><i class="fa-solid fa-file-import me-1"></i> Approve & Import to Knowledge Base</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Documents Queue List -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Document Name</th>
                        <th>Type & Size</th>
                        <th>Extracted Snippet</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($docs)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No documents uploaded for text extraction yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($docs as $d): ?>
                            <tr>
                                <td><strong>#<?php echo $d['id']; ?></strong></td>
                                <td><div class="font-bold"><?php echo htmlspecialchars($d['original_name']); ?></div></td>
                                <td><span class="badge bg-secondary"><?php echo strtoupper($d['file_type']); ?></span> (<?php echo round($d['file_size']/1024, 1); ?> KB)</td>
                                <td class="small text-muted" style="max-width: 300px;"><?php echo htmlspecialchars(mb_substr($d['extracted_text'], 0, 100)) . '...'; ?></td>
                                <td>
                                    <?php if ($d['status'] === 'imported'): ?>
                                        <span class="badge bg-success">Imported</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Extracted</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="documents.php?action=review&id=<?php echo $d['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye me-1"></i> Review & Import</a>
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
