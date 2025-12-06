-- ============================================
-- LAPTOP STORE DATABASE SCHEMA
-- PostgreSQL 15.x
-- ============================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Drop tables if exists (in order of dependencies)
DROP TABLE IF EXISTS activity_logs CASCADE;
DROP TABLE IF EXISTS financial_records CASCADE;
DROP TABLE IF EXISTS payments CASCADE;
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS cart_items CASCADE;
DROP TABLE IF EXISTS carts CASCADE;
DROP TABLE IF EXISTS product_reviews CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    avatar_url VARCHAR(500),
    role VARCHAR(20) DEFAULT 'CUSTOMER' CHECK (role IN ('CUSTOMER', 'ADMIN', 'STAFF')),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    remember_token VARCHAR(100),
    token_expiry TIMESTAMP,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for users
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_created_at ON users(created_at);

-- ============================================
-- PRODUCTS TABLE
-- ============================================
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    brand VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(12,2) NOT NULL CHECK (price >= 0),
    discount_price DECIMAL(12,2) CHECK (discount_price >= 0),
    cost_price DECIMAL(12,2) CHECK (cost_price >= 0),
    description TEXT,
    specifications JSONB,
    image_url VARCHAR(500),
    image_urls TEXT[],
    stock_quantity INTEGER NOT NULL DEFAULT 0 CHECK (stock_quantity >= 0),
    sold_count INTEGER DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0 CHECK (rating >= 0 AND rating <= 5),
    review_count INTEGER DEFAULT 0,
    view_count INTEGER DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    sku VARCHAR(50) UNIQUE,
    weight_kg DECIMAL(6,2),
    warranty_months INTEGER DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for products
CREATE INDEX idx_products_brand ON products(brand);
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_is_active ON products(is_active);
CREATE INDEX idx_products_is_featured ON products(is_featured);
CREATE INDEX idx_products_created_at ON products(created_at);
CREATE INDEX idx_products_slug ON products(slug);

-- Full-text search index
CREATE INDEX idx_products_search ON products USING GIN(
    to_tsvector('english', name || ' ' || COALESCE(brand, '') || ' ' || COALESCE(category, ''))
);

-- ============================================
-- PRODUCT REVIEWS TABLE
-- ============================================
CREATE TABLE product_reviews (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id),
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_reviews_product ON product_reviews(product_id);
CREATE INDEX idx_reviews_user ON product_reviews(user_id);

-- ============================================
-- CARTS TABLE
-- ============================================
CREATE TABLE carts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- CART ITEMS TABLE
-- ============================================
CREATE TABLE cart_items (
    id SERIAL PRIMARY KEY,
    cart_id INTEGER NOT NULL REFERENCES carts(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id),
    quantity INTEGER NOT NULL DEFAULT 1 CHECK (quantity > 0),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(cart_id, product_id)
);

-- Index for cart items
CREATE INDEX idx_cart_items_cart_id ON cart_items(cart_id);
CREATE INDEX idx_cart_items_product_id ON cart_items(product_id);

-- ============================================
-- ORDERS TABLE
-- ============================================
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    order_code VARCHAR(20) UNIQUE NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id),
    total_amount DECIMAL(12,2) NOT NULL CHECK (total_amount >= 0),
    discount_amount DECIMAL(12,2) DEFAULT 0 CHECK (discount_amount >= 0),
    shipping_fee DECIMAL(12,2) DEFAULT 0 CHECK (shipping_fee >= 0),
    final_amount DECIMAL(12,2) NOT NULL CHECK (final_amount >= 0),
    status VARCHAR(30) NOT NULL DEFAULT 'PENDING' CHECK (
        status IN ('PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPING', 'DELIVERED', 'COMPLETED', 'CANCELLED', 'REFUNDED')
    ),
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    phone VARCHAR(20) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    note TEXT,
    estimated_delivery_date DATE,
    actual_delivery_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancelled_at TIMESTAMP,
    completed_at TIMESTAMP
);

-- Index for orders
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_order_code ON orders(order_code);
CREATE INDEX idx_orders_created_at ON orders(created_at);

-- ============================================
-- ORDER ITEMS TABLE
-- ============================================
CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id),
    product_name VARCHAR(200) NOT NULL,
    product_image VARCHAR(500),
    specifications JSONB,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(12,2) NOT NULL CHECK (unit_price >= 0),
    total_price DECIMAL(12,2) NOT NULL CHECK (total_price >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for order items
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);

