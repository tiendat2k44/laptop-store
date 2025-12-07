<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$title = "Đăng ký - LaptopStore";

// Prepare DB connection (fix cho lỗi Undefined variable $db)
$pdo = null;
$errors = [];

try {
    // If project provides a helper function get_pdo(), prefer it
    if (function_exists('get_pdo')) {
        $pdo = get_pdo();
    } elseif (isset($db) && $db instanceof \PDO) {
        $pdo = $db;
    } else {
        // Fall back: build PDO from config constants (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS)
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
            throw new Exception('Database configuration not found. Vui lòng kiểm tra includes/config.php');
        }
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, defined('DB_PORT') ? DB_PORT : '5432', DB_NAME);
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS ?? '', $pdoOptions);
    }
} catch (Exception $e) {
    // If DB connection fails, add to errors and continue to show the form with message
    $errors[] = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
    // Do NOT exit — show user-friendly error on page
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($full_name)) $errors[] = "Họ tên không được để trống";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ";
    if (strlen($password) < 6) $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
    if ($password !== $confirm_password) $errors[] = "Mật khẩu xác nhận không khớp";

    // Proceed only if no validation errors and PDO is available
    if (empty($errors)) {
        if (!$pdo) {
            $errors[] = 'Không thể kết nối tới cơ sở dữ liệu. Vui lòng thử lại sau.';
        } else {
            try {
                // Check if email exists
                $sql = 'SELECT id FROM users WHERE email = :email LIMIT 1';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    $errors[] = "Email đã được sử dụng";
                } else {
                    // Insert user. Map columns properly.
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Use named params for clarity. Assuming table users has columns:
                    // username, email, password, full_name, phone, role
                    $sql = "INSERT INTO users (username, email, password, full_name, phone, role)
                            VALUES (:username, :email, :password, :full_name, :phone, 'CUSTOMER')
                            RETURNING id";
                    $stmt = $pdo->prepare($sql);
                    $params = [
                        'username' => $email, // you may want separate username field in future
                        'email' => $email,
                        'password' => $hashed_password,
                        'full_name' => $full_name,
                        'phone' => $phone
                    ];
                    $stmt->execute($params);
                    $insertId = false;

                    // For PostgreSQL using RETURNING id
                    $row = $stmt->fetch();
                    if ($row && isset($row['id'])) {
                        $insertId = $row['id'];
                    } else {
                        // fallback: try lastInsertId (may not work for Postgres)
                        try {
                            $lastId = $pdo->lastInsertId();
                            if ($lastId) $insertId = $lastId;
                        } catch (Exception $e) {
                            // ignore
                        }
                    }

                    if (!$insertId) {
                        throw new Exception('Không thể tạo tài khoản. Vui lòng thử lại.');
                    }

                    // Auto login - set session
                    if (session_status() === PHP_SESSION_NONE) session_start();
                    $_SESSION['user_id'] = $insertId;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['role'] = 'CUSTOMER';
                    $_SESSION['logged_in_at'] = time();

                    // Ghi log hoạt động (nếu bảng activity_logs tồn tại)
                    try {
                        $logSql = "INSERT INTO activity_logs (user_id, action, created_at) VALUES (:uid, 'REGISTER', NOW())";
                        $logStmt = $pdo->prepare($logSql);
                        $logStmt->execute(['uid' => $insertId]);
                    } catch (Exception $e) {
                        // Don't block registration if logging fails; just silently ignore
                        error_log('Activity log failed: ' . $e->getMessage());
                    }

                    // Redirect to homepage after successful registration
                    header('Location: ../index.php');
                    exit();
                }
            } catch (Exception $e) {
                $errors[] = "Lỗi hệ thống: " . $e->getMessage();
            }
        }
    }
}

// Include header AFTER processing POST to avoid headers already sent issues
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Đăng ký tài khoản</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên *</label>
                                <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu *</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100">Đăng ký</button>
                        </div>
                    </form>

                    <div class="text-center">
                        <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>