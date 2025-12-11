<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fallback helpers if missing
if (!function_exists('getFlashMessages')) {
    function getFlashMessages(): array {
        $out = [];
        if (!empty($_SESSION['flash_message'])) {
            $out['info'][] = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
        }
        return $out;
    }
}

// Ensure $auth exists and has expected methods (best-effort)
if (!isset($auth) || !is_object($auth)) {
    // provide lightweight fallback to avoid fatal errors (read-only)
    $auth = new class {
        public function isLoggedIn() { return !empty($_SESSION['user_id']); }
        public function isAdmin() { return (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'); }
        public function getUserName() { return $_SESSION['user_name'] ?? ''; }
    };
}

// Ensure cartProcessor exists (fallback uses session cart)
if (!isset($cartProcessor) || !is_object($cartProcessor) || !method_exists($cartProcessor, 'getCartCount')) {
    $cartProcessor = new class {
        public function getCartCount(): int {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                return array_sum(array_map(fn($it) => (int)($it['quantity'] ?? 0), $_SESSION['cart']));
            }
            return 0;
        }
    };
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription ?? 'Mua laptop chính hãng', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/responsive.css">
    <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/admin.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<header class="main-header">
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/index.php" data-prevent-double-click="true"><?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/products.php" data-prevent-double-click="true">Sản phẩm</a>
                    </li>
                </ul>
                <form class="search-form d-flex" action="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/products.php" method="GET">
                    <input class="form-control me-2" type="search" name="search" placeholder="Tìm kiếm sản phẩm" aria-label="Search">
                    <button class="btn btn-outline-primary" type="submit" data-prevent-double-click="true">Tìm</button>
                </form>
                <ul class="navbar-nav ms-3">
                    <li class="nav-item">
                        <a class="nav-link cart-icon" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/cart.php" data-prevent-double-click="true">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count"><?php echo (int)$cartProcessor->getCartCount(); ?></span>
                        </a>
                    </li>
                    <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($auth->getUserName(), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/profile.php" data-prevent-double-click="true">Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/orders.php" data-prevent-double-click="true">Đơn hàng</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/logout.php" data-prevent-double-click="true">Đăng xuất</a></li>
                        </ul>
                    </li>
                    <?php if ($auth->isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/index.php" data-prevent-double-click="true">Admin</a>
                    </li>
                    <?php endif; ?>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/login.php" data-prevent-double-click="true">Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/register.php" data-prevent-double-click="true">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<script>
(function() {
    function disableAfterClick(e) {
        var el = e.currentTarget;
        if (el.hasAttribute('data-clicked')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        el.setAttribute('data-clicked', 'true');
        if (el.tagName === 'BUTTON' || el.tagName === 'INPUT') {
            el.disabled = true;
        }
        // Re-enable after navigation or short timeout (fallback)
        setTimeout(function() {
            el.removeAttribute('data-clicked');
            if (el.tagName === 'BUTTON' || el.tagName === 'INPUT') {
                el.disabled = false;
            }
        }, 3000);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        var elements = document.querySelectorAll('[data-prevent-double-click="true"]');
        elements.forEach(function(el) {
            el.addEventListener('click', disableAfterClick);
        });
        
        // Also prevent double-submit on search form
        var searchForms = document.querySelectorAll('form.search-form');
        searchForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var btn = form.querySelector('button[type="submit"]');
                if (btn && btn.hasAttribute('data-submitted')) {
                    e.preventDefault();
                    return false;
                }
                if (btn) {
                    btn.setAttribute('data-submitted', 'true');
                    btn.disabled = true;
                }
            });
        });
    });
})();
</script>

<?php
// Hiển thị flash messages
$flash = getFlashMessages();
if (!empty($flash)) {
    foreach ($flash as $type => $messages) {
        foreach ($messages as $message) {
            echo '<div class="container mt-3"><div class="alert alert-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . ' alert-dismissible fade show" role="alert">'
                . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div></div>';
        }
    }
}
?>