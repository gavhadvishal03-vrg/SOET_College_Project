<?php
/**
 * Database Connection & Query Abstraction Layer
 *
 * Implements a Singleton PDO wrapper providing robust MySQL connection handling,
 * automatic port fallbacks (handling both 3307 and 3306), prepared statement execution,
 * and simplified CRUD helper methods for the entire application.
 */
class Database
{
    /** @var Database|null Singleton instance */
    private static ?Database $instance = null;

    /** @var PDO Active PDO database connection */
    private PDO $pdo;

    /**
     * Private constructor to enforce Singleton pattern.
     * Attempts multiple DSN candidates (e.g. port 3307 and 3306) to ensure
     * seamless connectivity across different local MySQL/MariaDB setups.
     *
     * @throws PDOException If no connection candidate succeeds.
     */
    private function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 3,
        ];

        $rawHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $port = defined('DB_PORT') ? DB_PORT : '3307';
        $dbName = defined('DB_NAME') ? DB_NAME : 'soet_college';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

        // Extract port if embedded in DB_HOST string (e.g., '127.0.0.1;port=3307')
        $host = $rawHost;
        if (strpos($rawHost, ';port=') !== false) {
            $parts = explode(';port=', $rawHost);
            $host = $parts[0];
            $port = $parts[1];
        }

        // DSN candidates prioritizing target XAMPP port (3307) then standard 3306
        $dsnCandidates = [
            "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}",
            "mysql:host=127.0.0.1;port=3307;dbname={$dbName};charset={$charset}",
            "mysql:host=localhost;port=3307;dbname={$dbName};charset={$charset}",
            "mysql:host=127.0.0.1;port=3306;dbname={$dbName};charset={$charset}",
            "mysql:host=localhost;dbname={$dbName};charset={$charset}"
        ];

        $firstException = null;
        $lastException = null;
        foreach (array_unique($dsnCandidates) as $dsn) {
            try {
                $this->pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                return; // Successfully connected!
            } catch (PDOException $e) {
                if ($firstException === null) {
                    $firstException = $e;
                }
                $lastException = $e;
            }
        }

        if ($firstException) {
            throw $firstException;
        }
    }

    /**
     * Retrieves the singleton Database instance.
     *
     * @return Database The shared Database instance.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gets the underlying raw PDO connection.
     *
     * @return PDO The active PDO instance.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Prepares and executes a parameterized SQL query.
     *
     * @param string $sql The SQL query with positional placeholders (?)
     * @param array $params Positional parameters to bind to the query
     * @return PDOStatement The executed statement object
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Executes a query and fetches all matching rows as an associative array.
     *
     * @param string $sql The SQL query
     * @param array $params Positional parameters
     * @return array List of associative arrays representing rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Executes a query and fetches a single row as an associative array.
     *
     * @param string $sql The SQL query
     * @param array $params Positional parameters
     * @return array|null The row data or null if not found
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Inserts a record into a table and returns the auto-generated primary key ID.
     *
     * @param string $table Target database table name
     * @param array $data Associative array of column => value pairs
     * @return int Last inserted record ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Updates records in a table matching the given WHERE condition.
     *
     * @param string $table Target database table name
     * @param array $data Associative array of column => value pairs to update
     * @param string $where WHERE clause SQL string (e.g., "id = ?")
     * @param array $whereParams Parameters for the WHERE clause
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $stmt = $this->query($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    /**
     * Deletes records matching the given WHERE condition.
     *
     * @param string $table Target database table name
     * @param string $where WHERE clause SQL string
     * @param array $params Parameters for the WHERE clause
     * @return int Number of deleted rows
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Counts records matching an optional WHERE condition.
     *
     * @param string $table Target database table name
     * @param string $where Optional WHERE condition (default: '1=1')
     * @param array $params Parameters for the WHERE condition
     * @return int Total count of matching records
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$where}";
        return (int) ($this->fetchOne($sql, $params)['cnt'] ?? 0);
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * @return int Last insert ID
     */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
}
