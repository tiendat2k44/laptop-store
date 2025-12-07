<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/payment_functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect('cart.php');
}

// Xử lý thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = sanitize($_POST['payment_method']);
    $shippingAddress = sanitize($_POST['shipping_address']);
    $phone = sanitize($_POST['phone']);
    $customerName = sanitize($_POST['customer_name']);
    
    try {
        // Tạo đơn hàng
        $orderCode = generateOrderCode();
        $totalAmount = getCartTotal();
        
        $sql = "INSERT INTO orders (order_code, user_id, total_amount, shipping_address, phone, customer_name, email, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $orderCode,
            $_SESSION['user_id'],
            $totalAmount,
            $shippingAddress,
            $phone,
            $customerName,
            $_SESSION['user_email'],
            $paymentMethod
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Lưu chi tiết đơn hàng
        $productIds = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        
        $sql = "SELECT id, name, price FROM products WHERE id IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $stmt->execute($productIds);
        $products = $stmt->fetchAll();
        
        $productMap = [];
        foreach ($products as $product) {
            $productMap[$product['id']] = $product;
        }
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            if (isset($productMap[$productId])) {
                $product = $productMap[$productId];
                $total = $product['price'] * $quantity;
                
                $sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, total) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $orderId,
                    $productId,
                    $product['name'],
                    $quantity,
                    $product['price'],
                    $total
                ]);
            }
        }
        
        // Xử lý thanh toán
        $paymentResult = PaymentProcessor::processPayment($orderId, $paymentMethod, $totalAmount);
        
        if ($paymentResult['success']) {
            // Xóa giỏ hàng
            unset($_SESSION['cart']);
            
            // Chuyển đến trang thành công
            $_SESSION['order_success'] = [
                'order_id' => $orderId,
                'order_code' => $orderCode,
                'transaction_id' => $paymentResult['transaction_id']
            ];
            
            redirect('order-success.php');
        } else {
            $error = $paymentResult['message'];
        }
        
    } catch (Exception $e) {
        $error = "Lỗi đặt hàng: " . $e->getMessage();
    }
}

$title = "Thanh Toán";
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8">
            <h2 class="mb-4">Thông Tin Thanh Toán</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Họ và tên *</label>
                    <input type="text" class="form-control" name="customer_name" 
                           value="<?php echo $_SESSION['user_name'] ?? ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Số điện thoại *</label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?php echo $_SESSION['user_phone'] ?? ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Địa chỉ giao hàng *</label>
                    <textarea class="form-control" name="shipping_address" rows="3" required></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Phương thức thanh toán *</label>
                    <select class="form-select" name="payment_method" required>
                        <option value="">Chọn phương thức</option>
                        <?php foreach (PaymentProcessor::getPaymentMethods() as $method): ?>
                        <option value="<?php echo $method['id']; ?>">
                            <?php echo $method['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-check-circle"></i> Xác Nhận Đặt Hàng
                </button>
            </form>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Đơn Hàng Của Bạn</h5>
                </div>
                <div class="card-body">
                    <?php
                    $total = 0;
                    if (isset($_SESSION['cart'])) {
                        $productIds = array_keys($_SESSION['cart']);
                        if (!empty($productIds)) {
                            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                            $sql = "SELECT id, name, price FROM products WHERE id IN ($placeholders)";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($productIds);
                            $products = $stmt->fetchAll();
                            
                            $productMap = [];
                            foreach ($products as $product) {
                                $productMap[$product['id']] = $product;
                            }
                            
                            foreach ($_SESSION['cart'] as $productId => $quantity) {
                                if (isset($productMap[$productId])) {
                                    $product = $productMap[$productId];
                                    $subtotal = $product['price'] * $quantity;
                                    $total += $subtotal;
                                    ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo $product['name']; ?> x<?php echo $quantity; ?></span>
                                        <span><?php echo formatPrice($subtotal); ?></span>
                                    </div>
                                    <?php
                                }
                            }
                        }
                    }
                    ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Tổng cộng:</span>
                        <span class="text-danger"><?php echo formatPrice($total); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h6>Lưu ý quan trọng:</h6>
                    <ul class="small">
                        <li>Đơn hàng sẽ được xử lý trong vòng 24h</li>
                        <li>Vui lòng kiểm tra hàng trước khi thanh toán</li>
                        <li>Mọi giao dịch đều được ghi nhận vào hệ thống thu chi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>