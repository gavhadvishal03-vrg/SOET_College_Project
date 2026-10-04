<?php
/**
 * Database Backup & Restoration Manager Module
 *
 * Provides database disaster recovery utilities for Super Administrators:
 * - Generates full SQL dump exports containing DROP, CREATE TABLE, and INSERT statements
 * - Imports and restores database schema from uploaded .sql backup files
 * - Disables foreign key checks during import for seamless table re-creation
 *
 * Required Permission: 'backup_restore' (Super Admin only)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requireLogin();
Auth::requirePermission('backup_restore');

$db = Database::getInstance();
$pdo = $db->getConnection();

// --- Handle Database Backup Export (Download) ---
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sql_output = "-- SOET Database Backup\n";
    $sql_output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        // Drop table statement
        $sql_output .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        // Show Create Table
        $res = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $create_row = $res->fetch(PDO::FETCH_NUM);
        $sql_output .= $create_row[1] . ";\n\n";

        // Query rows
        $rows_res = $pdo->query("SELECT * FROM `{$table}`");
        $fields_count = $rows_res->columnCount();

        while ($row = $rows_res->fetch(PDO::FETCH_NUM)) {
            $sql_output .= "INSERT INTO `{$table}` VALUES(";
            for ($j = 0; $j < $fields_count; $j++) {
                if (isset($row[$j])) {
                    // Escape string characters
                    $val = str_replace(["\\", "\n", "\r", "'"], ["\\\\", "\\n", "\\r", "\\'"], $row[$j]);
                    $sql_output .= "'{$val}'";
                } else {
                    $sql_output .= "NULL";
                }
                if ($j < ($fields_count - 1)) {
                    $sql_output .= ",";
                }
            }
            $sql_output .= ");\n";
        }
        $sql_output .= "\n";
    }

    $sql_output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    // Set headers to trigger file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="soet_backup_' . date('Ymd_His') . '.sql"');
    header('Content-Length: ' . strlen($sql_output));
    echo $sql_output;
    exit;
}

// --- Handle Database Restore ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_database'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
        redirect('backup.php');
    }

    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', 'Please upload a valid SQL backup file.');
        redirect('backup.php');
    }

    $file_path = $_FILES['backup_file']['tmp_name'];
    $file_ext = pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION);

    if (strtolower($file_ext) !== 'sql') {
        setFlash('danger', 'Only files with .sql extension are allowed.');
        redirect('backup.php');
    }

    try {
        $sql_content = file_get_contents($file_path);
        
        // Execute the SQL queries. We can split by semicolon.
        // To be safe, disable foreign key checks, run queries, and enable it back.
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        // Simple SQL parser that handles multi-line queries
        $queries = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql_content);
        
        $success_count = 0;
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $pdo->exec($query);
                $success_count++;
            }
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        
        setFlash('success', 'Database restored successfully! ' . $success_count . ' SQL statements executed.');
    } catch (Exception $e) {
        setFlash('danger', 'Database restore failed: ' . $e->getMessage());
    }
    redirect('backup.php');
}

$page_title = "Backup & Restore";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">System Maintenance</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Database Backup &amp; Restore</h2>
        <p class="text-muted small mb-0">Export system snapshots or restore the complete institutional database schema</p>
    </div>
    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2 fw-semibold">
        <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> Super Admin Level Action
    </span>
</div>

<div class="row g-4">
    <!-- Backup Export card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 text-center d-flex flex-column">
            <div class="stat-icon bg-primary-subtle text-primary rounded-4 mx-auto mb-3 d-flex align-items-center justify-content-center shadow-xs" style="width: 72px; height: 72px; font-size: 28px;">
                <i class="fa-solid fa-cloud-arrow-down"></i>
            </div>
            <h4 class="font-display fw-bold text-dark mb-2">Export Database Backup</h4>
            <p class="text-muted small mb-4 px-lg-3" style="line-height: 1.7;">
                Export and download the full schema of the SOET College database, including all configuration values, roles, placements, events, and log archives, formatted as standard SQL file statements.
            </p>
            
            <div class="mt-auto pt-3">
                <a href="backup.php?action=export" class="btn btn-primary rounded-pill w-100 py-2.5 fw-bold shadow-sm hover-lift">
                    <i class="fa-solid fa-download me-1.5"></i> Download SQL Backup Snapshot
                </a>
                <div class="text-muted small mt-2" style="font-size: 14px;"><i class="fa-solid fa-shield-halved me-1 text-success"></i> Includes foreign keys and transactional state</div>
            </div>
        </div>
    </div>

    <!-- Restore DB card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column">
            <div class="text-center mb-3">
                <div class="stat-icon bg-danger-subtle text-danger rounded-4 mx-auto mb-3 d-flex align-items-center justify-content-center shadow-xs" style="width: 72px; height: 72px; font-size: 28px;">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <h4 class="font-display fw-bold text-dark mb-2">Restore Database</h4>
                <p class="text-muted small mb-0 px-lg-2">
                    Upload a previously generated SOET SQL database backup file to restore structures. All existing table records will be replaced.
                </p>
            </div>
            
            <div class="alert alert-warning-subtle border border-warning-subtle rounded-3 small p-3 mb-4">
                <i class="fa-solid fa-circle-exclamation me-1 text-warning"></i> <strong>Caution:</strong> This process will overwrite current records with the uploaded snapshot.
            </div>

            <form method="POST" action="backup.php" enctype="multipart/form-data" class="mt-auto">
                <?php echo Security::csrfField(); ?>
                
                <div class="mb-3">
                    <label class="form-label font-semibold text-dark small">Choose SQL backup file (.sql) <span class="text-danger">*</span></label>
                    <input type="file" name="backup_file" class="form-control rounded-3" accept=".sql" required>
                </div>
                
                <button type="submit" name="restore_database" class="btn btn-danger rounded-pill w-100 py-2.5 fw-bold shadow-sm hover-lift" onclick="return confirm('WARNING: Are you absolutely sure you want to restore the database? This will overwrite all current tables!');">
                    <i class="fa-solid fa-rotate me-1.5"></i> Execute Database Restore
                </button>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
