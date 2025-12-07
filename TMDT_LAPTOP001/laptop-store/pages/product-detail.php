<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$productId = $_GET['id'] ?? 0;
$title = "Chi tiết sản phẩm";

if (!$productId) {
    header('Location: products.php');
    exit();
}

// Lấy thông tin sản phẩm
$sql = "SELECT * FROM products WHERE id = ? AND is_active = true";
$stmt = $db->prepare($sql);
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

// Tăng view count
$sql = "UPDATE products SET view_count = view_count + 1 WHERE id = ?";
$db->prepare($sql)->execute([$productId]);

// Lấy sản phẩm liên quan
$sql = "SELECT * FROM products 
        WHERE brand = ? AND id != ? AND is_active = true 
        LIMIT 4";
$stmt = $db->prepare($sql);
$stmt->execute([$product['brand'], $productId]);
$relatedProducts = $stmt->fetchAll();

// Parse specifications JSON
$specifications = json_decode($product['specifications'] ?? '{}', true);

$title = $product['name'] . " - LaptopStore";
include '../includes/header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6">
            <div class="product-image-main mb-3">
                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_url'] ?: 'default.jpg'); ?>" 
                     class="img-fluid rounded" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="max-height: 400px; object-fit: contain;">
            </div>
            
            <!-- Additional images (if available) -->
            <?php if (!empty($product['image_urls'])): ?>
            <div class="row">
                <?php 
                $images = json_decode($product['image_urls'], true) ?: [];
                foreach ($images as $image): 
                ?>
                <div class="col-3">
                    <img src="../assets/images/products/<?php echo htmlspecialchars($image); ?>" 
                         class="img-thumbnail" style="height: 80px; object-fit: cover;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Info -->
        <div class="col-md-6">
            <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="mb-3">
                <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($product['brand']); ?></span>
                <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($product['category']); ?></span>
                <?php if ($product['rating'] > 0): ?>
                <span class="badge bg-warning text-dark fs-6">
                    <i class="fas fa-star"></i> <?php echo number_format($product['rating'], 1); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Price -->
            <div class="mb-4">
                <?php if ($product['discount_price']): ?>
                    <h2 class="text-danger fw-bold"><?php echo formatPrice($product['discount_price']); ?></h2>
                    <del class="text-muted fs-5"><?php echo formatPrice($product['price']); ?></del>
                    <span class="badge bg-danger fs-6">
                        Giảm <?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>%
                    </span>
                <?php else: ?>
                    <h2 class="fw-bold"><?php echo formatPrice($product['price']); ?></h2>
                <?php endif; ?>
            </div>
            
            <!-- Stock Status -->
            <div class="mb-4">
                <?php if ($product['stock_quantity'] > 0): ?>
                    <p class="text-success">
                        <i class="fas fa-check-circle"></i> 
                        Còn <?php echo $product['stock_quantity']; ?> sản phẩm trong kho
                    </p>
                <?php else: ?>
                    <p class="text-danger">
                        <i class="fas fa-times-circle"></i> 
                        Tạm hết hàng
                    </p>
                <?php endif; ?>
                
                <?php if ($product['warranty_months']): ?>
                    <p><i class="fas fa-shield-alt"></i> Bảo hành: <?php echo $product['warranty_months']; ?> tháng</p>
                <?php endif; ?>
            </div>
            
            <!-- Add to Cart Form -->
            <?php if ($auth->isLoggedIn() && $product['stock_quantity'] > 0): ?>
            <form method="POST" action="cart.php" class="mb-4">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Số lượng</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" 
                               max="<?php echo $product['stock_quantity']; ?>">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                            </button>
                            <button type="button" class="btn btn-success btn-lg" id="buy-now">
                                <i class="fas fa-bolt"></i> Mua ngay
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <?php elseif (!$auth->isLoggedIn()): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> 
                Vui lòng <a href="login.php">đăng nhập</a> để mua hàng
            </div>
            <?php endif; ?>
            
            <!-- Short Description -->
            <?php if ($product['description']): ?>
            <div class="mb-4">
                <h5>Mô tả ngắn</h5>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Product Details Tabs -->
    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="specs-tab" data-bs-toggle="tab" 
                            data-bs-target="#specs" type="button" role="tab">
                        Thông số kỹ thuật
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="description-tab" data-bs-toggle="tab" 
                            data-bs-target="#description" type="button" role="tab">
                        Mô tả chi tiết
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" 
                            data-bs-target="#reviews" type="button" role="tab">
                        Đánh giá (<?php echo $product['review_count']; ?>)
                    </button>
                </li>
            </ul>
            
            <div class="tab-content p-3 border border-top-0 rounded-bottom">
                <!-- Specifications -->
                <div class="tab-pane fade show active" id="specs" role="tabpanel">
                    <?php if (!empty($specifications)): ?>
                    <div class="row">
                        <?php foreach ($specifications as $key => $value): ?>
                        <div class="col-md-6 mb-2">
                            <strong><?php echo htmlspecialchars(ucfirst($key)); ?>:</strong>
                            <span><?php echo htmlspecialchars($value); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">Chưa có thông số kỹ thuật chi tiết.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Full Description -->
                <div class="tab-pane fade" id="description" role="tabpanel">
                    <?php if ($product['full_description']): ?>
                        <?php echo nl2br(htmlspecialchars($product['full_description'])); ?>
                    <?php else: ?>
                        <p class="text-muted">Chưa có mô tả chi tiết.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Reviews -->
                <div class="tab-pane fade" id="reviews" role="tabpanel">
                    <?php
                    $sql = "SELECT pr.*, u.full_name, u.avatar_url 
                            FROM product_reviews pr 
                            JOIN users u ON pr.user_id = u.id 
                            WHERE pr.product_id = ? AND pr.is_approved = true 
                            ORDER BY pr.created_at DESC";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$productId]);
                    $reviews = $stmt->fetchAll();
                    ?>
                    
                    <?php if (empty($reviews)): ?>
                    <p class="text-muted">Chưa có đánh giá nào cho sản phẩm này.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-item mb-3 pb-3 border-bottom">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <?php if ($review['avatar_url']): ?>
                                    <img src="<?php echo htmlspecialchars($review['avatar_url']); ?>" 
                                         class="rounded-circle" width="50" height="50">
                                    <?php else: ?>
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <span class="text-white"><?php echo substr($review['full_name'], 0, 1); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6><?php echo htmlspecialchars($review['full_name']); ?></h6>
                                    <div class="text-warning mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($review['title']): ?>
                                    <h6><?php echo htmlspecialchars($review['title']); ?></h6>
                                    <?php endif; ?>
                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="mb-4">Sản phẩm liên quan</h3>
            <div class="row">
                <?php foreach ($relatedProducts as $related): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100">
                        <img src="../assets/images/products/<?php echo htmlspecialchars($related['image_url'] ?: 'default.jpg'); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($related['name']); ?>"
                             style="height: 150px; object-fit: contain;">
                        
                        <div class="card-body">
                            <h6 class="card-title">
                                <a href="product-detail.php?id=<?php echo $related['id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </a>
                            </h6>
                            
                            <div class="product-price">
                                <?php if ($related['discount_price']): ?>
                                    <span class="text-danger fw-bold"><?php echo formatPrice($related['discount_price']); ?></span>
                                <?php else: ?>
                                    <span class="fw-bold"><?php echo formatPrice($related['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Buy Now button
document.getElementById('buy-now').addEventListener('click', function() {
    const form = document.querySelector('form');
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'buy_now';
    actionInput.value = '1';
    form.appendChild(actionInput);
    form.submit();
});
</script>

<?php include '../includes/footer.php'; ?>