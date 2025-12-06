<?php
/**
 * ============================================
 * CONFIGURATION FILE - LAPTOP STORE
 * ============================================
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', '1508');

// Application Configuration
define('SITE_NAME', 'LaptopStore');
define('SITE_URL', 'http://localhost/laptop-store');
define('ADMIN_EMAIL', 'admin@laptopstore.com');
define('SUPPORT_EMAIL', 'support@laptopstore.com');

// File Upload Configuration
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('PRODUCT_IMAGE_PATH', __DIR__ . '/../assets/images/products/');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CART_SESSION_KEY', 'cart_items');

// Payment Configuration (Test Mode)
define('PAYMENT_TEST_MODE', true);
define('VNPAY_TMN_CODE', 'TEST123');
define('MOMO_PARTNER_CODE', 'MOMOTEST');

// Security Configuration
define('PASSWORD_SALT', 'laptop_store_salt_2024_secure_key');
define('JWT_SECRET', 'your_jwt_secret_here_change_in_production');

// Email Configuration (Gmail SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_FROM_NAME', 'LaptopStore');

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Currency
define('CURRENCY_SYMBOL', '₫');
define('CURRENCY_CODE', 'VND');

// Debug Mode
define('DEBUG_MODE', true);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Auto-load classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Create necessary directories
$directories = [
    __DIR__ . '/../logs',
    __DIR__ . '/../assets/uploads',
    __DIR__ . '/../assets/images/products',
    __DIR__ . '/../assets/images/banners'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}
?>