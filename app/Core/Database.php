<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Thin singleton wrapper around PDO. All Models talk to the DB through
 * this class so there is exactly one connection per request.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            // Never leak credentials or raw PDO messages to the browser.
            error_log('[DB CONNECTION ERROR] ' . $e->getMessage());
            http_response_code(500);
            if ((require dirname(__DIR__, 2) . '/config/app.php')['debug']) {
                die('Database connection failed: ' . $e->getMessage());
            }
            die('A server error occurred. Please try again later.');
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

    /**
     * Run a prepared statement and return the PDOStatement.
     * @param array<string,mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    // Prevent cloning/unserialization of the singleton.
    private function __clone(): void
    {
    }

    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize Database singleton');
    }
}
