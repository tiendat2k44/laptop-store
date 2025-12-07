<?php
// File: index.php - Trang chủ chính
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php'; // QUAN TRỌNG: Phải include functions.php

$title = "LaptopStore - Bán Laptop Chính Hãng";
include 'includes/header.php';

// Lấy sản phẩm nổi bật
$featuredProducts = getFeaturedProducts(8);
$bestSelling = getBestSellingProducts(4);

// Lấy danh sách brand
$brands = [];
try {
    $sql = "SELECT DISTINCT brand FROM products WHERE is_active = true ORDER BY brand";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $brands = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Log error but continue
    error_log("Brands error: " . $e->getMessage());
}
?>

<!-- Hero Section -->
<div class="hero bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">Laptop Chính Hãng Giá Tốt</h1>
                <p class="lead mb-4">Khám phá bộ sưu tập laptop đa dạng từ Dell, Apple, Asus, Lenovo, HP với giá ưu đãi.</p>
                <a href="pages/products.php" class="btn btn-light btn-lg px-4">
                    <i class="fas fa-shopping-cart"></i> Mua Ngay
                </a>
            </div>
            <div class="col-lg-6 text-center">
                <img src="assets/images/laptop-hero.png" alt="Laptop" class="img-fluid" style="max-height: 300px;">
            </div>
        </div>
    </div>
</div>

<!-- Featured Products -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Sản Phẩm Nổi Bật</h2>
            <a href="pages/products.php" class="btn btn-outline-primary">Xem Tất Cả</a>
        </div>
        
        <?php if (empty($featuredProducts)): ?>
            <div class="alert alert-warning">
                Chưa có sản phẩm nổi bật. Vui lòng thêm sản phẩm trong admin.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card product-card h-100">
                        <div class="product-image-wrapper">
                            <img src="assets/images/products/<?php echo $product['image_url'] ?? 'default.jpg'; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="height: 200px; object-fit: contain; padding: 10px;">
                            <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                            <span class="product-badge badge bg-danger">
                                -<?php echo round(($product['price'] - $product['discount_price']) / $product['price'] * 100); ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title product-name" title="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h5>
                            <p class="card-text">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['brand']); ?></span>
                                <span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span>
                            </p>
                            
                            <div class="product-price mb-3">
                                <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                                    <span class="current-price text-danger fw-bold">
                                        <?php echo formatPrice($product['discount_price']); ?>
                                    </span>
                                    <del class="old-price text-muted">
                                        <?php echo formatPrice($product['price']); ?>
                                    </del>
                                <?php else: ?>
                                    <span class="current-price fw-bold">
                                        <?php echo formatPrice($product['price']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-stock mb-3">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i> Còn <?php echo $product['stock_quantity']; ?> sp
                                    </span>
                                <?php else: ?>
                                    <span class="text-danger">
                                        <i class="fas fa-times-circle"></i> Hết hàng
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="pages/product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> Xem Chi Tiết
                                </a>
                                
                                <?php if (isLoggedIn() && $product['stock_quantity'] > 0): ?>
                                <form method="POST" action="pages/cart.php">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="fas fa-cart-plus"></i> Thêm Giỏ Hàng
                                    </button>
                                </form>
                                <?php elseif (!isLoggedIn()): ?>
                                <a href="pages/login.php" class="btn btn-outline-secondary btn-sm">
                                    Đăng nhập để mua
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Brands -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Thương Hiệu Hàng Đầu</h2>
        <div class="row justify-content-center">
            <?php foreach ($brands as $brand): ?>
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <a href="pages/products.php?brand=<?php echo urlencode($brand); ?>" 
                   class="brand-card d-block text-center p-3 bg-white rounded shadow-sm">
                    <div class="brand-icon mb-2">
                        <i class="fas fa-laptop fa-2x text-primary"></i>
                    </div>
                    <h6 class="brand-name mb-0"><?php echo htmlspecialchars($brand); ?></h6>
                    <small class="text-muted">Xem sản phẩm</small>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Tại Sao Chọn LaptopStore?</h2>
        <div class="row text-center">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-shield-alt fa-3x text-primary"></i>
                </div>
                <h5>Bảo Hành Chính Hãng</h5>
                <p class="text-muted">Bảo hành từ 12-36 tháng tại trung tâm ủy quyền</p>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-truck fa-3x text-primary"></i>
                </div>
                <h5>Giao Hàng Toàn Quốc</h5>
                <p class="text-muted">Miễn phí vận chuyển cho đơn từ 5 triệu</p>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-credit-card fa-3x text-primary"></i>
                </div>
                <h5>Thanh Toán Linh Hoạt</h5>
                <p class="text-muted">COD, chuyển khoản, trả góp 0% lãi suất</p>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-headset fa-3x text-primary"></i>
                </div>
                <h5>Hỗ Trợ 24/7</h5>
                <p class="text-muted">Đội ngũ tư vấn chuyên nghiệp, nhiệt tình</p>
            </div>
        </div>
    </div>
</section>

<!-- Best Selling -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <h2 class="text-center mb-4">Sản Phẩm Bán Chạy</h2>
        <div class="row">
            <?php foreach ($bestSelling as $product): ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card bg-dark text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="card-text">Đã bán: <?php echo $product['sold_count']; ?> sản phẩm</p>
                        <a href="pages/product-detail.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-light btn-sm">Xem Ngay</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>