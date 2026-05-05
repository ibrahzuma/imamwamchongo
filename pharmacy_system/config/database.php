<?php
/**
 * Database Configuration
 * Uses PDO with prepared statements for security.
 */
class Database {
    // Read from env vars when set (for production), fall back to local XAMPP defaults.
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    public $conn;

    public function __construct() {
        $this->host     = getenv('DB_HOST')     ?: 'localhost';
        $this->db_name  = getenv('DB_NAME')     ?: 'pharmacy_db';
        $this->username = getenv('DB_USER')     ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    /**
     * Get the PDO database connection.
     */
    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log the real error server-side; never reveal it to the browser.
            error_log('DB connection error: ' . $e->getMessage());
            http_response_code(500);
            die('Database connection failed. Please contact the administrator.');
        }
        return $this->conn;
    }
}
