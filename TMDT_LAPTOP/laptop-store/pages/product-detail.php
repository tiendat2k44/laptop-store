<?php
// Product detail
?>
<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/cart_functions.php';

if (!isset($_GET['id'])) {
    redirect('products.php');
}

$sql = "SELECT * FROM products WHERE id = :id AND is_active = true";
$product = $db->selectOne($sql, [':id' => (int)$_GET['id']]);

if (!$product) {
    redirect('products.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($cartProcessor->addToCart($product['id'], $quantity)) {
        addSuccessMessage('Thêm vào giỏ hàng thành công!');
    } else {
        addErrorMessage('Sản phẩm hết hàng!');
    }
    redirect(currentUrl());
}

$title = $product['name'];
include '../includes/header.php';
?>

<section class="product-detail py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo getProductImage($product['image_urls']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="badge bg-secondary"><?php echo htmlspecialchars($product['brand']); ?></p>
                <div class="product-price mb-3">
                    <?php if ($product['discount_price']): ?>
                    <span class="current-price text-danger fw-bold"><?php echo formatPrice($product['discount_price']); ?></span>
                    <del class="old-price text-muted"><?php echo formatPrice($product['price']); ?></del>
                    <?php else: ?>
                    <span class="current-price fw-bold"><?php echo formatPrice($product['price']); ?></span>
                    <?php endif; ?>
                </div>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <h5>Thông số kỹ thuật</h5>
                <ul>
                    <?php $specs = json_decode($product['specifications'], true); ?>
                    <?php if ($specs): ?>
                    <?php foreach ($specs as $key => $value): ?>
                    <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <form method="POST">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Số lượng</label>
                        <input type="number" class="form-control w-25" id="quantity" name="quantity" min="1" max="<?php echo $product['stock_quantity']; ?>" value="1">
                    </div>
                    <button type="submit" name="add_to_cart" class="btn btn-primary" <?php if ($product['stock_quantity'] <= 0): ?>disabled<?php endif; ?>>Thêm vào giỏ</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>