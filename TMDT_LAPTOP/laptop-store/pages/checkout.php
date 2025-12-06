<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/cart_functions.php';
require_once '../includes/payment_functions.php';

$auth = new Auth();
$auth->requireLogin();

$cart = new Cart();
$paymentProcessor = new PaymentProcessor();

// Validate cart
$validation = $cart->validateCart();
if (!$validation['valid']) {
    addErrorMessage(implode('<br>', $validation['errors']));
    redirect('/pages/cart.php');
}

$summary = $cart->getSummary();
$user = $auth->getCurrentUser();
$paymentMethods = $paymentProcessor->getPaymentMethods();

$errors = [];
$step = (int)($_GET['step'] ?? 1);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_order'])) {
        // Step 1: Create Order
        $orderData = [
            'customer_name' => clean($_POST['customer_name']),
            'email' => clean($_POST['email']),
            'phone' => clean($_POST['phone']),
            'shipping_address' => clean($_POST['shipping_address']),
            'billing_address' => clean($_POST['billing_address'] ?? $_POST['shipping_address']),
            'note' => clean($_POST['note'] ?? '')
        ];
        
        $result = $paymentProcessor->createOrder($orderData);
        
        if ($result['success']) {
            $_SESSION['pending_order_id'] = $result['order_id'];
            $_SESSION['pending_order_code'] = $result['order_code'];
            redirect('/pages/checkout.php?step=2');
        } else {
            addErrorMessage($result['message']);
        }
    } elseif (isset($_POST['process_payment'])) {
        // Step 2: Process Payment
        $orderId = $_SESSION['pending_order_id'] ?? 0;
        $paymentMethod = clean($_POST['payment_method']);
        
        $result = $paymentProcessor->processPayment($orderId, $paymentMethod);
        
        if ($result['success']) {
            unset($_SESSION['pending_order_id'], $_SESSION['pending_order_code']);
            addSuccessMessage('Đặt hàng thành công! Mã đơn hàng: ' . $result['order_code']);
            redirect('/pages/order-success.php?order=' . $result['order_code']);
        } else {
            addErrorMessage($result['message']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - LaptopStore</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .checkout-steps { display: flex; justify-content: center; margin: 30px 0; position: relative; }
        .checkout-steps::before { content: ''; position: absolute; top: 20px; left: 25%; right: 25%; height: 2px; background: #e2e8f0; z-index: 0; }
        .step { position: relative; z-index: 1; text-align: center; flex: 1; max-width: 200px; }
        .step-circle { width: 40px; height: 40px; background: #e2e8f0; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .step.active .step-circle { background: #2563eb; color: white; }
        .step.completed .step-circle { background: #10b981; color: white; }
        .payment-method { border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s; }
        .payment-method:hover { border-color: #2563eb; }
        .payment-method input[type="radio"]:checked + label { border-color: #2563eb; background: #eff6ff; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-laptop"></i> LaptopStore
            </a>
            <div>
                <a href="cart.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="text-center mb-4">Thanh Toán</h2>
        
        <!-- Progress Steps -->
        <div class="checkout-steps">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                <div class="step-circle"><?php echo $step > 1 ? '✓' : '1'; ?></div>
                <div class="step-label">Thông tin</div>
            </div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                <div class="step-circle"><?php echo $step > 2 ? '✓' : '2'; ?></div>
                <div class="step-label">Thanh toán</div>
            </div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                <div class="step-circle">3</div>
                <div class="step-label">Hoàn tất</div>
            </div>
        </div>
        
        <?php echo displayFlashMessages(); ?>
        
        <div class="row">
            <div class="col-lg-8">
                <?php if ($step === 1): ?>
                <!-- Step 1: Customer Information -->
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-4">Thông Tin Giao Hàng</h4>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Họ và tên *</label>
                                    <input type="text" class="form-control" name="customer_name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại *</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                       placeholder="0901234567" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ giao hàng *</label>
                                <textarea class="form-control" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                                <small class="text-muted">Vui lòng nhập đầy đủ: Số nhà, đường, quận/huyện, tỉnh/thành phố</small>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="sameBilling" checked 
                                       onchange="toggleBillingAddress()">
                                <label class="form-check-label" for="sameBilling">
                                    Địa chỉ thanh toán giống địa chỉ giao hàng
                                </label>
                            </div>
                            
                            <div id="billingAddress" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Địa chỉ thanh toán</label>
                                    <textarea class="form-control" name="billing_address" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ghi chú đơn hàng (tùy chọn)</label>
                                <textarea class="form-control" name="note" rows="2" 
                                          placeholder="Ghi chú về đơn hàng, ví dụ: thời gian hay chỉ dẫn địa điểm giao hàng chi tiết hơn"></textarea>
                            </div>
                            
                            <button type="submit" name="create_order" class="btn btn-primary btn-lg">
                                Tiếp tục đến thanh toán <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <?php elseif ($step === 2): ?>
                <!-- Step 2: Payment Method -->
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-4">Phương Thức Thanh Toán</h4>
                        <form method="POST" id="paymentForm">
                            <?php foreach ($paymentMethods as $method): ?>
                            <?php if ($method['enabled']): ?>
                            <div class="payment-method">
                                <input type="radio" class="form-check-input" name="payment_method" 
                                       value="<?php echo $method['code']; ?>" 
                                       id="payment_<?php echo $method['code']; ?>" required>
                                <label class="d-block w-100" for="payment_<?php echo $method['code']; ?>" style="cursor: pointer;">
                                    <div class="d-flex align-items-center">
                                        <i class="<?php echo $method['icon']; ?> fa-2x text-primary me-3"></i>
                                        <div>
                                            <strong><?php echo $method['name']; ?></strong>
                                            <p class="mb-0 text-muted small"><?php echo $method['description']; ?></p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <div class="mt-4">
                                <button type="submit" name="process_payment" class="btn btn-success btn-lg">
                                    <i class="fas fa-check-circle"></i> Hoàn Tất Đặt Hàng
                                </button>
                                <a href="checkout.php?step=1" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm" style="position: sticky; top: 20px;">
                    <div class="card-body">
                        <h5 class="mb-4">Đơn Hàng (<?php echo $summary['quantity_count']; ?> sản phẩm)</h5>
                        
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($summary['items'] as $item): ?>
                            <div class="d-flex mb-3">
                                <img src="<?php echo getProductImage($item['image_url']); ?>" 
                                     style="width: 60px; height: 60px; object-fit: contain;" class="me-3">
                                <div class="flex-grow-1">
                                    <div class="small"><?php echo htmlspecialchars(truncate($item['name'], 40)); ?></div>
                                    <div class="text-muted small">SL: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold"><?php echo formatPrice($item['item_total']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <span><?php echo formatPrice($summary['subtotal']); ?></span>
                        </div>
                        
                        <?php if ($summary['discount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Giảm giá:</span>
                            <span>-<?php echo formatPrice($summary['discount']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Phí vận chuyển:</span>
                            <span>
                                <?php if ($summary['shipping_fee'] > 0): ?>
                                    <?php echo formatPrice($summary['shipping_fee']); ?>
                                <?php else: ?>
                                    <span class="text-success">Miễn phí</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <strong class="fs-5">Tổng cộng:</strong>
                            <strong class="fs-4 text-danger"><?php echo formatPrice($summary['total']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleBillingAddress() {
            const checkbox = document.getElementById('sameBilling');
            const billing = document.getElementById('billingAddress');
            billing.style.display = checkbox.checked ? 'none' : 'block';
        }
        
        // Confirm before payment
        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            if (!confirm('Xác nhận hoàn tất đơn hàng?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
?>