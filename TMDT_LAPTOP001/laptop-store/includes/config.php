<?php
// ============================================
// POSTGRESQL CONFIGURATION - ĐÃ KẾT NỐI THÀNH CÔNG
// ============================================

// Đường dẫn gốc của dự án
define('BASE_PATH', dirname(__DIR__)); // Điều chỉnh nếu cần
define('APP_PATH', BASE_PATH . '/includes');
define('PUBLIC_PATH', BASE_PATH . '/pages');
define('ASSETS_PATH', BASE_PATH . '/assets');

// URL Configuration - QUAN TRỌNG
define('BASE_URL', 'http://localhost/TienDat123/TMDT_LAPTOP001/laptop-store');
define('ASSETS_URL', BASE_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMAGES_URL', ASSETS_URL . '/images');

// PostgreSQL Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', '1508');

// Upload Configuration
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('UPLOAD_PATH', __DIR__ . '/../assets/images/products/');

// Start session
session_start();

// ============================================
// POSTGRESQL CONNECTION - SỬA QUAN TRỌNG
// ============================================
try {
    // Đúng format PostgreSQL DSN
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true
    ]);
    
    // Set UTF-8 và timezone
    $pdo->exec("SET NAMES 'UTF8'");
    $pdo->exec("SET timezone = 'Asia/Ho_Chi_Minh'");
    
    // Optional: Log success (remove in production)
    // error_log("PostgreSQL connection established successfully");
    
} catch (PDOException $e) {
    die("<h3>Database Connection Error</h3>
        <p>PostgreSQL connection failed. Please check:</p>
        <ul>
            <li>Database: " . DB_NAME . "</li>
            <li>User: " . DB_USER . "</li>
            <li>Password: " . (DB_PASS ? "Set" : "Not set") . "</li>
            <li>Port: " . DB_PORT . "</li>
        </ul>
        <p><strong>Error Details:</strong> " . $e->getMessage() . "</p>");
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
}

function hasPermission($requiredRole) {
    if (!isset($_SESSION['role'])) return false;
    $roles = ['CUSTOMER' => 1, 'STAFF' => 2, 'ADMIN' => 3];
    return ($roles[$_SESSION['role']] ?? 0) >= ($roles[$requiredRole] ?? 0);
}

// Format price
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Redirect with message
function redirect($url, $message = null) {
    if ($message) {
        $_SESSION['flash_message'] = $message;
    }
    header("Location: $url");
    exit();
}
?>