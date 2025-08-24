<?php
// Database connection and query helpers
// Appeal Prospect MVP - Database Layer

declare(strict_types=1);

/**
 * Database connection class with singleton pattern
 * Provides secure PDO connection with UTF8MB4 support
 */
class Database {
    private static ?PDO $connection = null;
    private static ?self $instance = null;
    
    private function __construct() {
        // Private constructor to prevent direct instantiation
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection with proper configuration
     */
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            require_once __DIR__ . '/config.php';
            
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_NAME
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            try {
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new RuntimeException('Database connection failed');
            }
        }
        
        return self::$connection;
    }
}

/**
 * Execute a prepared statement and return results
 */
function db_query(string $sql, array $params = []): array {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a prepared statement and return single row
 */
function db_query_single(string $sql, array $params = []): ?array {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Execute INSERT/UPDATE/DELETE and return affected rows
 */
function db_execute(string $sql, array $params = []): int {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Execute INSERT and return last inserted ID
 */
function db_insert(string $sql, array $params = []): int {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$pdo->lastInsertId();
}

/**
 * Begin database transaction
 */
function db_begin_transaction(): bool {
    return Database::getConnection()->beginTransaction();
}

/**
 * Commit database transaction
 */
function db_commit(): bool {
    return Database::getConnection()->commit();
}

/**
 * Rollback database transaction
 */
function db_rollback(): bool {
    return Database::getConnection()->rollBack();
}

/**
 * Check if we're in a transaction
 */
function db_in_transaction(): bool {
    return Database::getConnection()->inTransaction();
}

?>