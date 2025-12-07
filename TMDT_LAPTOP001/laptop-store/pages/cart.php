<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$title = "Giỏ hàng - LaptopStore";

/**
 * CSRF token
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/**
 * If cart helper functions are not provided in includes/functions.php,
 * define minimal safe implementations here (session-based).
 * These implementations will try to fetch product details from DB if possible,
 * otherwise use placeholders.
 */
if (!function_exists('getProductFromDB')) {
    function getProductFromDB($productId)
    {
        // Try to use PDO from config if available
        if (function_exists('get_pdo')) {
            try {
                $pdo = get_pdo();
                $stmt = $pdo->prepare('SELECT id, name, brand, image_url, price, discount_price, stock_quantity FROM products WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $productId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) return $row;
            } catch (\Exception $e) {
                // ignore, fallback to placeholder
            }
        }
        // Fallback placeholder (minimal fields)
        return [
            'id' => $productId,
            'name' => 'Sản phẩm #' . htmlspecialchars((string)$productId),
            'brand' => '',
            'image_url' => 'default.jpg',
            'price' => 0,
            'discount_price' => null,
            'stock_quantity' => 0,
        ];
    }
}

if (!function_exists('addToCart')) {
    function addToCart($productId, $quantity = 1)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $productId = (string)$productId;
        $quantity = max(1, (int)$quantity);

        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        // If product already in cart, increase quantity
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $product = getProductFromDB($productId);
            $_SESSION['cart'][$productId] = [
                'product' => $product,
                'quantity' => $quantity,
            ];
        }
    }
}

if (!function_exists('updateCartItem')) {
    function updateCartItem($productId, $quantity)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $productId = (string)$productId;
        $quantity = max(0, (int)$quantity);
        if (!isset($_SESSION['cart'])) return;
        if (!isset($_SESSION['cart'][$productId])) return;
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        }
    }
}

if (!function_exists('removeFromCart')) {
    function removeFromCart($productId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $productId = (string)$productId;
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
    }
}

if (!function_exists('clearCart')) {
    function clearCart()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['cart'] = [];
    }
}

if (!function_exists('getCartItems')) {
    function getCartItems(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $items = $_SESSION['cart'] ?? [];
        // Ensure products exist for each item
        foreach ($items as $pid => &$it) {
            if (empty($it['product'])) {
                $it['product'] = getProductFromDB($pid);
            }
        }
        return $items;
    }
}

if (!function_exists('calculateCartTotal')) {
    function calculateCartTotal(): int
    {
        $items = getCartItems();
        $total = 0;
        foreach ($items as $it) {
            $product = $it['product'] ?? [];
            $qty = (int)($it['quantity'] ?? 0);
            $price = $product['discount_price'] ?? $product['price'] ?? 0;
            $total += ((int)$price) * $qty;
        }
        return $total;
    }
}

/**
 * Xử lý actions (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF validation
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], (string)$postedToken)) {
        // Invalid request
        $_SESSION['flash_error'] = 'Yêu cầu không hợp lệ (CSRF).';
        header('Location: cart.php');
        exit();
    }

    $action = $_POST['action'] ?? '';
    $productId = $_POST['product_id'] ?? ($_POST['cart_item_id'] ?? 0);
    $quantity = $_POST['quantity'] ?? 1;

    switch ($action) {
        case 'add':
            addToCart($productId, $quantity);
            if (isset($_POST['buy_now'])) {
                header('Location: checkout.php');
                exit();
            }
            break;

        case 'update':
            if (isset($_POST['cart_item_id'])) {
                updateCartItem($_POST['cart_item_id'], $quantity);
            }
            break;

        case 'remove':
            if (isset($_POST['cart_item_id'])) {
                removeFromCart($_POST['cart_item_id']);
            }
            break;

        case 'clear':
            clearCart();
            break;
    }

    header('Location: cart.php');
    exit();
}

/**
 * Lấy giỏ hàng & tổng
 */
$cartItems = getCartItems();
$cartTotal = calculateCartTotal();

include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4"><?php echo htmlspecialchars($title); ?></h1>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            <i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn đang trống.
            <a href="../products.php" class="alert-link">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>

    <div class="row">
        <!-- Cart Items -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <form method="post" action="cart.php" id="cart-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Đơn giá</th>
                                        <th style="width:140px">Số lượng</th>
                                        <th>Thành tiền</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $productId => $item):
                                        $product = $item['product'] ?? $item;
                                        $quantity = (int)($item['quantity'] ?? 1);
                                        $price = isset($product['discount_price']) && $product['discount_price'] !== null ? (int)$product['discount_price'] : (int)($product['price'] ?? 0);
                                        $lineTotal = $price * $quantity;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars(getProductImage($product['image_url'] ?? 'default.jpg')); ?>"
                                                     class="img-thumbnail me-3" alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                                                     style="width:80px;height:80px;object-fit:contain;">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($product['name'] ?? ''); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['brand'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <?php if (!empty($product['discount_price'])): ?>
                                                <div>
                                                    <span class="text-danger fw-bold"><?php echo formatPrice((int)$product['discount_price']); ?></span>
                                                    <div><del class="text-muted small"><?php echo formatPrice((int)$product['price']); ?></del></div>
                                                </div>
                                            <?php else: ?>
                                                <div><?php echo formatPrice((int)($product['price'] ?? 0)); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="input-group">
                                                <form method="post" action="cart.php" class="d-flex align-items-center update-form">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($productId); ?>">
                                                    <input type="number" name="quantity" class="form-control form-control-sm" min="1" value="<?php echo $quantity; ?>" style="width:80px;">
                                                    <button class="btn btn-outline-secondary btn-sm ms-2" type="submit">Cập nhật</button>
                                                </form>
                                            </div>
                                        </td>
                                        <td class="align-middle"><?php echo formatPrice($lineTotal); ?></td>
                                        <td class="align-middle text-end">
                                            <form method="post" action="cart.php" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($productId); ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <form method="post" action="cart.php" onsubmit="return confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="clear">
                        <button class="btn btn-outline-danger btn-sm" type="submit"><i class="fas fa-trash-alt"></i> Xóa toàn bộ</button>
                    </form>

                    <a href="../products.php" class="btn btn-outline-secondary btn-sm">Tiếp tục mua sắm</a>
                </div>
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5>Tóm tắt đơn hàng</h5>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <div>Tạm tính</div>
                        <div><?php echo formatPrice($cartTotal); ?></div>
                    </div>
                    <!-- You can add shipping, discounts, taxes here -->
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <div>Tổng</div>
                        <div><?php echo formatPrice($cartTotal); ?></div>
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <form method="post" action="cart.php" id="buy-now-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="add">
                            <!-- buy_now will trigger immediate redirect to checkout in POST handler -->
                            <input type="hidden" name="buy_now" value="1">
                            <!-- If you want to purchase current cart as-is, you might simply redirect to checkout.php instead of posting add -->
                            <a href="checkout.php" class="btn btn-primary btn-lg">Tiến hành thanh toán</a>
                        </form>

                        <a href="pages/checkout.php" class="btn btn-outline-primary">Thanh toán với hình thức khác</a>
                    </div>
                </div>
            </div>

            <div class="mt-3 text-center">
                <small class="text-muted">Thanh toán an toàn — Bảo mật thông tin khách hàng.</small>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    // Optional: intercept update forms to send via AJAX for better UX
    // Keep simple for now; forms submit normally.
</script>
?>