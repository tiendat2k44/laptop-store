<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            // DSN cho PostgreSQL
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            
            // Kiểm tra driver trước
            $drivers = PDO::getAvailableDrivers();
            if (!in_array('pgsql', $drivers)) {
                throw new Exception("PostgreSQL PDO driver not found. Available drivers: " . implode(', ', $drivers));
            }
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            // Set UTF-8
            $this->pdo->exec("SET NAMES 'UTF8'");
            $this->pdo->exec("SET timezone = 'Asia/Ho_Chi_Minh'");
            
        } catch (PDOException $e) {
            // Ghi log chi tiết
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Hiển thị message thân thiện
            die("<h3>Database Connection Error</h3>
                <p>Please check:</p>
                <ol>
                    <li>PostgreSQL service is running</li>
                    <li>Database 'laptop_store' exists</li>
                    <li>Username/password is correct</li>
                    <li>PHP pdo_pgsql extension is installed</li>
                </ol>
                <p>Error details: " . htmlspecialchars($e->getMessage()) . "</p>");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // ... rest of your methods
}

// Tạo kết nối
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("Failed to initialize database: " . $e->getMessage());
}
?>