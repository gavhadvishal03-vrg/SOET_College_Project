<?php
/**
 * Database Connection Class - Singleton PDO
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 3,
        ];

        $primaryHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1;port=3307';
        $dbName = defined('DB_NAME') ? DB_NAME : 'soet_college';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

        // Auto-Probing DSN Candidate List for 100% Port & Environment Resilience
        $dsnCandidates = [
            'mysql:host=' . $primaryHost . ';dbname=' . $dbName . ';charset=' . $charset,
            'mysql:host=127.0.0.1;port=3307;dbname=' . $dbName . ';charset=' . $charset,
            'mysql:host=127.0.0.1;port=3306;dbname=' . $dbName . ';charset=' . $charset,
            'mysql:host=localhost;dbname=' . $dbName . ';charset=' . $charset,
            'mysql:host=127.0.0.1;dbname=' . $dbName . ';charset=' . $charset
        ];

        $lastException = null;
        foreach (array_unique($dsnCandidates) as $dsn) {
            try {
                $this->pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                return; // Successfully connected!
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }

        if ($lastException) {
            throw $lastException;
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $stmt = $this->query($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$where}";
        return (int) ($this->fetchOne($sql, $params)['cnt'] ?? 0);
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
}
