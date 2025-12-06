<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cart_functions.php';

/**
 * Payment Processing Class
 */
class PaymentProcessor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create order from cart
     */
    public function createOrder($orderData) {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
        }
        
        $userId = $_SESSION['user_id'];
        $cart = new Cart($userId);
        
        // Validate cart
        $validation = $cart->validateCart();
        if (!$validation['valid']) {
            return ['success' => false, 'message' => implode(', ', $validation['errors'])];
        }
        
        // Get cart summary
        $summary = $cart->getSummary();
        
        try {
            $this->db->beginTransaction();
            
            // Generate order code
            $orderCode = generateOrderCode();
            
            // Create order
            $sql = "INSERT INTO orders (
                        order_code, user_id, total_amount, discount_amount, 
                        shipping_fee, final_amount, status, shipping_address, 
                        billing_address, phone, customer_name, email, note,
                        estimated_delivery_date, created_at
                    ) VALUES (
                        :order_code, :user_id, :total_amount, :discount_amount,
                        :shipping_fee, :final_amount, 'PENDING', :shipping_address,
                        :billing_address, :phone, :customer_name, :email, :note,
                        :estimated_delivery, NOW()
                    ) RETURNING id";
            
            $estimatedDelivery = date('Y-m-d', strtotime('+3 days'));
            
            $orderResult = $this->db->selectOne($sql, [
                ':order_code' => $orderCode,
                ':user_id' => $userId,
                ':total_amount' => $summary['subtotal'],
                ':discount_amount' => $summary['discount'],
                ':shipping_fee' => $summary['shipping_fee'],
                ':final_amount' => $summary['total'],
                ':shipping_address' => $orderData['shipping_address'],
                ':billing_address' => $orderData['billing_address'] ?? $orderData['shipping_address'],
                ':phone' => $orderData['phone'],
                ':customer_name' => $orderData['customer_name'],
                ':email' => $orderData['email'],
                ':note' => $orderData['note'] ?? '',
                ':estimated_delivery' => $estimatedDelivery
            ]);
            
            if (!$orderResult) {
                throw new Exception('Không thể tạo đơn hàng');
            }
            
            $orderId = $orderResult['id'];
            
            // Create order items
            foreach ($summary['items'] as $item) {
                $sql = "INSERT INTO order_items (
                            order_id, product_id, product_name, product_image,
                            specifications, quantity, unit_price, total_price
                        ) VALUES (
                            :order_id, :product_id, :product_name, :product_image,
                            :specifications, :quantity, :unit_price, :total_price
                        )";
                
                $specs = $this->db->selectOne(
                    "SELECT specifications FROM products WHERE id = :id",
                    [':id' => $item['product_id']]
                );
                
                $this->db->insert($sql, [
                    ':order_id' => $orderId,
                    ':product_id' => $item['product_id'],
                    ':product_name' => $item['name'],
                    ':product_image' => $item['image_url'],
                    ':specifications' => $specs['specifications'] ?? null,
                    ':quantity' => $item['quantity'],
                    ':unit_price' => $item['current_price'],
                    ':total_price' => $item['item_total']
                ]);
            }
            
            // Clear cart
            $cart->clearCart();
            
            // Log activity
            logActivity($userId, 'ORDER_CREATED', "Created order #$orderCode");
            
            $this->db->commit();
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_code' => $orderCode,
                'message' => 'Đơn hàng đã được tạo thành công'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process payment for order
     */
    public function processPayment($orderId, $paymentMethod, $paymentData = []) {
        try {
            $this->db->beginTransaction();
            
            // Validate order
            $order = $this->validateOrder($orderId);
            if (!$order) {
                throw new Exception("Đơn hàng không hợp lệ");
            }
            
            // Check stock availability
            $this->checkStockAvailability($orderId);
            
            // Process payment by method
            $paymentResult = $this->processPaymentByMethod(
                $paymentMethod, 
                $order['final_amount'], 
                $paymentData
            );
            
            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['message']);
            }
            
            // Update order status
            $this->updateOrderStatus($orderId, 'CONFIRMED');
            
            // Create payment record
            $paymentId = $this->createPaymentRecord(
                $orderId,
                $paymentMethod,
                $order['final_amount'],
                $paymentResult['transaction_id'],
                'COMPLETED'
            );
            
            // Update stock quantity
            $this->updateProductStock($orderId);
            
            // Create financial record
            $this->createFinancialRecord(
                $orderId,
                $paymentId,
                $order['final_amount'],
                $paymentMethod
            );
            
            // Log activity
            logActivity($order['user_id'], 'PAYMENT_SUCCESS', "Payment completed for order #{$order['order_code']}");
            
            $this->db->commit();
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'transaction_id' => $paymentResult['transaction_id'],
                'order_code' => $order['order_code'],
                'message' => 'Thanh toán thành công'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Payment processing error: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate order
     */
    private function validateOrder($orderId) {
        $sql = "SELECT o.*, u.email, u.full_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = :order_id 
                AND o.user_id = :user_id 
                AND o.status = 'PENDING'";
        
        return $this->db->selectOne($sql, [
            ':order_id' => $orderId,
            ':user_id' => $_SESSION['user_id']
        ]);
    }
    
    /**
     * Check stock availability
     */
    private function checkStockAvailability($orderId) {
        $sql = "SELECT oi.product_id, oi.quantity, p.name, p.stock_quantity 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = :order_id";
        
        $items = $this->db->select($sql, [':order_id' => $orderId]);
        
        foreach ($items as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                throw new Exception("Sản phẩm '{$item['name']}' không đủ hàng trong kho");
            }
        }
        
        return true;
    }
    
    /**
     * Process payment by method
     */
    private function processPaymentByMethod($method, $amount, $data) {
        switch ($method) {
            case 'COD':
                return $this->processCOD();
                
            case 'BANK_TRANSFER':
                return $this->processBankTransfer($amount);
                
            case 'VNPAY':
                return $this->processVNPay($amount, $data);
                
            case 'MOMO':
                return $this->processMoMo($amount, $data);
                
            default:
                throw new Exception("Phương thức thanh toán không hợp lệ");
        }
    }
    
    /**
     * Process COD payment
     */
    private function processCOD() {
        return [
            'success' => true,
            'transaction_id' => 'COD-' . time() . '-' . rand(1000, 9999),
            'message' => 'Đơn hàng sẽ thanh toán khi nhận hàng'
        ];
    }
    
    /**
     * Process Bank Transfer
     */
    private function processBankTransfer($amount) {
        return [
            'success' => true,
            'transaction_id' => 'BANK-' . date('YmdHis') . rand(100, 999),
            'message' => 'Vui lòng chuyển khoản theo thông tin đã cung cấp'
        ];
    }
    
    /**
     * Process VNPay
     */
    private function processVNPay($amount, $data) {
        if (PAYMENT_TEST_MODE) {
            return [
                'success' => true,
                'transaction_id' => 'VNPAY-' . date('YmdHis') . rand(100, 999),
                'message' => 'Thanh toán VNPay thành công (Test Mode)'
            ];
        }
        
        // Real VNPay integration would go here
        throw new Exception("VNPay chưa được tích hợp");
    }
    
    /**
     * Process MoMo
     */
    private function processMoMo($amount, $data) {
        if (PAYMENT_TEST_MODE) {
            return [
                'success' => true,
                'transaction_id' => 'MOMO-' . date('YmdHis') . rand(100, 999),
                'message' => 'Thanh toán MoMo thành công (Test Mode)'
            ];
        }
        
        throw new Exception("MoMo chưa được tích hợp");
    }
    
    /**
     * Create payment record
     */
    private function createPaymentRecord($orderId, $method, $amount, $transactionId, $status) {
        $sql = "INSERT INTO payments (
                    order_id, payment_method, amount, status, transaction_id,
                    payment_date, ip_address, user_agent, created_at
                ) VALUES (
                    :order_id, :method, :amount, :status, :transaction_id,
                    NOW(), :ip, :ua, NOW()
                ) RETURNING id";
        
        $result = $this->db->selectOne($sql, [
            ':order_id' => $orderId,
            ':method' => $method,
            ':amount' => $amount,
            ':status' => $status,
            ':transaction_id' => $transactionId,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        return $result['id'];
    }
    
    /**
     * Update order status
     */
    private function updateOrderStatus($orderId, $status) {
        $sql = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id";
        return $this->db->update($sql, [':status' => $status, ':id' => $orderId]);
    }
    
    /**
     * Update product stock after order
     */
    private function updateProductStock($orderId) {
        $sql = "UPDATE products p SET 
                    stock_quantity = stock_quantity - oi.quantity,
                    sold_count = sold_count + oi.quantity
                FROM order_items oi 
                WHERE p.id = oi.product_id AND oi.order_id = :order_id";
        
        return $this->db->query("UPDATE products p SET 
                    stock_quantity = stock_quantity - oi.quantity,
                    sold_count = sold_count + oi.quantity
                FROM order_items oi 
                WHERE p.id = oi.product_id AND oi.order_id = $orderId");
    }
    
    /**
     * Create financial record
     */
    private function createFinancialRecord($orderId, $paymentId, $amount, $method) {
        $sql = "INSERT INTO financial_records (
                    record_type, amount, description, reference_id,
                    reference_type, payment_method, created_by, created_at
                ) VALUES (
                    'THU', :amount, :description, :ref_id,
                    'PAYMENT', :method, :user_id, NOW()
                )";
        
        $order = $this->db->selectOne("SELECT order_code FROM orders WHERE id = :id", [':id' => $orderId]);
        
        return $this->db->insert($sql, [
            ':amount' => $amount,
            ':description' => "Doanh thu từ đơn hàng #{$order['order_code']}",
            ':ref_id' => $paymentId,
            ':method' => $method,
            ':user_id' => $_SESSION['user_id']
        ]);
    }
    
    /**
     * Get payment methods
     */
    public function getPaymentMethods() {
        return [
            [
                'code' => 'COD',
                'name' => 'Thanh toán khi nhận hàng (COD)',
                'description' => 'Trả tiền mặt khi nhận được hàng',
                'icon' => 'fas fa-money-bill-wave',
                'enabled' => true
            ],
            [
                'code' => 'BANK_TRANSFER',
                'name' => 'Chuyển khoản ngân hàng',
                'description' => 'Chuyển khoản qua Internet Banking',
                'icon' => 'fas fa-university',
                'enabled' => true
            ],
            [
                'code' => 'VNPAY',
                'name' => 'VNPay',
                'description' => 'Thanh toán qua cổng VNPay',
                'icon' => 'fas fa-qrcode',
                'enabled' => PAYMENT_TEST_MODE
            ],
            [
                'code' => 'MOMO',
                'name' => 'Ví MoMo',
                'description' => 'Thanh toán qua ví điện tử MoMo',
                'icon' => 'fas fa-mobile-alt',
                'enabled' => PAYMENT_TEST_MODE
            ]
        ];
    }
    
    /**
     * Get order details
     */
    public function getOrderDetails($orderId) {
        $sql = "SELECT o.*, 
                       (SELECT json_agg(row_to_json(oi)) 
                        FROM (SELECT * FROM order_items WHERE order_id = o.id) oi) as items,
                       p.payment_method, p.transaction_id, p.status as payment_status
                FROM orders o
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE o.id = :id AND o.user_id = :user_id";
        
        return $this->db->selectOne($sql, [
            ':id' => $orderId,
            ':user_id' => $_SESSION['user_id']
        ]);
    }
    
    /**
     * Get user orders
     */
    public function getUserOrders($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT o.*, 
                       COUNT(oi.id) as item_count,
                       p.payment_method
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE o.user_id = :user_id
                GROUP BY o.id, p.payment_method
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        return $this->db->select($sql, [
            ':user_id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder($orderId, $reason = '') {
        try {
            $this->db->beginTransaction();
            
            // Validate order belongs to user and can be cancelled
            $order = $this->db->selectOne(
                "SELECT * FROM orders WHERE id = :id AND user_id = :user_id AND status IN ('PENDING', 'CONFIRMED')",
                [':id' => $orderId, ':user_id' => $_SESSION['user_id']]
            );
            
            if (!$order) {
                throw new Exception("Không thể hủy đơn hàng này");
            }
            
            // Restore stock
            $this->db->query("UPDATE products p SET 
                              stock_quantity = stock_quantity + oi.quantity,
                              sold_count = GREATEST(0, sold_count - oi.quantity)
                              FROM order_items oi 
                              WHERE p.id = oi.product_id AND oi.order_id = $orderId");
            
            // Update order status
            $sql = "UPDATE orders SET status = 'CANCELLED', cancelled_at = NOW(), note = :note WHERE id = :id";
            $this->db->update($sql, [':note' => $reason, ':id' => $orderId]);
            
            // Log activity
            logActivity($_SESSION['user_id'], 'ORDER_CANCELLED', "Cancelled order #{$order['order_code']}");
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Đã hủy đơn hàng thành công'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>