```php
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaptopStore - Laptop Chính Hãng Giá Tốt Nhất</title>
    <meta name="description" content="Mua laptop chính hãng với giá tốt nhất. Dell, HP, Asus, Apple, Lenovo. Giao hàng toàn quốc.">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-color);
            background-color: #fff;
        }

        /* Header */
        .main-header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .navbar-brand i {
            margin-right: 8px;
        }

        .search-form {
            max-width: 500px;
        }

        .search-form .form-control {
            border-radius: 8px 0 0 8px;
            border-right: none;
        }

        .search-form .btn {
            border-radius: 0 8px 8px 0;
        }

        .cart-icon {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
            color: white;
            padding: 60px 0;
        }

        .hero-section h1 {
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero-section .lead {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .hero-image {
            max-width: 100%;
            height: auto;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Product Cards */
        .product-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            background: white;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .product-image-wrapper {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .product-image-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image-wrapper img {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--danger-color);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-card .card-body {
            padding: 20px;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 600;
            height: 48px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 12px;
            color: var(--dark-color);
        }

        .product-price {
            margin-bottom: 12px;
        }

        .current-price {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--danger-color);
        }

        .old-price {
            font-size: 0.95rem;
            color: #94a3b8;
            text-decoration: line-through;
            margin-left: 8px;
        }

        .product-stock {
            font-size: 0.875rem;
            margin-bottom: 16px;
        }

        /* Brand Section */
        .brands-section {
            background: var(--light-bg);
            padding: 60px 0;
        }

        .brand-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            height: 100%;
        }

        .brand-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }

        .brand-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 12px;
        }

        .brand-name {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        /* Features Section */
        .features-section {
            padding: 60px 0;
        }

        .feature-box {
            text-align: center;
            padding: 30px 20px;
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .feature-box h5 {
            font-weight: 600;
            margin-bottom: 12px;
        }

        .feature-box p {
            color: #64748b;
            margin: 0;
        }

        /* Footer */
        .main-footer {
            background: var(--dark-color);
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }

        .footer-links a {
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            color: #94a3b8;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            border-radius: 8px;
        }

        /* Section Titles */
        .section-title {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 30px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 40px 0;
            }

            .hero-section h1 {
                font-size: 1.8rem;
            }

            .product-image-wrapper {
                height: 180px;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <i class="fas fa-laptop"></i>
                    LaptopStore
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <form class="d-flex mx-auto search-form">
                        <input class="form-control" type="search" placeholder="Tìm kiếm laptop..." aria-label="Search">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#products">Sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#brands">Thương hiệu</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="alert('Chức năng đăng nhập - Cần kết nối PHP')">
                                <i class="fas fa-user"></i> Đăng nhập
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="#" onclick="alert('Giỏ hàng - Cần kết nối PHP')">
                                <span class="cart-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span class="cart-count">3</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4">Laptop Chính Hãng<br>Giá Tốt Nhất</h1>
                    <p class="lead">Khám phá bộ sưu tập laptop đa dạng từ các thương hiệu hàng đầu với mức giá ưu đãi. Giao hàng nhanh, bảo hành chính hãng.</p>
                    <div class="mt-4">
                        <a href="#products" class="btn btn-light btn-lg px-4 me-2">
                            <i class="fas fa-shopping-bag"></i> Mua Ngay
                        </a>
                        <a href="#brands" class="btn btn-outline-light btn-lg px-4">
                            Xem Thương Hiệu
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center mt-4 mt-lg-0">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 500 400'%3E%3Crect fill='%23fff' x='50' y='80' width='400' height='240' rx='20'/%3E%3Crect fill='%23e2e8f0' x='70' y='100' width='360' height='180'/%3E%3Crect fill='%2364748b' x='80' y='110' width='340' height='160'/%3E%3Crect fill='%23334155' x='50' y='320' width='400' height='20' rx='5'/%3E%3Ccircle fill='%23cbd5e1' cx='250' cy='330' r='8'/%3E%3C/svg%3E" 
                         alt="Laptop" class="hero-image" style="max-width: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5" id="products">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">Sản Phẩm Nổi Bật</h2>
                <a href="#" class="btn btn-outline-primary" onclick="alert('Xem tất cả sản phẩm')">
                    Xem Tất Cả <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            
            <div class="row g-4">
                <!-- Product 1 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card product-card">
                        <div class="product-image-wrapper">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 200'%3E%3Crect fill='%23f1f5f9' width='300' height='200'/%3E%3Crect fill='%23cbd5e1' x='30' y='40' width='240' height='120' rx='8'/%3E%3Crect fill='%2364748b' x='40' y='50' width='220' height='100'/%3E%3Ctext x='150' y='110' font-family='Arial' font-size='24' fill='%23fff' text-anchor='middle'%3EDELL%3C/text%3E%3C/svg%3E" 
                                 alt="Dell XPS 13">
                            <span class="product-badge">-7%</span>
                        </div>
                        <div class="card-body">
                            <h5 class="product-name">Dell XPS 13 9310 - Intel Core i7 Gen 11</h5>
                            <p class="mb-2">
                                <span class="badge bg-secondary">Dell</span>
                            </p>
                            <div class="product-price">
                                <span class="current-price">27.990.000₫</span>
                                <del class="old-price">29.990.000₫</del>
                            </div>
                            <div class="product-stock mb-3">
                                <small class="text-success">
                                    <i class="fas fa-check-circle"></i> Còn hàng
                                </small>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="alert('Xem chi tiết sản phẩm')">
                                    <i class="fas fa-eye"></i> Chi Tiết
                                </button>
                                <button class="btn btn-success btn-sm" onclick="addToCart('Dell XPS 13')">
                                    <i class="fas fa-cart-plus"></i> Thêm Vào Giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product 2 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card product-card">
                        <div class="product-image-wrapper">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 200'%3E%3Crect fill='%23f1f5f9' width='300' height='200'/%3E%3Crect fill='%23e2e8f0' x='30' y='40' width='240' height='120' rx='8'/%3E%3Crect fill='%2394a3b8' x='40' y='50' width='220' height='100'/%3E%3Ctext x='150' y='110' font-family='Arial' font-size='20' fill='%23fff' text-anchor='middle'%3EMacBook%3C/text%3E%3C/svg%3E" 
                                 alt="MacBook Air M2">
                            <span class="product-badge">-5%</span>
                        </div>
                        <div class="card-body">
                            <h5 class="product-name">MacBook Air M2 2022 - 8GB RAM 256GB SSD</h5>
                            <p class="mb-2">
                                <span class="badge bg-secondary">Apple</span>
                            </p>
                            <div class="product-price">
                                <span class="current-price">31.490.000₫</span>
                                <del class="old-price">32.990.000₫</del>
                            </div>
                            <div class="product-stock mb-3">
                                <small class="text-success">
                                    <i class="fas fa-check-circle"></i> Còn hàng
                                </small>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="alert('Xem chi tiết sản phẩm')">
                                    <i class="fas fa-eye"></i> Chi Tiết
                                </button>
                                <button class="btn btn-success btn-sm" onclick="addToCart('MacBook Air M2')">
                                    <i class="fas fa-cart-plus"></i> Thêm Vào Giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product 3 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card product-card">
                        <div class="product-image-wrapper">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 200'%3E%3Crect fill='%23f1f5f9' width='300' height='200'/%3E%3Crect fill='%23dc2626' x='30' y='40' width='240' height='120' rx='8'/%3E%3Crect fill='%23991b1b' x='40' y='50' width='220' height='100'/%3E%3Ctext x='150' y='110' font-family='Arial' font-size='24' fill='%23fff' text-anchor='middle'%3EROG%3C/text%3E%3C/svg%3E" 
                                 alt="Asus ROG">
                        </div>
                        <div class="card-body">
                            <h5 class="product-name">Asus ROG Zephyrus G14 - Ryzen 9 RTX 3060</h5>
                            <p class="mb-2">
                                <span class="badge bg-danger">Asus</span>
                            </p>
                            <div class="product-price">
                                <span class="current-price">35.990.000₫</span>
                            </div>
                            <div class="product-stock mb-3">
                                <small class="text-success">
                                    <i class="fas fa-check-circle"></i> Còn hàng
                                </small>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="alert('Xem chi tiết sản phẩm')">
                                    <i class="fas fa-eye"></i> Chi Tiết
                                </button>
                                <button class="btn btn-success btn-sm" onclick="addToCart('Asus ROG G14')">
                                    <i class="fas fa-cart-plus"></i> Thêm Vào Giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product 4 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card product-card">
                        <div class="product-image-wrapper">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 200'%3E%3Crect fill='%23f1f5f9' width='300' height='200'/%3E%3Crect fill='%23334155' x='30' y='40' width='240' height='120' rx='8'/%3E%3Crect fill='%231e293b' x='40' y='50' width='220' height='100'/%3E%3Ctext x='150' y='105' font-family='Arial' font-size='18' fill='%23ef4444' text-anchor='middle'%3EThinkPad%3C/text%3E%3C/svg%3E" 
                                 alt="Lenovo ThinkPad">
                            <span class="product-badge">-5%</span>
                        </div>
                        <div class="card-body">
                            <h5 class="product-name">Lenovo ThinkPad X1 Carbon Gen 9 - i7 16GB</h5>
                            <p class="mb-2">
                                <span class="badge bg-dark">Lenovo</span>
                            </p>
                            <div class="product-price">
                                <span class="current-price">26.490.000₫</span>
                                <del class="old-price">27.990.000₫</del>
                            </div>
                            <div class="product-stock mb-3">
                                <small class="text-success">
                                    <i class="fas fa-check-circle"></i> Còn hàng
                                </small>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="alert('Xem chi tiết sản phẩm')">
                                    <i class="fas fa-eye"></i> Chi Tiết
                                </button>
                                <button class="btn btn-success btn-sm" onclick="addToCart('ThinkPad X1')">
                                    <i class="fas fa-cart-plus"></i> Thêm Vào Giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Brands Section -->
    <section class="brands-section" id="brands">
        <div class="container">
            <h2 class="section-title text-center">Thương Hiệu Nổi Bật</h2>
            <div class="row g-4">
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="#" class="brand-card" onclick="alert('Xem sản phẩm Dell')">
                        <div class="brand-icon">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <h6 class="brand-name">Dell</h6>
                    </a>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="#" class="brand-card" onclick="alert('Xem sản phẩm Apple')">
                        <div class="brand-icon">
                            <i class="fab fa-apple"></i>
                        </div>
                        <h6 class="brand-name">Apple</h6>
                    </a>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="#" class="brand-card" onclick="alert('Xem sản phẩm Asus')">
                        <div class="brand-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h6 class="brand-name">Asus</h6>
                    </a>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="#" class="brand-card" onclick="alert('Xem sản phẩm Lenovo')">
                        <div class="brand-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <h6 class="brand-name">Lenovo</h6>
                    </a>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="#" class="brand-card" onclick="alert('Xem sản phẩm HP')">
                        <div class="brand-icon">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <h6 class="brand-name">HP</h6>
                    </a>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="#" class="brand-card" onclick="alert('Xem sản phẩm MSI')">
                        <div class="brand-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <h6 class="brand-name">MSI</h6>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title text-center mb-5">Tại Sao Chọn LaptopStore?</h2>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5>Bảo Hành Chính Hãng</h5>
                        <p>Bảo hành 12-36 tháng tại các trung tâm ủy quyền</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h5>Giao Hàng Toàn Quốc</h5>
                        <p>Miễn phí giao hàng cho đơn từ 5 triệu đồng</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h5>Thanh Toán Linh Hoạt</h5>
                        <p>COD, chuyển khoản, trả góp 0% lãi suất</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h5>Hỗ Trợ 24/7</h5>
                        <p>Đội ngũ tư vấn hỗ trợ nhiệt tình mọi lúc</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-laptop me-2"></i> LaptopStore
                    </h5>
                    <p>Chuyên cung cấp laptop chính hãng từ các thương hiệu hàng đầu với giá cạnh tranh và dịch vụ tốt nhất.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Thông Tin</h5>
                    <ul class="footer-links list-unstyled">
                        <li><a href="#" onclick="alert('Chính sách bảo mật')">Chính sách bảo mật</a></li>
                        <li><a href="#" onclick="alert('Điều khoản dịch vụ')">Điều khoản dịch vụ</a></li>
                        <li><a href="#" onclick="alert('Chính sách hoàn tiền')">Chính sách hoàn tiền</a></li>
                        <li><a href="#" onclick="alert('Chính sách vận chuyển')">Chính sách vận chuyển</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Hỗ Trợ</h5>
                    <ul class="footer-links list-unstyled">
                        <li><a href="#" onclick="alert('Câu hỏi thường gặp')">Câu hỏi thường gặp</a></li>
                        <li><a href="#" onclick="alert('Liên hệ hỗ trợ')">Liên hệ hỗ trợ</a></li>
                        <li><a href="#" onclick="alert('Hướng dẫn mua hàng')">Hướng dẫn mua hàng</a></li>
                        <li><a href="#" onclick="alert('Trả góp')">Trả góp</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="mb-3">Kết Nối Với Chúng Tôi</h5>
                    <div class="d-flex gap-3 mb-3">
                        <a href="#" class="text-white fs-4" onclick="alert('Facebook')"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white fs-4" onclick="alert('Instagram')"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white fs-4" onclick="alert('YouTube')"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="text-white fs-4" onclick="alert('Twitter')"><i class="fab fa-twitter"></i></a>
                    </div>
                    <p>Liên hệ: 0123 456 789<br>Email: support@laptopstore.vn</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 LaptopStore. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        function addToCart(productName) {
            // Ở đây bạn có thể thêm logic thực tế với PHP/AJAX
            alert(`Đã thêm "${productName}" vào giỏ hàng!`);
            // Cập nhật số lượng giỏ hàng ví dụ
            document.querySelector('.cart-count').textContent = parseInt(document.querySelector('.cart-count').textContent) + 1;
        }
    </script>

</body>
</html>
```