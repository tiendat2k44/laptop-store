<<<<<<< HEAD
"# laptop-store"  
=======
# 🛒 LAPTOP STORE - E-COMMERCE SYSTEM

Hệ thống thương mại điện tử bán laptop hoàn chỉnh được xây dựng bằng PHP 8.x và PostgreSQL 15.x

## 📋 MỤC LỤC

- [Tính năng](#-tính-năng)
- [Yêu cầu hệ thống](#-yêu-cầu-hệ-thống)
- [Cài đặt](#-cài-đặt)
- [Cấu trúc dự án](#-cấu-trúc-dự-án)
- [Tài khoản demo](#-tài-khoản-demo)
- [API Endpoints](#-api-endpoints)
- [Bảo mật](#-bảo-mật)
- [Troubleshooting](#-troubleshooting)

## ✨ TÍNH NĂNG

### Khách hàng
- ✅ Đăng ký / Đăng nhập với bảo mật cao
- ✅ Tìm kiếm và lọc sản phẩm theo thương hiệu, giá, danh mục
- ✅ Xem chi tiết sản phẩm với đánh giá
- ✅ Giỏ hàng với quản lý số lượng realtime
- ✅ Checkout với nhiều phương thức thanh toán
- ✅ Theo dõi đơn hàng
- ✅ Quản lý hồ sơ cá nhân
- ✅ Hủy đơn hàng

### Admin
- ✅ Dashboard với thống kê realtime
- ✅ Quản lý sản phẩm (CRUD)
- ✅ Quản lý đơn hàng
- ✅ Quản lý khách hàng
- ✅ Báo cáo tài chính
- ✅ Theo dõi tồn kho

### Kỹ thuật
- ✅ MVC Pattern
- ✅ OOP với Singleton Database
- ✅ PDO với Prepared Statements
- ✅ Transaction support
- ✅ Error logging
- ✅ Session management
- ✅ CSRF protection ready
- ✅ XSS protection
- ✅ SQL Injection prevention

## 🖥️ YÊU CẦU HỆ THỐNG

- **PHP**: 8.0 hoặc cao hơn
- **PostgreSQL**: 15.x
- **Web Server**: Apache 2.4+ với mod_rewrite
- **RAM**: Tối thiểu 512MB
- **Disk Space**: 100MB

### PHP Extensions cần thiết:
```bash
php-pgsql
php-mbstring
php-json
php-session
php-curl
```

## 📦 CÀI ĐẶT

### Bước 1: Clone/Download dự án

```bash
# Clone repository
git clone https://github.com/yourusername/laptop-store.git
cd laptop-store

# Hoặc download và giải nén
```

### Bước 2: Cài đặt PostgreSQL

#### Windows:
1. Download PostgreSQL từ https://www.postgresql.org/download/windows/
2. Cài đặt với cổng mặc định 5432
3. Ghi nhớ mật khẩu postgres user

#### Ubuntu/Linux:
```bash
sudo apt update
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

#### macOS:
```bash
brew install postgresql@15
brew services start postgresql@15
```

### Bước 3: Tạo Database

```bash
# Đăng nhập PostgreSQL
sudo -u postgres psql

# Tạo database
CREATE DATABASE laptop_store;

# Tạo user (optional)
CREATE USER laptop_admin WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE laptop_store TO laptop_admin;

# Thoát
\q
```

### Bước 4: Import Schema

```bash
# Import schema.sql
psql -U postgres -d laptop_store -f database/schema.sql

# Hoặc trong psql prompt
\i /path/to/database/schema.sql
```

### Bước 5: Cấu hình

Chỉnh sửa `includes/config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password_here');  // ⚠️ Thay đổi này

// Security
define('PASSWORD_SALT', 'change_this_to_random_string');  // ⚠️ Thay đổi này
define('JWT_SECRET', 'change_this_to_random_string');     // ⚠️ Thay đổi này
```

### Bước 6: Cấp quyền thư mục

```bash
# Linux/Mac
chmod -R 755 laptop-store/
chmod -R 777 laptop-store/logs/
chmod -R 777 laptop-store/assets/uploads/

# Windows - Right click → Properties → Security → Edit permissions
```

### Bước 7: Cấu hình Apache

Tạo VirtualHost hoặc đặt trong htdocs:

```apache
<VirtualHost *:80>
    ServerName laptop-store.local
    DocumentRoot "/path/to/laptop-store"
    
    <Directory "/path/to/laptop-store">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/laptop-store-error.log"
    CustomLog "logs/laptop-store-access.log" common
</VirtualHost>
```

### Bước 8: Truy cập hệ thống

```
Frontend: http://localhost/laptop-store/pages/index.php
Admin:    http://localhost/laptop-store/admin/index.php
```

## 📁 CẤU TRÚC DỰ ÁN

```
laptop-store/
├── admin/                      # Admin panel
│   ├── index.php              # Dashboard
│   ├── products.php           # Quản lý sản phẩm
│   ├── orders.php             # Quản lý đơn hàng
│   └── ...
├── api/                       # API endpoints
│   └── cart-add.php          # Add to cart API
├── includes/                  # Core files
│   ├── config.php            # ✅ Cấu hình
│   ├── database.php          # ✅ Database class
│   ├── auth.php              # ✅ Authentication
│   ├── functions.php         # ✅ Utilities
│   ├── cart_functions.php    # ✅ Cart management
│   └── payment_functions.php # ✅ Payment processing
├── pages/                     # Frontend pages
│   ├── index.php             # ✅ Homepage
│   ├── login.php             # ✅ Login
│   ├── register.php          # ✅ Registration
│   ├── products.php          # ✅ Product listing
│   ├── cart.php              # ✅ Shopping cart
│   ├── checkout.php          # ✅ Checkout
│   └── ...
├── assets/                    # Static files
│   ├── css/
│   ├── js/
│   └── images/
├── database/                  # Database files
│   └── schema.sql            # ✅ Database schema
└── logs/                      # Log files
```

## 👤 TÀI KHOẢN DEMO

### Admin
```
Email:    admin@laptopstore.com
Password: admin123
```

### Customer
```
Email:    customer1@example.com
Password: 123456
```

## 🔌 API ENDPOINTS

### Cart API

**Add to Cart**
```
POST /api/cart-add.php
Content-Type: application/json

{
    "product_id": 1,
    "quantity": 1
}

Response:
{
    "success": true,
    "message": "Đã thêm sản phẩm vào giỏ hàng",
    "cart_count": 3
}
```

## 🔒 BẢO MẬT

### Implemented Security Measures:

1. **Password Security**
   - Bcrypt hashing with salt
   - Minimum 6 characters
   - Password strength validation

2. **SQL Injection Prevention**
   - PDO Prepared Statements
   - Parameter binding
   - Input validation

3. **XSS Protection**
   - htmlspecialchars() for output
   - strip_tags() for input
   - Content Security Policy ready

4. **Session Security**
   - Secure session configuration
   - Session timeout
   - Session regeneration on login

5. **CSRF Protection**
   - Token ready (implement in forms)
   - SameSite cookie attribute

### Security Checklist:

- [ ] Thay đổi `PASSWORD_SALT` trong config.php
- [ ] Thay đổi `JWT_SECRET` trong config.php
- [ ] Thay đổi password admin mặc định
- [ ] Cấu hình HTTPS trong production
- [ ] Giới hạn file upload size
- [ ] Cấu hình firewall cho PostgreSQL
- [ ] Backup database định kỳ

## 🐛 TROUBLESHOOTING

### Lỗi kết nối database

**Problem**: "Database connection error"

**Solution**:
```bash
# Kiểm tra PostgreSQL đang chạy
sudo systemctl status postgresql

# Kiểm tra port
sudo netstat -plnt | grep 5432

# Test connection
psql -U postgres -d laptop_store -h localhost
```

### Lỗi permission denied

**Problem**: "Permission denied" khi upload file

**Solution**:
```bash
chmod -R 777 logs/
chmod -R 777 assets/uploads/

# Check ownership
ls -la logs/
```

### Lỗi 404 Not Found

**Problem**: Các trang không load

**Solution**:
- Kiểm tra `.htaccess` file
- Enable mod_rewrite: `sudo a2enmod rewrite`
- Restart Apache: `sudo systemctl restart apache2`

### Lỗi session

**Problem**: "Session not working"

**Solution**:
```bash
# Check session directory
ls -la /var/lib/php/sessions/

# Fix permissions
chmod 1733 /var/lib/php/sessions/
```

### Database schema errors

**Problem**: "Table does not exist"

**Solution**:
```bash
# Drop and recreate
psql -U postgres -d laptop_store

DROP SCHEMA public CASCADE;
CREATE SCHEMA public;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO public;

\i database/schema.sql
```

## 🚀 PRODUCTION DEPLOYMENT

### Checklist:

1. **Database**
   - [ ] Backup database
   - [ ] Change default passwords
   - [ ] Configure pg_hba.conf
   - [ ] Enable SSL connection

2. **Security**
   - [ ] Set DEBUG_MODE = false
   - [ ] Use environment variables for secrets
   - [ ] Configure HTTPS
   - [ ] Enable CSRF tokens
   - [ ] Set secure cookie flags

3. **Performance**
   - [ ] Enable opcode caching (OPcache)
   - [ ] Configure PostgreSQL for production
   - [ ] Set up CDN for static assets
   - [ ] Enable gzip compression

4. **Monitoring**
   - [ ] Set up error logging
   - [ ] Configure monitoring (New Relic, etc.)
   - [ ] Set up backup automation
   - [ ] Configure alerts

## 📝 DATABASE SCHEMA

### Main Tables:

- **users**: User accounts and authentication
- **products**: Product catalog
- **orders**: Customer orders
- **order_items**: Items in each order
- **payments**: Payment transactions
- **cart_items**: Shopping cart items
- **financial_records**: Accounting records
- **activity_logs**: System audit trail

### Key Features:

- ✅ Foreign key constraints
- ✅ Indexes for performance
- ✅ Triggers for automation
- ✅ Views for reporting
- ✅ Transaction support

## 📞 SUPPORT

Nếu bạn gặp vấn đề:

1. Kiểm tra [Troubleshooting](#-troubleshooting)
2. Xem log files trong `logs/`
3. Kiểm tra PostgreSQL logs
4. Enable DEBUG_MODE trong config.php

## 📄 LICENSE

MIT License - Free to use for personal and commercial projects

## 🎯 NEXT STEPS

Sau khi cài đặt thành công:

1. Đăng nhập admin và thay đổi password
2. Thêm sản phẩm mới
3. Test flow mua hàng
4. Cấu hình email settings
5. Tùy chỉnh giao diện theo brand
6. Tích hợp payment gateway thực

---

**Made with ❤️ for Vietnamese E-commerce**

Version: 1.0.0
Last Updated: December 2024
>>>>>>> f3e632f (Initial commit)
