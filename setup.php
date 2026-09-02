<?php
/**
 * SOET MGM University — 1-Click Database Auto-Installer & System Initializer
 * Enables zero-config 100% fail-proof execution on ANY computer / OS / server.
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 1);

$dbName = 'soet_college';
$dbUser = 'root';
$dbPass = '';
$hostsToProbe = ['127.0.0.1;port=3307', '127.0.0.1;port=3306', 'localhost;port=3307', 'localhost;port=3306', '127.0.0.1', 'localhost'];

$pdo = null;
$connectedHost = null;
$errorLog = [];

// 1. Multi-Port Auto-Probing MySQL Connection
foreach ($hostsToProbe as $host) {
    try {
        $dsn = "mysql:host={$host};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $connectedHost = $host;
        break;
    } catch (PDOException $e) {
        $errorLog[] = "Host '{$host}': " . $e->getMessage();
    }
}

$isCli = (php_sapi_name() === 'cli');

if (!$pdo) {
    $errMsg = "Database Connection Failed on all tested ports (3306, 3307).\nMake sure MySQL or XAMPP MySQL Service is running.\nDetails: " . implode(' | ', $errorLog);
    if ($isCli) {
        echo "❌ {$errMsg}\n";
        exit(1);
    }
    die("<div style='font-family:sans-serif; padding:40px; background:#fff3f3; color:#721c24; border:2px solid #f5c6cb; border-radius:12px; max-width:650px; margin:50px auto;'><h2>❌ Database Connection Failed</h2><p>{$errMsg}</p></div>");
}

$setupSuccess = false;
$setupMessage = "";

try {
    // 2. Create Database if missing
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `{$dbName}`;");

    // 3. Check if tables already initialized
    $stmt = $pdo->query("SHOW TABLES LIKE 'faculty'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        $schemaPath = __DIR__ . '/database/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new Exception("schema.sql not found at {$schemaPath}");
        }

        $sqlContent = file_get_contents($schemaPath);
        
        // Execute schema queries
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec($sqlContent);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $setupMessage = "Database `{$dbName}` created & imported successfully from schema.sql!";
    } else {
        $setupMessage = "Database `{$dbName}` is already installed and fully operational!";
    }
    $setupSuccess = true;
} catch (Exception $ex) {
    $setupMessage = "Installation Error: " . $ex->getMessage();
}

if ($isCli) {
    if ($setupSuccess) {
        echo "✅ SUCCESS: {$setupMessage} (Connected via {$connectedHost})\n";
        exit(0);
    } else {
        echo "❌ ERROR: {$setupMessage}\n";
        exit(1);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOET System Auto-Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #ffffff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .setup-card { background: #ffffff; color: #1e293b; border-radius: 20px; max-width: 550px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.5); overflow: hidden; border-top: 6px solid #bfa15f; }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="setup-card mx-auto p-4 p-sm-5 text-center">
        <div class="mb-3">
            <div class="d-inline-flex align-items-center justify-content-center bg-dark text-warning rounded-circle shadow-sm mb-2" style="width: 80px; height: 80px;">
                <i class="fa-solid fa-server fa-2x"></i>
            </div>
            <h3 class="fw-bold mb-1" style="color: #0d233a;">SOET Portal Initializer</h3>
            <span class="badge bg-warning text-dark font-semibold">1-Click Fail-Proof Setup</span>
        </div>

        <?php if ($setupSuccess): ?>
            <div class="alert alert-success border-0 shadow-xs text-start my-4 p-3 rounded-3" role="alert">
                <div class="d-flex align-items-center mb-1 fw-bold text-success">
                    <i class="fa-solid fa-circle-check fa-lg me-2"></i> System Ready & Operational!
                </div>
                <p class="small text-muted mb-0"><?php echo htmlspecialchars($setupMessage); ?></p>
                <div class="small text-dark mt-2"><strong>Connected Host:</strong> <code><?php echo htmlspecialchars($connectedHost); ?></code></div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <a href="index.php" class="btn btn-dark btn-lg py-2.5 rounded-pill font-bold shadow-sm" style="background-color: #0d233a;">
                    <i class="fa-solid fa-globe me-2 text-warning"></i> Open Public Website Portal
                </a>
                <a href="admin/login.php" class="btn btn-outline-secondary btn-md py-2 rounded-pill font-semibold">
                    <i class="fa-solid fa-user-shield me-2"></i> Open Executive Admin Panel
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger border-0 shadow-xs text-start my-4 p-3 rounded-3" role="alert">
                <div class="d-flex align-items-center mb-1 fw-bold text-danger">
                    <i class="fa-solid fa-triangle-exclamation fa-lg me-2"></i> Setup Interrupted
                </div>
                <p class="small text-muted mb-0"><?php echo htmlspecialchars($setupMessage); ?></p>
            </div>
            <a href="setup.php" class="btn btn-warning w-100 py-2.5 rounded-pill font-bold">
                <i class="fa-solid fa-rotate-right me-2"></i> Retry Installation
            </a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
