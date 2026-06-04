<?php
namespace config;
use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $host    = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
            $db      = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'bt';
            $user    = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
            $pass    = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: ''; 
            $charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}