<?php
/**
 * Utility Functions for PostgreSQL
 */

// Get featured products
/**
 * Get products with optional filtering
 */
function getProducts($limit = 12, $category = null, $brand = null, $minPrice = null, $maxPrice = null) {
    global $db;
    
    $sql = "SELECT * FROM products WHERE is_active = true";
    $params = [];
    $conditions = [];
    
    // Add category filter
    if ($category) {
        $conditions[] = "category = ?";
        $params[] = $category;
    }
    
    // Add brand filter
    if ($brand) {
        $conditions[] = "brand = ?";
        $params[] = $brand;
    }
    
    // Add price range filter
    if ($minPrice !== null) {
        $conditions[] = "price >= ?";
        $params[] = $minPrice;
    }
    
    if ($maxPrice !== null) {
        $conditions[] = "price <= ?";
        $params[] = $maxPrice;
    }
    
    // Combine conditions
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }
    
    // Add ordering and limit
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("getProducts error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get product by ID
 */
function getProductById($id) {
    global $db;
    
    $sql = "SELECT * FROM products WHERE id = ? AND is_active = true";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    
    return $stmt->fetch();
}

/**
 * Get products by category
 */
function getProductsByCategory($category, $limit = 12) {
    global $db;
    
    $sql = "SELECT * FROM products WHERE category = ? AND is_active = true ORDER BY created_at DESC LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$category, $limit]);
    
    return $stmt->fetchAll();
}

/**
 * Get featured products
 */
function getFeaturedProducts($limit = 8) {
    global $db;
    
    $sql = "SELECT * FROM products WHERE is_featured = true AND is_active = true ORDER BY created_at DESC LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}

/**
 * Get best selling products
 */
function getBestSellingProducts($limit = 6) {
    global $db;
    
    $sql = "SELECT * FROM products WHERE is_active = true ORDER BY sold_count DESC, created_at DESC LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}

// Add to cart
function addToCart($productId, $quantity = 1) {
    if (!isset($_SESSION['user_id'])) {
        // Use session cart for guests
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        return true;
    }
    
    // For logged in users, save to database
    global $db;
    
    try {
        // Get or create cart for user
        $sql = "SELECT id FROM carts WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $cart = $stmt->fetch();
        
        if (!$cart) {
            $sql = "INSERT INTO carts (user_id) VALUES (?) RETURNING id";
            $stmt = $db->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
            $cartId = $stmt->fetch()['id'];
        } else {
            $cartId = $cart['id'];
        }
        
        // Add item to cart
        $sql = "INSERT INTO cart_items (cart_id, product_id, quantity) 
                VALUES (?, ?, ?)
                ON CONFLICT (cart_id, product_id) 
                DO UPDATE SET quantity = cart_items.quantity + EXCLUDED.quantity";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$cartId, $productId, $quantity]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Cart error: " . $e->getMessage());
        return false;
    }
}

// Get cart items
function getCartItems() {
    if (!isset($_SESSION['user_id'])) {
        // Guest cart from session
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return [];
        }
        
        $productIds = array_keys($_SESSION['cart']);
        if (empty($productIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        global $db;
        $sql = "SELECT id, name, price, discount_price, image_url 
                FROM products 
                WHERE id IN ($placeholders) AND is_active = true";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($productIds);
        $products = $stmt->fetchAll();
        
        $items = [];
        foreach ($products as $product) {
            $items[] = [
                'product' => $product,
                'quantity' => $_SESSION['cart'][$product['id']]
            ];
        }
        
        return $items;
    }
    
    // Logged in user cart from database
    global $db;
    
    $sql = "SELECT p.id, p.name, p.price, p.discount_price, p.image_url, ci.quantity
            FROM carts c
            JOIN cart_items ci ON c.id = ci.cart_id
            JOIN products p ON ci.product_id = p.id
            WHERE c.user_id = ? AND p.is_active = true";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetchAll();
}

// Calculate cart total
function calculateCartTotal() {
    $items = getCartItems();
    $total = 0;
    
    foreach ($items as $item) {
        $price = $item['discount_price'] ?? $item['price'];
        $total += $price * $item['quantity'];
    }
    
    return $total;
}

// Create order
function createOrder($orderData) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Generate order code
        $orderCode = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        
        // Insert order
        $sql = "INSERT INTO orders (
                    order_code, user_id, total_amount, shipping_address, 
                    phone, customer_name, email, payment_method, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')
                RETURNING id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $orderCode,
            $_SESSION['user_id'],
            $orderData['total_amount'],
            $orderData['shipping_address'],
            $orderData['phone'],
            $orderData['customer_name'],
            $orderData['email'],
            $orderData['payment_method']
        ]);
        
        $orderId = $stmt->fetch()['id'];
        
        // Add order items
        $items = getCartItems();
        foreach ($items as $item) {
            $price = $item['discount_price'] ?? $item['price'];
            $total = $price * $item['quantity'];
            
            $sql = "INSERT INTO order_items (
                        order_id, product_id, product_name, 
                        quantity, unit_price, total_price
                    ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $orderId,
                $item['id'],
                $item['name'],
                $item['quantity'],
                $price,
                $total
            ]);
        }
        
        // Clear cart
        if (isset($_SESSION['user_id'])) {
            $sql = "DELETE FROM cart_items WHERE cart_id = (
                SELECT id FROM carts WHERE user_id = ?
            )";
            $stmt = $db->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
        } else {
            unset($_SESSION['cart']);
        }
        
        $db->commit();
        return $orderId;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception("Order creation failed: " . $e->getMessage());
    }
}



// Process payment (QUAN TRỌNG - CẬP NHẬT THU CHI)
function processPayment($orderId, $paymentMethod, $amount) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Create payment record
        $transactionId = 'TX' . date('YmdHis') . rand(100, 999);
        
        $sql = "INSERT INTO payments (
                    order_id, payment_method, amount, status, transaction_id
                ) VALUES (?, ?, ?, 'COMPLETED', ?)
                RETURNING id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$orderId, $paymentMethod, $amount, $transactionId]);
        $paymentId = $stmt->fetch()['id'];
        
        // CẬP NHẬT THU CHI NGAY - QUAN TRỌNG
        $sql = "INSERT INTO financial_records (
                    record_type, amount, description, 
                    reference_id, reference_type, created_by
                ) VALUES ('THU', ?, ?, ?, 'PAYMENT', ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $amount,
            "Thanh toán đơn hàng #$orderId - Mã GD: $transactionId",
            $paymentId,
            $_SESSION['user_id']
        ]);
        
        // Update order status
        $sql = "UPDATE orders SET status = 'CONFIRMED', payment_status = 'PAID' WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$orderId]);
        
        // Update product stock
        $sql = "UPDATE products p
                SET stock_quantity = p.stock_quantity - oi.quantity,
                    sold_count = p.sold_count + oi.quantity
                FROM order_items oi
                WHERE p.id = oi.product_id AND oi.order_id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$orderId]);
        
        $db->commit();
        
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'payment_id' => $paymentId
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Get financial summary
function getFinancialSummary($startDate = null, $endDate = null) {
    global $db;
    
    $sql = "SELECT 
                SUM(CASE WHEN record_type = 'THU' THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN record_type = 'CHI' THEN amount ELSE 0 END) as total_expense
            FROM financial_records";
    
    $params = [];
    
    if ($startDate && $endDate) {
        $sql .= " WHERE created_at BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch();
}


?>