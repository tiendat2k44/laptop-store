<?php
/**
 * Utility Functions for Laptop Store
 */

/**
 * Redirect to another page
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Format price with VND currency
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . CURRENCY_SYMBOL;
}

/**
 * Get current date time
 */
function now() {
    return date('Y-m-d H:i:s');
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Clean input (remove HTML tags)
 */
function clean($input) {
    if (is_array($input)) {
        return array_map('clean', $input);
    }
    return strip_tags(trim($input));
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate order code
 */
function generateOrderCode() {
    return 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Calculate discount percentage
 */
function calculateDiscountPercentage($originalPrice, $discountPrice) {
    if ($originalPrice <= 0) return 0;
    return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
}

/**
 * Get current URL
 */
function currentUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Add success message
 */
function addSuccessMessage($message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = ['success' => [], 'error' => [], 'warning' => [], 'info' => []];
    }
    $_SESSION['flash_messages']['success'][] = $message;
}

/**
 * Add error message
 */
function addErrorMessage($message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = ['success' => [], 'error' => [], 'warning' => [], 'info' => []];
    }
    $_SESSION['flash_messages']['error'][] = $message;
}

/**
 * Add warning message
 */
function addWarningMessage($message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = ['success' => [], 'error' => [], 'warning' => [], 'info' => []];
    }
    $_SESSION['flash_messages']['warning'][] = $message;
}

/**
 * Add info message
 */
function addInfoMessage($message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = ['success' => [], 'error' => [], 'warning' => [], 'info' => []];
    }
    $_SESSION['flash_messages']['info'][] = $message;
}

/**
 * Get flash messages
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? ['success' => [], 'error' => [], 'warning' => [], 'info' => []];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Display flash messages
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    $html = '';
    
    $alertTypes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    foreach ($alertTypes as $type => $class) {
        if (!empty($messages[$type])) {
            foreach ($messages[$type] as $message) {
                $html .= '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
                $html .= htmlspecialchars($message);
                $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                $html .= '</div>';
            }
        }
    }
    
    return $html;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Vietnamese)
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $phone);
}

/**
 * Format phone number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
    }
    return $phone;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = '') {
    try {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (:user_id, :action, :details, :ip, :ua, NOW())";
        
        $params = [
            ':user_id' => $userId,
            ':action' => $action,
            ':details' => $details,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $db->insert($sql, $params);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Upload image
 */
function uploadImage($file, $targetDir = null) {
    $errors = [];
    
    if ($targetDir === null) {
        $targetDir = PRODUCT_IMAGE_PATH;
    }
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'errors' => ['File không hợp lệ']];
    }
    
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = 'File quá lớn. Kích thước tối đa: ' . (MAX_UPLOAD_SIZE / 1048576) . 'MB';
    }
    
    // Check file type
    $fileInfo = getimagesize($file['tmp_name']);
    if ($fileInfo === false) {
        $errors[] = 'File không phải là hình ảnh hợp lệ';
    }
    
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
        $errors[] = 'Chỉ chấp nhận file: ' . implode(', ', ALLOWED_IMAGE_TYPES);
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $fileType;
    $targetFile = $targetDir . $filename;
    
    // Create directory if not exists
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Optionally resize image here
        return ['success' => true, 'filename' => $filename, 'path' => $targetFile];
    }
    
    return ['success' => false, 'errors' => ['Không thể upload file. Vui lòng thử lại.']];
}

/**
 * Delete image file
 */
function deleteImage($filename, $dir = null) {
    if ($dir === null) {
        $dir = PRODUCT_IMAGE_PATH;
    }
    
    $filepath = $dir . $filename;
    
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * Get product image URL
 */
function getProductImage($imageUrl, $default = '/assets/images/no-image.png') {
    if (empty($imageUrl)) {
        return $default;
    }
    
    // If it's an array, get first image
    if (is_array($imageUrl)) {
        $imageUrl = $imageUrl[0] ?? $default;
    }
    
    // Check if it's a full URL
    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return $imageUrl;
    }
    
    // Check if file exists
    $filepath = PRODUCT_IMAGE_PATH . $imageUrl;
    if (file_exists($filepath)) {
        return '/assets/images/products/' . $imageUrl;
    }
    
    return $default;
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return formatDate($datetime, $format);
}

/**
 * Time ago
 */
function timeAgo($datetime) {
    if (empty($datetime)) return '';
    
    try {
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) {
            return $diff . ' giây trước';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' phút trước';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' giờ trước';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . ' ngày trước';
        } else {
            return formatDateTime($datetime);
        }
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Truncate text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Generate slug from text
 */
function generateSlug($text) {
    // Convert to lowercase
    $text = mb_strtolower($text, 'UTF-8');
    
    // Vietnamese characters mapping
    $vietnamese = [
        'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
        'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
        'ì', 'í', 'ị', 'ỉ', 'ĩ',
        'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
        'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
        'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
        'đ'
    ];
    
    $latin = [
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd'
    ];
    
    $text = str_replace($vietnamese, $latin, $text);
    
    // Remove special characters
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Replace spaces and multiple dashes with single dash
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Trim dashes from ends
    return trim($text, '-');
}

/**
 * Get order status badge class
 */
function getOrderStatusBadge($status) {
    $badges = [
        'PENDING' => 'bg-warning',
        'CONFIRMED' => 'bg-info',
        'PROCESSING' => 'bg-primary',
        'SHIPPING' => 'bg-primary',
        'DELIVERED' => 'bg-success',
        'COMPLETED' => 'bg-success',
        'CANCELLED' => 'bg-danger',
        'REFUNDED' => 'bg-secondary'
    ];
    
    return $badges[$status] ?? 'bg-secondary';
}

/**
 * Get order status text
 */
function getOrderStatusText($status) {
    $texts = [
        'PENDING' => 'Chờ xác nhận',
        'CONFIRMED' => 'Đã xác nhận',
        'PROCESSING' => 'Đang xử lý',
        'SHIPPING' => 'Đang giao hàng',
        'DELIVERED' => 'Đã giao hàng',
        'COMPLETED' => 'Hoàn thành',
        'CANCELLED' => 'Đã hủy',
        'REFUNDED' => 'Đã hoàn tiền'
    ];
    
    return $texts[$status] ?? $status;
}

/**
 * Get payment status badge class
 */
function getPaymentStatusBadge($status) {
    $badges = [
        'PENDING' => 'bg-warning',
        'PROCESSING' => 'bg-info',
        'COMPLETED' => 'bg-success',
        'FAILED' => 'bg-danger',
        'REFUNDED' => 'bg-secondary',
        'CANCELLED' => 'bg-dark'
    ];
    
    return $badges[$status] ?? 'bg-secondary';
}

/**
 * Check if user owns order
 */
function userOwnsOrder($orderId, $userId) {
    $db = Database::getInstance();
    $sql = "SELECT COUNT(*) as count FROM orders WHERE id = :order_id AND user_id = :user_id";
    $result = $db->selectOne($sql, [':order_id' => $orderId, ':user_id' => $userId]);
    return $result && $result['count'] > 0;
}
?>