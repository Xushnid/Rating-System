<?php

namespace App;

use PDO;
use PDOException;

class Database {
    private $pdo;

    public function __construct() {
        $host = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_DATABASE'];
        $user = $_ENV['DB_USERNAME'];
        $pass = $_ENV['DB_PASSWORD'];
        $charset = $_ENV['DB_CHARSET'];

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            log_error("Database Connection Error: " . $e->getMessage());
            
            // Don't expose database details in production
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                throw new PDOException('Database connection failed. Please contact system administrator.', 500);
            } else {
                // Only show detailed error in development
                throw new PDOException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode());
            }
        }
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log the full error with SQL for debugging
            log_error("SQL Error: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
            
            // Don't expose SQL details in production
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                throw new PDOException('Database query failed. Please contact system administrator.', 500);
            } else {
                // Only show detailed error in development
                throw new PDOException('Database query failed: ' . $e->getMessage(), (int)$e->getCode());
            }
        }
    }
}