-- ============================================
-- PAYMENTS TABLE
-- ============================================
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    order_id INTEGER UNIQUE NOT NULL REFERENCES orders(id),
    payment_method VARCHAR(50) NOT NULL CHECK (
        payment_method IN ('COD', 'BANK_TRANSFER', 'VNPAY', 'MOMO', 'ZALOPAY', 'CREDIT_CARD')
    ),
    amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
    status VARCHAR(30) NOT NULL DEFAULT 'PENDING' CHECK (
        status IN ('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'REFUNDED', 'CANCELLED')
    ),
    transaction_id VARCHAR(100),
    gateway_response JSONB,
    error_code VARCHAR(50),
    error_message TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payment_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for payments
CREATE INDEX idx_payments_order_id ON payments(order_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_transaction_id ON payments(transaction_id);

-- ============================================
-- FINANCIAL RECORDS TABLE
-- ============================================
CREATE TABLE financial_records (
    id SERIAL PRIMARY KEY,
    record_type VARCHAR(10) NOT NULL CHECK (record_type IN ('THU', 'CHI')),
    amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
    description VARCHAR(255) NOT NULL,
    reference_id INTEGER NOT NULL,
    reference_type VARCHAR(50) NOT NULL CHECK (
        reference_type IN ('ORDER', 'PAYMENT', 'REFUND', 'EXPENSE', 'ADJUSTMENT')
    ),
    payment_method VARCHAR(50),
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for financial records
CREATE INDEX idx_financial_type ON financial_records(record_type);
CREATE INDEX idx_financial_reference ON financial_records(reference_type, reference_id);
CREATE INDEX idx_financial_created_at ON financial_records(created_at);

-- ============================================
-- ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for activity logs
CREATE INDEX idx_activity_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_created_at ON activity_logs(created_at);
CREATE INDEX idx_activity_action ON activity_logs(action);

-- ============================================
-- TRIGGERS AND FUNCTIONS
-- ============================================

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply trigger to tables
CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_products_updated_at BEFORE UPDATE ON products
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_orders_updated_at BEFORE UPDATE ON orders
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to update product rating
CREATE OR REPLACE FUNCTION update_product_rating()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE products SET
        rating = (SELECT AVG(rating)::DECIMAL(3,2) FROM product_reviews WHERE product_id = NEW.product_id AND is_approved = true),
        review_count = (SELECT COUNT(*) FROM product_reviews WHERE product_id = NEW.product_id AND is_approved = true)
    WHERE id = NEW.product_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_review_rating AFTER INSERT OR UPDATE OR DELETE ON product_reviews
    FOR EACH ROW EXECUTE FUNCTION update_product_rating();

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- Daily sales report view
CREATE OR REPLACE VIEW vw_daily_sales AS
SELECT 
    DATE(created_at) as sale_date,
    COUNT(*) as total_orders,
    SUM(final_amount) as total_revenue,
    SUM(CASE WHEN status IN ('COMPLETED', 'DELIVERED') THEN final_amount ELSE 0 END) as completed_revenue,
    AVG(final_amount) as avg_order_value
FROM orders
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

-- Product performance view
CREATE OR REPLACE VIEW vw_product_performance AS
SELECT 
    p.id,
    p.name,
    p.brand,
    p.category,
    p.price,
    p.discount_price,
    p.stock_quantity,
    p.sold_count,
    p.rating,
    p.review_count,
    COALESCE(SUM(oi.quantity), 0) as total_sold_via_orders,
    COALESCE(SUM(oi.total_price), 0) as total_revenue
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('COMPLETED', 'DELIVERED')
GROUP BY p.id
ORDER BY total_revenue DESC;

-- Customer purchase summary
CREATE OR REPLACE VIEW vw_customer_summary AS
SELECT 
    u.id as customer_id,
    u.full_name,
    u.email,
    u.phone,
    COUNT(DISTINCT o.id) as total_orders,
    SUM(o.final_amount) as total_spent,
    MAX(o.created_at) as last_purchase_date,
    AVG(o.final_amount) as avg_order_value
FROM users u
LEFT JOIN orders o ON u.id = o.user_id AND o.status IN ('COMPLETED', 'DELIVERED')
WHERE u.role = 'CUSTOMER'
GROUP BY u.id
ORDER BY total_spent DESC NULLS LAST;

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role, is_active) VALUES
('admin', 'admin@laptopstore.com', '$2y$10$YxOaKXqFz5G8rZ.MdU5vvO5P7r9EqL.fJxW.QHvGzk5LZ2C1x3Z5G', 'Administrator', 'ADMIN', true);

-- Insert sample customers (password: 123456)
INSERT INTO users (username, email, password, full_name, phone, address, role) VALUES
('customer1', 'customer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', '0901234567', '123 Đường ABC, Quận 1, TP.HCM', 'CUSTOMER'),
('customer2', 'customer2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B', '0912345678', '456 Đường XYZ, Quận 2, TP.HCM', 'CUSTOMER');

-- Insert sample products
INSERT INTO products (name, slug, brand, category, price, discount_price, description, image_url, stock_quantity, is_featured, warranty_months) VALUES
('Dell XPS 13 9310', 'dell-xps-13-9310', 'Dell', 'Ultrabook', 29990000, 27990000, 'Laptop Dell XPS 13 với thiết kế sang trọng, màn hình InfinityEdge, hiệu năng mạnh mẽ với Intel Core i7', 'dell-xps-13.jpg', 10, true, 24),
('MacBook Air M2 2022', 'macbook-air-m2-2022', 'Apple', 'Ultrabook', 32990000, 31490000, 'MacBook Air với chip M2 mới, thiết kế mỏng nhẹ, hiệu năng vượt trội, pin bền bỉ', 'macbook-air-m2.jpg', 8, true, 12),
('Asus ROG Zephyrus G14', 'asus-rog-zephyrus-g14', 'Asus', 'Gaming', 35990000, NULL, 'Laptop gaming cao cấp với AMD Ryzen 9, RTX 3060, màn hình 144Hz', 'asus-rog-g14.jpg', 5, true, 24),
('Lenovo ThinkPad X1 Carbon Gen 9', 'lenovo-thinkpad-x1-carbon-gen-9', 'Lenovo', 'Business', 27990000, 26490000, 'Laptop doanh nghiệp cao cấp, bàn phím tốt nhất, độ bền cao', 'lenovo-thinkpad.jpg', 12, false, 36),
('HP Spectre x360 14', 'hp-spectre-x360-14', 'HP', '2-in-1', 25990000, 24990000, 'Laptop 2-in-1 cao cấp, màn hình cảm ứng OLED, thiết kế xoay 360 độ', 'hp-spectre.jpg', 7, true, 24),
('Acer Predator Helios 300', 'acer-predator-helios-300', 'Acer', 'Gaming', 23990000, 22490000, 'Laptop gaming giá tốt, RTX 3060, màn hình 144Hz, tản nhiệt tốt', 'acer-predator.jpg', 15, false, 24),
('MSI Modern 14', 'msi-modern-14', 'MSI', 'Business', 18990000, 17990000, 'Laptop văn phòng mỏng nhẹ, hiệu năng ổn định, pin lâu', 'msi-modern.jpg', 20, false, 24),
('Asus ZenBook 14', 'asus-zenbook-14', 'Asus', 'Ultrabook', 21990000, 20490000, 'Laptop siêu mỏng với màn hình OLED, hiệu năng tốt cho công việc', 'asus-zenbook.jpg', 9, false, 24);

-- Update specifications for products
UPDATE products SET specifications = '{
    "processor": "Intel Core i7-1185G7",
    "ram": "16GB LPDDR4x",
    "storage": "512GB SSD",
    "display": "13.4 inch FHD+",
    "graphics": "Intel Iris Xe",
    "weight": "1.2kg"
}'::jsonb WHERE slug = 'dell-xps-13-9310';

UPDATE products SET specifications = '{
    "processor": "Apple M2 8-core",
    "ram": "8GB Unified Memory",
    "storage": "256GB SSD",
    "display": "13.6 inch Liquid Retina",
    "graphics": "M2 GPU 8-core",
    "weight": "1.24kg"
}'::jsonb WHERE slug = 'macbook-air-m2-2022';

UPDATE products SET specifications = '{
    "processor": "AMD Ryzen 9 5900HS",
    "ram": "16GB DDR4",
    "storage": "1TB SSD",
    "display": "14 inch QHD 144Hz",
    "graphics": "NVIDIA RTX 3060 6GB",
    "weight": "1.7kg"
}'::jsonb WHERE slug = 'asus-rog-zephyrus-g14';

-- Comments
COMMENT ON TABLE users IS 'User accounts and authentication';
COMMENT ON TABLE products IS 'Product catalog';
COMMENT ON TABLE orders IS 'Customer orders';
COMMENT ON TABLE order_items IS 'Items in each order';
COMMENT ON TABLE payments IS 'Payment transactions';
COMMENT ON TABLE financial_records IS 'Financial accounting records';
COMMENT ON TABLE activity_logs IS 'System activity audit trail';