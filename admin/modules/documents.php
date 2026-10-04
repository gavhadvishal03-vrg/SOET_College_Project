<?php
/**
 * Official Institutional Documents & Booklets Management Module
 *
 * Manages official downloadable academic and compliance documents:
 * - Upload and catalog brochures, syllabus documents, rulebooks, and booklets
 * - Category classification (Brochure, Syllabus, Rulebook, Booklet, Form)
 * - Academic batch tag and featured status flags
 * - Real-time file size calculation and download hit tracking
 * - Document registry CSV export
 *
 * Required Permission: 'manage_courses'
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_courses'); // Academic & Documents management permission

$db = Database::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_doc = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll("SELECT title, category, academic_year, file_size, download_count, is_featured, is_active, created_at FROM documents ORDER BY category, title");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Official_Documents_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Document Title', 'Category', 'Academic Year/Batch', 'File Size', 'Downloads', 'Featured', 'Status', 'Created Date']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['title'],
            ucfirst($r['category']),
            $r['academic_year'],
            $r['file_size'] ?? 'N/A',
            $r['download_count'],
            $r['is_featured'] ? 'Yes' : 'No',
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Official Documents & Booklets Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_doc = $db->fetchOne("SELECT * FROM documents WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_document'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $doc_id = isset($_POST['doc_id']) ? (int)$_POST['doc_id'] : null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category'] ?? 'brochure');
    $academic_year = trim($_POST['academic_year'] ?? 'AY 2026-27');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $file_path = $edit_doc ? $edit_doc['file_path'] : '';
    $file_size = $edit_doc ? $edit_doc['file_size'] : '';

    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $doc_errors = Security::validateUpload($_FILES['document_file'], ALLOWED_DOC_TYPES);
        if (empty($doc_errors)) {
            $uploaded = Security::uploadFile($_FILES['document_file'], 'documents', 'doc_');
            if ($uploaded) {
                $file_path = 'uploads/documents/' . $uploaded;
                $bytes = $_FILES['document_file']['size'];
                $file_size = $bytes > 1048576 ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024) . ' KB';
            }
        } else {
            setFlash('danger', implode(' ', $doc_errors));
        }
    }

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($category)) {
        setFlash('danger', 'Please enter document title and category.');
    } elseif (empty($file_path)) {
        setFlash('danger', 'Please upload a valid PDF document.');
    } else {
        $data = [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'academic_year' => $academic_year,
            'file_path' => $file_path,
            'file_size' => $file_size,
            'sort_order' => $sort_order,
            'is_featured' => $is_featured,
            'is_active' => $is_active
        ];

        if ($doc_id) {
            $db->update('documents', $data, 'id = ?', [$doc_id]);
            setFlash('success', 'Official document updated successfully.');
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->insert('documents', $data);
            setFlash('success', 'New official brochure/booklet registered successfully.');
        }
        redirect('documents.php');
    }
}

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('documents', 'id = ?', [$target_id]);
    setFlash('success', 'Document record removed.');
    redirect('documents.php');
}

// Fetch all documents
$documents = $db->fetchAll("SELECT * FROM documents ORDER BY is_featured DESC, category ASC, title ASC");
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Official Resources</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Brochures &amp; Booklets Manager</h2>
        <p class="text-muted small mb-0">Manage official SOET brochures, academic leaflets, and downloadable curriculum booklets</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="documents.php?action=export_csv" class="btn btn-sm btn-success rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="documents.php?action=add" class="btn btn-primary btn-sm rounded-pill px-3 font-semibold">
                <i class="fa-solid fa-cloud-arrow-up me-1 text-warning"></i> Upload New Document
            </a>
        </div>
    <?php else: ?>
        <a href="documents.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-semibold">
            <i class="fa-solid fa-angle-left me-1"></i> Back to List
        </a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h5 class="font-display fw-bold mb-0 text-dark"><i class="fa-solid fa-file-pdf text-danger me-2"></i>Official Document Repository (<?php echo count($documents); ?>)</h5>
            <input type="text" id="docSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search title, category, batch..." style="width: 260px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="documentsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Document Title</th>
                            <th>Category</th>
                            <th>Academic Year / Batch</th>
                            <th>File Size</th>
                            <th class="text-center">Featured</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">No documents registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="fa-solid fa-file-pdf fa-lg"></i>
                                            </div>
                                            <div>
                                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="font-display fw-bold text-dark text-decoration-none hover-primary">
                                                    <?php echo htmlspecialchars($doc['title']); ?>
                                                </a>
                                                <small class="text-muted d-block text-truncate" style="max-width: 380px;"><?php echo htmlspecialchars($doc['description'] ?? ''); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill text-uppercase" style="font-size: 14px;">
                                            <?php echo htmlspecialchars($doc['category']); ?>
                                        </span>
                                    </td>
                                    <td><span class="font-semibold text-secondary small"><?php echo htmlspecialchars($doc['academic_year']); ?></span></td>
                                    <td><span class="small text-muted"><?php echo htmlspecialchars($doc['file_size'] ?? 'PDF'); ?></span></td>
                                    <td class="text-center">
                                        <?php if ($doc['is_featured']): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill"><i class="fa-solid fa-star me-1"></i> Featured</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border rounded-pill">Standard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo statusBadge($doc['is_active'] ? 'active' : 'inactive'); ?></td>
                                    <td class="text-end pe-4 text-nowrap">
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 font-semibold me-1" title="View PDF">
                                            <i class="fa-solid fa-eye me-1"></i> View
                                        </a>
                                        <a href="documents.php?action=edit&id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-semibold me-1">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                        </a>
                                        <a href="documents.php?action=delete&id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 font-semibold" onclick="return confirm('Delete this official document entry?');">
                                            <i class="fa-solid fa-trash-can me-1"></i> Delete
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('docSearchInput');
        const table = document.getElementById('documentsTable');
        if (searchInput && table) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                table.querySelectorAll('tbody tr').forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = (!q || text.includes(q)) ? '' : 'none';
                });
            });
        }
    });
    </script>
<?php else: ?>
    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
                <h4 class="fw-bold text-primary-color mb-4">
                    <i class="fa-solid fa-file-arrow-up text-warning me-2"></i><?php echo $edit_doc ? 'Edit Document Information' : 'Upload Official Brochure / Booklet'; ?>
                </h4>
                <form method="POST" action="documents.php" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_doc): ?>
                        <input type="hidden" name="doc_id" value="<?php echo $edit_doc['id']; ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font-semibold">Document Title *</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($edit_doc['title'] ?? ''); ?>" placeholder="e.g. SOET Admission Brochure AY 2026-27">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="brochure" <?php echo ($edit_doc && $edit_doc['category'] === 'brochure') ? 'selected' : ''; ?>>Official Brochure</option>
                                <option value="leaflet" <?php echo ($edit_doc && $edit_doc['category'] === 'leaflet') ? 'selected' : ''; ?>>Institutional Leaflet</option>
                                <option value="curriculum" <?php echo ($edit_doc && $edit_doc['category'] === 'curriculum') ? 'selected' : ''; ?>>Curriculum Booklet</option>
                                <option value="intake" <?php echo ($edit_doc && $edit_doc['category'] === 'intake') ? 'selected' : ''; ?>>Intake Matrix</option>
                                <option value="admission" <?php echo ($edit_doc && $edit_doc['category'] === 'admission') ? 'selected' : ''; ?>>Admission Notice / Guidelines</option>
                                <option value="policy" <?php echo ($edit_doc && $edit_doc['category'] === 'policy') ? 'selected' : ''; ?>>Academic Policy</option>
                                <option value="report" <?php echo ($edit_doc && $edit_doc['category'] === 'report') ? 'selected' : ''; ?>>Placement / Annual Report</option>
                                <option value="notice" <?php echo ($edit_doc && $edit_doc['category'] === 'notice') ? 'selected' : ''; ?>>Official Notice</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Academic Year / Applicable Batch *</label>
                            <input type="text" name="academic_year" class="form-control" required value="<?php echo htmlspecialchars($edit_doc['academic_year'] ?? 'AY 2026-27'); ?>" placeholder="e.g. AY 2026-27 or Batch 2026-30">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Display Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?php echo $edit_doc ? (int)($edit_doc['sort_order'] ?? 0) : 0; ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Upload PDF Document <?php echo $edit_doc ? '(Leave blank to keep existing)' : '*'; ?></label>
                            <input type="file" name="document_file" class="form-control" accept=".pdf,.doc,.docx" <?php echo $edit_doc ? '' : 'required'; ?>>
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Short Summary / Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief outline of document contents, scope, target audience..."><?php echo htmlspecialchars($edit_doc['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" <?php echo (!$edit_doc || $edit_doc['is_featured']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isFeatured">Featured on Homepage Quick Downloads</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_doc || $edit_doc['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActive">Active for Public Access</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="documents.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_document" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Document</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
