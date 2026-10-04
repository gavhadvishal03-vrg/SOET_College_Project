<?php
echo "Testing PHP...\n";
$start = microtime(true);

$dsns = [
    "mysql:host=127.0.0.1;port=3307;charset=utf8mb4",
    "mysql:host=127.0.0.1;port=3306;charset=utf8mb4",
    "mysql:host=localhost;port=3307;charset=utf8mb4",
];

foreach ($dsns as $dsn) {
    echo "Trying $dsn ... ";
    $t0 = microtime(true);
    try {
        $pdo = new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);
        echo "SUCCESS in " . round(microtime(true) - $t0, 3) . "s\n";
    } catch (Exception $e) {
        echo "FAILED in " . round(microtime(true) - $t0, 3) . "s: " . $e->getMessage() . "\n";
    }
}
