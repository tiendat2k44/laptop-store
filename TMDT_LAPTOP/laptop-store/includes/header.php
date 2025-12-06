<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? SITE_NAME; ?></title>
    <meta name="description" content="<?php echo $metaDescription ?? 'Mua laptop chính hãng'; ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">
    <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<header class="main-header">
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/pages/index.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/products.php">Sản phẩm</a>
                    </li>
                </ul>
                <form class="search-form d-flex" action="<?php echo SITE_URL; ?>/pages/products.php" method="GET">
                    <input class="form-control me-2" type="search" name="search" placeholder="Tìm kiếm sản phẩm" aria-label="Search">
                    <button class="btn btn-outline-primary" type="submit">Tìm</button>
                </form>
                <ul class="navbar-nav ms-3">
                    <li class="nav-item">
                        <a class="nav-link cart-icon" href="<?php echo SITE_URL; ?>/pages/cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count"><?php echo $cartProcessor->getCartCount(); ?></span>
                        </a>
                    </li>
                    <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/profile.php">Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/orders.php">Đơn hàng</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/logout.php">Đăng xuất</a></li>
                        </ul>
                    </li>
                    <?php if ($auth->isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/index.php">Admin</a>
                    </li>
                    <?php endif; ?>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/login.php">Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/register.php">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
<?php
// Hiển thị flash messages
$flash = getFlashMessages();
if (!empty($flash)) {
    foreach ($flash as $type => $messages) {
        foreach ($messages as $message) {
            echo '<div class="container mt-3"><div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
                . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div></div>';
        }
    }
}
?>