-- ============================================
-- SEED FEATURED PRODUCTS
-- Sample data for featured products with images
-- ============================================

-- Insert featured laptop products with placeholder images
INSERT INTO products (
    name, 
    slug, 
    brand, 
    category, 
    price, 
    discount_price, 
    cost_price,
    description, 
    specifications,
    image_url, 
    stock_quantity, 
    is_featured, 
    is_active,
    sku,
    warranty_months
) VALUES 
(
    'Dell XPS 13 9320 - Intel Core i7-1260P',
    'dell-xps-13-9320-i7-1260p',
    'Dell',
    'Ultrabook',
    32990000,
    29990000,
    24000000,
    'Dell XPS 13 9320 với thiết kế sang trọng, màn hình InfinityEdge 13.4 inch, chip Intel Core i7 thế hệ 12, RAM 16GB, SSD 512GB. Phù hợp cho công việc văn phòng và di động.',
    '{"cpu": "Intel Core i7-1260P", "ram": "16GB LPDDR5", "storage": "512GB NVMe SSD", "display": "13.4 inch FHD+", "gpu": "Intel Iris Xe", "weight": "1.27kg", "battery": "52WHr"}'::jsonb,
    'assets/images/products/dell-xps-13-9320.jpg',
    15,
    TRUE,
    TRUE,
    'DELL-XPS13-9320-001',
    24
),
(
    'MacBook Air M2 2022 - 13.6 inch',
    'macbook-air-m2-2022-13inch',
    'Apple',
    'Ultrabook',
    28990000,
    27490000,
    23000000,
    'MacBook Air M2 chip mới nhất từ Apple, màn hình Liquid Retina 13.6 inch, RAM 8GB, SSD 256GB. Thiết kế mỏng nhẹ, hiệu năng vượt trội, thời lượng pin ấn tượng.',
    '{"cpu": "Apple M2 8-core", "ram": "8GB Unified Memory", "storage": "256GB SSD", "display": "13.6 inch Liquid Retina", "gpu": "8-core GPU", "weight": "1.24kg", "battery": "52.6WHr"}'::jsonb,
    'assets/images/products/macbook-air-m2-2022.jpg',
    20,
    TRUE,
    TRUE,
    'APPLE-MBA-M2-2022-001',
    12
),
(
    'Lenovo ThinkPad X1 Carbon Gen 10',
    'lenovo-thinkpad-x1-carbon-gen10',
    'Lenovo',
    'Business',
    38990000,
    35990000,
    29000000,
    'Lenovo ThinkPad X1 Carbon Gen 10 - laptop doanh nhân cao cấp với chip Intel Core i7-1260P, RAM 16GB, SSD 512GB, màn hình 14 inch WUXGA. Bàn phím TrackPoint huyền thoại.',
    '{"cpu": "Intel Core i7-1260P", "ram": "16GB LPDDR5", "storage": "512GB NVMe SSD", "display": "14 inch WUXGA IPS", "gpu": "Intel Iris Xe", "weight": "1.12kg", "battery": "57WHr"}'::jsonb,
    'assets/images/products/lenovo-thinkpad-x1-carbon-gen10.jpg',
    12,
    TRUE,
    TRUE,
    'LENOVO-X1C-G10-001',
    36
),
(
    'ASUS ROG Strix G15 - Ryzen 7 6800H RTX 3060',
    'asus-rog-strix-g15-ryzen7-6800h-rtx3060',
    'ASUS',
    'Gaming',
    32990000,
    30990000,
    26000000,
    'ASUS ROG Strix G15 - laptop gaming mạnh mẽ với AMD Ryzen 7 6800H, RTX 3060 6GB, RAM 16GB, SSD 512GB, màn hình 15.6 inch FHD 144Hz. Tản nhiệt hiệu quả, RGB Aura Sync.',
    '{"cpu": "AMD Ryzen 7 6800H", "ram": "16GB DDR5", "storage": "512GB NVMe SSD", "display": "15.6 inch FHD 144Hz", "gpu": "NVIDIA RTX 3060 6GB", "weight": "2.3kg", "battery": "90WHr"}'::jsonb,
    'assets/images/products/asus-rog-strix-g15.jpg',
    10,
    TRUE,
    TRUE,
    'ASUS-ROG-G15-001',
    24
),
(
    'HP Pavilion 15 - Intel Core i5-1235U',
    'hp-pavilion-15-i5-1235u',
    'HP',
    'Consumer',
    16990000,
    15490000,
    13000000,
    'HP Pavilion 15 - laptop đa năng cho sinh viên và văn phòng với Intel Core i5-1235U, RAM 8GB, SSD 512GB, màn hình 15.6 inch FHD. Thiết kế đẹp, giá cả phải chăng.',
    '{"cpu": "Intel Core i5-1235U", "ram": "8GB DDR4", "storage": "512GB NVMe SSD", "display": "15.6 inch FHD IPS", "gpu": "Intel Iris Xe", "weight": "1.75kg", "battery": "41WHr"}'::jsonb,
    'assets/images/products/hp-pavilion-15.jpg',
    25,
    TRUE,
    TRUE,
    'HP-PAV15-001',
    12
),
(
    'Acer Predator Helios 300 - i7-12700H RTX 3070Ti',
    'acer-predator-helios-300-i7-12700h-rtx3070ti',
    'Acer',
    'Gaming',
    42990000,
    39990000,
    33000000,
    'Acer Predator Helios 300 - laptop gaming cao cấp với Intel Core i7-12700H, RTX 3070Ti 8GB, RAM 16GB, SSD 1TB, màn hình 15.6 inch QHD 165Hz. Hiệu năng đỉnh cao cho game thủ.',
    '{"cpu": "Intel Core i7-12700H", "ram": "16GB DDR5", "storage": "1TB NVMe SSD", "display": "15.6 inch QHD 165Hz", "gpu": "NVIDIA RTX 3070Ti 8GB", "weight": "2.6kg", "battery": "90WHr"}'::jsonb,
    'assets/images/products/acer-predator-helios-300.jpg',
    8,
    TRUE,
    TRUE,
    'ACER-PH300-001',
    24
),
(
    'MSI Modern 14 - Ryzen 5 5625U',
    'msi-modern-14-ryzen5-5625u',
    'MSI',
    'Consumer',
    14990000,
    13990000,
    11500000,
    'MSI Modern 14 - laptop văn phòng thanh lịch với AMD Ryzen 5 5625U, RAM 8GB, SSD 512GB, màn hình 14 inch FHD. Mỏng nhẹ, thời lượng pin tốt, giá hợp lý.',
    '{"cpu": "AMD Ryzen 5 5625U", "ram": "8GB DDR4", "storage": "512GB NVMe SSD", "display": "14 inch FHD IPS", "gpu": "AMD Radeon Graphics", "weight": "1.4kg", "battery": "50WHr"}'::jsonb,
    'assets/images/products/msi-modern-14.jpg',
    18,
    TRUE,
    TRUE,
    'MSI-MOD14-001',
    24
),
(
    'MacBook Pro 14 M2 Pro 2023',
    'macbook-pro-14-m2-pro-2023',
    'Apple',
    'Professional',
    52990000,
    50990000,
    42000000,
    'MacBook Pro 14 inch với chip M2 Pro mạnh mẽ, màn hình Liquid Retina XDR, RAM 16GB, SSD 512GB. Dành cho các chuyên gia sáng tạo, lập trình viên, thiết kế đồ họa.',
    '{"cpu": "Apple M2 Pro 10-core", "ram": "16GB Unified Memory", "storage": "512GB SSD", "display": "14.2 inch Liquid Retina XDR", "gpu": "16-core GPU", "weight": "1.6kg", "battery": "70WHr"}'::jsonb,
    'assets/images/products/macbook-pro-14-m2-pro.jpg',
    6,
    TRUE,
    TRUE,
    'APPLE-MBP14-M2P-001',
    12
);

-- Update existing products if any (optional - sets random products as featured)
-- UPDATE products SET is_featured = TRUE WHERE id IN (
--     SELECT id FROM products WHERE is_featured = FALSE ORDER BY RANDOM() LIMIT 3
-- );

-- Note: After running this SQL, copy placeholder images to:
-- TMDT_LAPTOP/laptop-store/assets/images/products/
-- Image files should be named as specified in image_url column above
