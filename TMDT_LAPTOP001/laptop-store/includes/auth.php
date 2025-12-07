<?php
require_once 'config.php';
require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Đăng ký tài khoản mới
     */
    public function register($userData) {
        try {
            // Kiểm tra email đã tồn tại chưa
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$userData['email']]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email đã được sử dụng'];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password, full_name, phone, address, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'CUSTOMER') 
                    RETURNING id";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                $userData['email'], // dùng email làm username
                $userData['email'],
                $hashedPassword,
                $userData['full_name'],
                $userData['phone'] ?? '',
                $userData['address'] ?? ''
            ]);
            
            $userId = $stmt->fetch()['id'];
            
            // Tự động đăng nhập sau khi đăng ký
            $this->login($userData['email'], $userData['password']);
            
            return [
                'success' => true,
                'message' => 'Đăng ký thành công',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
    
    /**
     * Đăng nhập
     */
    public function login($email, $password) {
        try {
            $sql = "SELECT * FROM users WHERE email = ? AND is_active = true";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email không tồn tại'];
            }
            
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Mật khẩu không đúng'];
            }
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Update last login
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$user['id']]);
            
            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'user' => $user
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
    
    /**
     * Đăng xuất
     */
    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }
    
    /**
     * Kiểm tra đăng nhập
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Kiểm tra admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
    }
    
    /**
     * Lấy thông tin user hiện tại
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        
        $sql = "SELECT id, username, email, full_name, phone, address, role, created_at 
                FROM users WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        
        return $stmt->fetch();
    }
}

// Khởi tạo Auth
$auth = new Auth();
?>