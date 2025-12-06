<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('/pages/index.php');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $result = $auth->login($email, $password, $remember);
        
        if ($result['success']) {
            // Check if there's a redirect URL
            $redirectUrl = $_SESSION['redirect_after_login'] ?? '/pages/index.php';
            unset($_SESSION['redirect_after_login']);
            
            addSuccessMessage('Đăng nhập thành công! Chào mừng ' . $result['user']['full_name']);
            redirect($redirectUrl);
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Đăng nhập';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - LaptopStore</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .login-container {
            max-width: 450px;
            margin: 0 auto;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .form-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: white;
            padding: 14px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #64748b;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .register-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .password-toggle {
            cursor: pointer;
            background: white;
            border: 2px solid #e2e8f0;
            border-left: none;
        }
        
        .form-check-input:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-laptop fa-3x mb-3"></i>
                    <h1>Đăng Nhập</h1>
                    <p class="mb-0">Chào mừng trở lại với LaptopStore</p>
                </div>
                
                <div class="login-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Lỗi!</strong> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php
                    $messages = getFlashMessages();
                    if (!empty($messages['success'])):
                        foreach ($messages['success'] as $msg):
                    ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                    
                    <form method="POST" action="" id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   placeholder="example@email.com"
                                   required
                                   autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Mật khẩu
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password"
                                       placeholder="Nhập mật khẩu"
                                       required>
                                <span class="input-group-text password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="password-icon"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Ghi nhớ đăng nhập
                                </label>
                            </div>
                            <a href="#" class="text-decoration-none" onclick="alert('Chức năng quên mật khẩu đang được phát triển')">
                                Quên mật khẩu?
                            </a>
                        </div>
                        
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt"></i> Đăng Nhập
                        </button>
                    </form>
                    
                    <div class="divider">
                        <span>hoặc</span>
                    </div>
                    
                    <div class="register-link">
                        <p class="mb-0">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-muted">
                            <i class="fas fa-arrow-left"></i> Quay về trang chủ
                        </a>
                    </div>
                    
                    <!-- Demo accounts info -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <strong>Tài khoản demo:</strong><br>
                            Admin: admin@laptopstore.com / admin123<br>
                            Customer: customer1@example.com / 123456
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const field = document.getElementById('password');
            const icon = document.getElementById('password-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
?>