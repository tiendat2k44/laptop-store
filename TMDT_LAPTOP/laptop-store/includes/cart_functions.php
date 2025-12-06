<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Shopping Cart Management Class
 */
class Cart {
    private $db;
    private $userId;
    private $cartId;
    
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        $this->userId = $userId ?? ($_SESSION['user_id'] ?? null);
        
        if ($this->userId) {
            $this->initializeCart();
        }
    }
    
    /**
     * Initialize cart for user
     */
    private function initializeCart() {
        // Check if cart exists
        $sql = "SELECT id FROM carts WHERE user_id = :user_id";
        $cart = $this->db->selectOne($sql, [':user_id' => $this->userId]);
        
        if ($cart) {
            $this->cartId = $cart['id'];
        } else {
            // Create new cart
            $sql = "INSERT INTO carts (user_id, created_at) VALUES (:user_id, NOW()) RETURNING id";
            $result = $this->db->selectOne($sql, [':user_id' => $this->userId]);
            $this->cartId = $result['id'];
        }
    }
    
    /**
     * Add item to cart
     */
    public function addItem($productId, $quantity = 1) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập để thêm vào giỏ hàng'];
        }
        
        // Validate product
        $product = $this->getProduct($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }
        
        if (!$product['is_active']) {
            return ['success' => false, 'message' => 'Sản phẩm không còn kinh doanh'];
        }
        
        // Check stock
        if ($product['stock_quantity'] < $quantity) {
            return ['success' => false, 'message' => 'Sản phẩm không đủ số lượng trong kho'];
        }
        
        try {
            // Check if item already in cart
            $sql = "SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id";
            $existingItem = $this->db->selectOne($sql, [
                ':cart_id' => $this->cartId,
                ':product_id' => $productId
            ]);
            
            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $quantity;
                
                if ($newQuantity > $product['stock_quantity']) {
                    return ['success' => false, 'message' => 'Số lượng vượt quá tồn kho'];
                }
                
                $sql = "UPDATE cart_items SET quantity = :quantity WHERE id = :id";
                $this->db->update($sql, [
                    ':quantity' => $newQuantity,
                    ':id' => $existingItem['id']
                ]);
            } else {
                // Insert new item
                $sql = "INSERT INTO cart_items (cart_id, product_id, quantity, added_at) 
                        VALUES (:cart_id, :product_id, :quantity, NOW())";
                $this->db->insert($sql, [
                    ':cart_id' => $this->cartId,
                    ':product_id' => $productId,
                    ':quantity' => $quantity
                ]);
            }
            
            // Update cart timestamp
            $this->updateCartTimestamp();
            
            return [
                'success' => true, 
                'message' => 'Đã thêm sản phẩm vào giỏ hàng',
                'cart_count' => $this->getItemCount()
            ];
            
        } catch (Exception $e) {
            error_log("Add to cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'];
        }
    }
    
    /**
     * Update item quantity
     */
    public function updateQuantity($productId, $quantity) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
        }
        
        if ($quantity < 1) {
            return $this->removeItem($productId);
        }
        
        // Check stock
        $product = $this->getProduct($productId);
        if ($quantity > $product['stock_quantity']) {
            return ['success' => false, 'message' => 'Số lượng vượt quá tồn kho'];
        }
        
        try {
            $sql = "UPDATE cart_items SET quantity = :quantity 
                    WHERE cart_id = :cart_id AND product_id = :product_id";
            
            $this->db->update($sql, [
                ':quantity' => $quantity,
                ':cart_id' => $this->cartId,
                ':product_id' => $productId
            ]);
            
            $this->updateCartTimestamp();
            
            return [
                'success' => true,
                'message' => 'Đã cập nhật số lượng',
                'cart_total' => $this->getTotal()
            ];
            
        } catch (Exception $e) {
            error_log("Update cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($productId) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
        }
        
        try {
            $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id";
            $this->db->delete($sql, [
                ':cart_id' => $this->cartId,
                ':product_id' => $productId
            ]);
            
            $this->updateCartTimestamp();
            
            return [
                'success' => true,
                'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
                'cart_count' => $this->getItemCount()
            ];
            
        } catch (Exception $e) {
            error_log("Remove from cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }
    
    /**
     * Clear cart
     */
    public function clearCart() {
        if (!$this->userId) {
            return false;
        }
        
        try {
            $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
            return $this->db->delete($sql, [':cart_id' => $this->cartId]);
        } catch (Exception $e) {
            error_log("Clear cart error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all cart items with product details
     */
    public function getItems() {
        if (!$this->userId) {
            return [];
        }
        
        $sql = "SELECT 
                    ci.id as cart_item_id,
                    ci.quantity,
                    ci.added_at,
                    p.id as product_id,
                    p.name,
                    p.slug,
                    p.brand,
                    p.price,
                    p.discount_price,
                    p.image_url,
                    p.stock_quantity,
                    p.is_active,
                    COALESCE(p.discount_price, p.price) as current_price,
                    COALESCE(p.discount_price, p.price) * ci.quantity as item_total
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = :cart_id
                ORDER BY ci.added_at DESC";
        
        return $this->db->select($sql, [':cart_id' => $this->cartId]);
    }
    
    /**
     * Get cart item count
     */
    public function getItemCount() {
        if (!$this->userId) {
            return 0;
        }
        
        $sql = "SELECT COALESCE(SUM(quantity), 0) as count FROM cart_items WHERE cart_id = :cart_id";
        $result = $this->db->selectOne($sql, [':cart_id' => $this->cartId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get cart total amount
     */
    public function getTotal() {
        if (!$this->userId) {
            return 0;
        }
        
        $sql = "SELECT COALESCE(SUM(
                    COALESCE(p.discount_price, p.price) * ci.quantity
                ), 0) as total
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = :cart_id AND p.is_active = true";
        
        $result = $this->db->selectOne($sql, [':cart_id' => $this->cartId]);
        return $result ? (float)$result['total'] : 0;
    }
    
    /**
     * Get cart summary
     */
    public function getSummary() {
        $items = $this->getItems();
        $subtotal = 0;
        $discount = 0;
        
        foreach ($items as $item) {
            if ($item['discount_price']) {
                $discount += ($item['price'] - $item['discount_price']) * $item['quantity'];
            }
            $subtotal += $item['current_price'] * $item['quantity'];
        }
        
        // Calculate shipping fee (free if over 5 million)
        $shippingFee = $subtotal >= 5000000 ? 0 : 50000;
        
        $total = $subtotal + $shippingFee;
        
        return [
            'items' => $items,
            'item_count' => count($items),
            'quantity_count' => $this->getItemCount(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_fee' => $shippingFee,
            'total' => $total
        ];
    }
    
    /**
     * Validate cart before checkout
     */
    public function validateCart() {
        $items = $this->getItems();
        $errors = [];
        
        if (empty($items)) {
            $errors[] = 'Giỏ hàng trống';
            return ['valid' => false, 'errors' => $errors];
        }
        
        foreach ($items as $item) {
            if (!$item['is_active']) {
                $errors[] = "Sản phẩm '{$item['name']}' không còn kinh doanh";
            }
            
            if ($item['stock_quantity'] < $item['quantity']) {
                $errors[] = "Sản phẩm '{$item['name']}' chỉ còn {$item['stock_quantity']} sản phẩm trong kho";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get product by ID
     */
    private function getProduct($productId) {
        $sql = "SELECT * FROM products WHERE id = :id";
        return $this->db->selectOne($sql, [':id' => $productId]);
    }
    
    /**
     * Update cart timestamp
     */
    private function updateCartTimestamp() {
        $sql = "UPDATE carts SET updated_at = NOW() WHERE id = :id";
        $this->db->update($sql, [':id' => $this->cartId]);
    }
    
    /**
     * Transfer session cart to user cart (after login)
     */
    public function mergeSessionCart($sessionCart) {
        if (!$this->userId || empty($sessionCart)) {
            return false;
        }
        
        foreach ($sessionCart as $productId => $quantity) {
            $this->addItem($productId, $quantity);
        }
        
        return true;
    }
}

/**
 * Get cart instance for current user
 */
function getCart() {
    static $cart = null;
    if ($cart === null) {
        $cart = new Cart();
    }
    return $cart;
}

/**
 * Get cart item count for display
 */
function getCartCount() {
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }
    
    $cart = getCart();
    return $cart->getItemCount();
}
?>