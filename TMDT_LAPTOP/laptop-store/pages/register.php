<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('/pages/index.php');
}

$errors = [];
$formData = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'email' => clean($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'full_name' => clean($_POST['full_name'] ?? ''),
        'phone' => clean($_POST['phone'] ?? ''),
        'address' => clean($_POST['address'] ?? '')
    ];
    
    $result = $auth->register($formData);
    
    if ($result['success']) {
        addSuccessMessage('Đăng ký thành công! Chào mừng bạn đến với LaptopStore.');
        redirect('/pages/index.php');
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Đăng ký tài khoản';
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
        
        .register-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .register-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .register-body {
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
        
        .form-control.is-invalid {
            border-color: #ef4444;
        }
        
        .invalid-feedback {
            display: block;
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 4px;
        }
        
        .btn-register {
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
        
        .btn-register:hover {
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
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .input-group-text {
            background: white;
            border: 2px solid #e2e8f0;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .password-toggle {
            cursor: pointer;
            background: white;
            border: 2px solid #e2e8f0;
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <i class="fas fa-laptop fa-3x mb-3"></i>
                    <h1>Đăng Ký Tài Khoản</h1>
                    <p class="mb-0">Tạo tài khoản để mua sắm tại LaptopStore</p>
                </div>
                
                <div class="register-body">
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $field => $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email *
                            </label>
                            <input type="email" 
                                   class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                   placeholder="example@email.com"
                                   required>
                            <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">
                                <i class="fas fa-user"></i> Họ và tên *
                            </label>
                            <input type="text" 
                                   class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>"
                                   placeholder="Nguyễn Văn A"
                                   required>
                            <?php if (isset($errors['full_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Mật khẩu *
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           id="password" 
                                           name="password"
                                           placeholder="Tối thiểu 6 ký tự"
                                           required>
                                    <span class="input-group-text password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                    </span>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock"></i> Xác nhận mật khẩu *
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           id="confirm_password" 
                                           name="confirm_password"
                                           placeholder="Nhập lại mật khẩu"
                                           required>
                                    <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye" id="confirm_password-icon"></i>
                                    </span>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['confirm_password']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i> Số điện thoại
                            </label>
                            <input type="tel" 
                                   class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>"
                                   placeholder="0901234567">
                            <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Địa chỉ
                            </label>
                            <textarea class="form-control" 
                                      id="address" 
                                      name="address" 
                                      rows="2"
                                      placeholder="Số nhà, đường, quận/huyện, tỉnh/thành phố"><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                Tôi đồng ý với <a href="#" target="_blank">Điều khoản sử dụng</a> và <a href="#" target="_blank">Chính sách bảo mật</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-register">
                            <i class="fas fa-user-plus"></i> Đăng Ký
                        </button>
                    </form>
                    
                    <div class="divider">
                        <span>hoặc</span>
                    </div>
                    
                    <div class="login-link">
                        <p class="mb-0">Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-muted">
                            <i class="fas fa-arrow-left"></i> Quay về trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
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
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Mật khẩu phải có ít nhất 6 ký tự!');
                return false;
            }
        });
    </script>
</body>
</html>
?>