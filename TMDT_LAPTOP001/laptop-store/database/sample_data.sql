-- Dữ liệu mẫu

-- Users
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@laptopstore.com', '$2y$10$YourHashedPasswordHere', 'Admin User', 'admin'),
('customer1', 'customer1@email.com', '$2y$10$YourHashedPasswordHere', 'Nguyen Van A', 'customer');

-- Products
INSERT INTO products (name, brand, price, stock_quantity, image_url) VALUES
('Dell XPS 13 9310', 'Dell', 29990000, 10, 'dell-xps-13.jpg'),
('MacBook Air M2', 'Apple', 32990000, 5, 'macbook-air-m2.jpg'),
('Asus ROG Zephyrus G14', 'Asus', 35990000, 8, 'asus-rog-g14.jpg'),
('Lenovo ThinkPad X1 Carbon', 'Lenovo', 27990000, 12, 'thinkpad-x1.jpg'),
('HP Spectre x360', 'HP', 25990000, 7, 'hp-spectre.jpg'),
('Acer Predator Helios 300', 'Acer', 23990000, 6, 'acer-predator.jpg'),
('MSI Modern 14', 'MSI', 18990000, 15, 'msi-modern.jpg'),
('LG Gram 17', 'LG', 34990000, 4, 'lg-gram.jpg');