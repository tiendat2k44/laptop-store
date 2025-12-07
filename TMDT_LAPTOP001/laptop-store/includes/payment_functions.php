<?php
/**
 * Payment Processing Functions - CẬP NHẬT THU CHI NGAY LẬP TỨC
 */

require_once 'config.php';
require_once 'database.php';

class PaymentProcessor {
    
    /**
     * Xử lý thanh toán và cập nhật thu chi ngay
     */
    public static function processPayment($orderId, $paymentMethod, $amount) {
        global $db;
        
        try {
            // Bắt đầu transaction
            $db->beginTransaction();
            
            // 1. Lấy thông tin đơn hàng
            $sql = "SELECT * FROM orders WHERE id = ? AND status = 'pending'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception("Đơn hàng không hợp lệ");
            }
            
            // 2. Kiểm tra tồn kho
            $sql = "SELECT oi.product_id, oi.quantity, p.name, p.stock_quantity 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                if ($item['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Sản phẩm '{$item['name']}' chỉ còn {$item['stock_quantity']} sản phẩm");
                }
            }
            
            // 3. Tạo payment record
            $transactionId = 'TX' . date('YmdHis') . rand(100, 999);
            $sql = "INSERT INTO payments (order_id, payment_method, amount, status, transaction_id) 
                    VALUES (?, ?, ?, 'completed', ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$orderId, $paymentMethod, $amount, $transactionId]);
            $paymentId = $db->lastInsertId();
            
            // 4. CẬP NHẬT THU CHI NGAY LẬP TỨC - QUAN TRỌNG
            $sql = "INSERT INTO financial_records (type, amount, description, reference_id, reference_type) 
                    VALUES ('THU', ?, ?, ?, 'PAYMENT')";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $amount,
                "Thanh toán đơn hàng #$orderId - Mã GD: $transactionId",
                $paymentId
            ]);
            
            // 5. Cập nhật trạng thái đơn hàng
            $sql = "UPDATE orders SET status = 'confirmed', payment_status = 'paid' WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$orderId]);
            
            // 6. Cập nhật tồn kho
            foreach ($items as $item) {
                $sql = "UPDATE products 
                        SET stock_quantity = stock_quantity - ?, 
                            sold_count = sold_count + ? 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
            }
            
            // 7. Commit transaction
            $db->commit();
            
            // 8. Gửi email xác nhận
            self::sendConfirmationEmail($orderId, $transactionId);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'message' => 'Thanh toán thành công!'
            ];
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Lỗi thanh toán: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Lấy danh sách phương thức thanh toán
     */
    public static function getPaymentMethods() {
        return [
            [
                'id' => 'cod',
                'name' => 'Thanh toán khi nhận hàng (COD)',
                'description' => 'Trả tiền mặt khi nhận được hàng',
                'icon' => 'fas fa-money-bill-wave'
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'Chuyển khoản ngân hàng',
                'description' => 'Chuyển khoản qua Internet Banking',
                'icon' => 'fas fa-university'
            ]
        ];
    }
    
    /**
     * Lấy báo cáo thu chi
     */
    public static function getFinancialReport($startDate = null, $endDate = null) {
        global $db;
        
        $sql = "SELECT * FROM financial_records WHERE 1=1";
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " AND created_at BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Tính tổng thu chi
     */
    public static function getFinancialSummary() {
        global $db;
        
        $sql = "SELECT 
                SUM(CASE WHEN type = 'THU' THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN type = 'CHI' THEN amount ELSE 0 END) as total_expense
                FROM financial_records";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Gửi email xác nhận
     */
    private static function sendConfirmationEmail($orderId, $transactionId) {
        // Trong thực tế, bạn sẽ tích hợp với PHPMailer hoặc SMTP
        error_log("Gửi email xác nhận cho đơn hàng #$orderId, mã GD: $transactionId");
        return true;
    }
}
?>