<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $dump = "-- SOET Full Database Backup\n-- Generated on: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $t) {
        $create = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_ASSOC);
        $dump .= "DROP TABLE IF EXISTS `{$t}`;\n" . $create['Create Table'] . ";\n\n";
        
        $rows = $pdo->query("SELECT * FROM `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $cols = array_map(function($c) { return "`{$c}`"; }, array_keys($r));
            $vals = array_map(function($v) use ($pdo) {
                return $v === null ? 'NULL' : $pdo->quote($v);
            }, array_values($r));
            $dump .= "INSERT INTO `{$t}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
        }
        $dump .= "\n";
    }

    $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $backupFile = __DIR__ . '/../database/backup_before_admin_cms_update_' . date('Ymd_His') . '.sql';
    file_put_contents($backupFile, $dump);
    echo "Backup completed successfully! Saved to: {$backupFile} (" . strlen($dump) . " bytes)\n";
} catch (Exception $e) {
    echo "Backup failed: " . $e->getMessage() . "\n";
}
