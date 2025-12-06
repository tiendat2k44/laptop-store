<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/cart_functions.php';

$auth = new Auth();
$db = Database::getInstance();

// Get filter parameters
$brand = clean($_GET['brand'] ?? '');
$category = clean($_GET['category'] ?? '');
$search = clean($_GET['search'] ?? '');
$minPrice = (int)($_GET['min_price'] ?? 0);
$maxPrice = (int)($_GET['max_price'] ?? 0);
$sortBy = clean($_GET['sort'] ?? 'newest');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Build query
$where = ['p.is_active = true'];
$params = [];

if ($brand) {
    $where[] = 'p.brand = :brand';
    $params[':brand'] = $brand;
}

if ($category) {
    $where[] = 'p.category = :category';
    $params[':category'] = $category;
}

if ($search) {
    $where[] = '(p.name ILIKE :search OR p.brand ILIKE :search OR p.category ILIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($minPrice > 0) {
    $where[] = 'COALESCE(p.discount_price, p.price) >= :min_price';
    $params[':min_price'] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = 'COALESCE(p.discount_price, p.price) <= :max_price';
    $params[':max_price'] = $maxPrice;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Sort
$orderBy = match($sortBy) {
    'price_asc' => 'COALESCE(p.discount_price, p.price) ASC',
    'price_desc' => 'COALESCE(p.discount_price, p.price) DESC',
    'name' => 'p.name ASC',
    'popular' => 'p.sold_count DESC',
    default => 'p.created_at DESC'
};

// Get products
$sql = "SELECT p.* FROM products p $whereClause ORDER BY $orderBy LIMIT :limit OFFSET :offset";
$params[':limit'] = $perPage;
$params[':offset'] = $offset;
$products = $db->select($sql, $params);

// Get total count
$countSql = "SELECT COUNT(*) as total FROM products p $whereClause";
unset($params[':limit'], $params[':offset']);
$totalResult = $db->selectOne($countSql, $params);
$totalProducts = $totalResult['total'];
$totalPages = ceil($totalProducts / $perPage);

// Get brands for filter
$brands = $db->select("SELECT DISTINCT brand FROM products WHERE is_active = true ORDER BY brand");

// Get categories
$categories = $db->select("SELECT DISTINCT category FROM products WHERE is_active = true ORDER BY category");

$pageTitle = $search ? "Tìm kiếm: $search" : ($brand ? "Laptop $brand" : 'Danh sách sản phẩm');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - LaptopStore</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .navbar { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .product-card { border-radius: 12px; transition: all 0.3s; height: 100%; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); }
        .product-image { height: 200px; object-fit: contain; padding: 20px; }
        .badge-discount { position: absolute; top: 10px; right: 10px; }
        .filter-card { position: sticky; top: 80px; }
        .price-badge { font-size: 1.25rem; font-weight: 700; color: #ef4444; }
        .old-price { text-decoration: line-through; color: #94a3b8; font-size: 0.9rem; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-laptop"></i> LaptopStore
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto" method="GET" style="max-width: 500px;">
                    <input class="form-control" type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm laptop...">
                    <button class="btn btn-primary ms-2" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="products.php">Sản phẩm</a></li>
                    <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Đơn hàng</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> 
                        <?php $count = getCartCount(); if ($count > 0): ?>
                        <span class="badge bg-danger"><?php echo $count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><?php echo $_SESSION['user_name']; ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                    <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Đăng nhập</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php echo displayFlashMessages(); ?>
        
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="card filter-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-filter"></i> Bộ lọc
                        </h5>
                        
                        <form method="GET" id="filterForm">
                            <!-- Brands -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Thương hiệu</h6>
                                <?php foreach ($brands as $b): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="brand" value="<?php echo htmlspecialchars($b['brand']); ?>" 
                                           id="brand_<?php echo htmlspecialchars($b['brand']); ?>"
                                           <?php echo $brand === $b['brand'] ? 'checked' : ''; ?>
                                           onchange="this.form.submit()">
                                    <label class="form-check-label" for="brand_<?php echo htmlspecialchars($b['brand']); ?>">
                                        <?php echo htmlspecialchars($b['brand']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <?php if ($brand): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="clearFilter('brand')">Xóa lọc</button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Categories -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Phân loại</h6>
                                <?php foreach ($categories as $c): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" value="<?php echo htmlspecialchars($c['category']); ?>"
                                           id="cat_<?php echo htmlspecialchars($c['category']); ?>"
                                           <?php echo $category === $c['category'] ? 'checked' : ''; ?>
                                           onchange="this.form.submit()">
                                    <label class="form-check-label" for="cat_<?php echo htmlspecialchars($c['category']); ?>">
                                        <?php echo htmlspecialchars($c['category']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <?php if ($category): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="clearFilter('category')">Xóa lọc</button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Khoảng giá</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" class="form-control form-control-sm" name="min_price" 
                                               value="<?php echo $minPrice ?: ''; ?>" placeholder="Từ">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control form-control-sm" name="max_price" 
                                               value="<?php echo $maxPrice ?: ''; ?>" placeholder="Đến">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">Áp dụng</button>
                            </div>
                            
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                            
                            <a href="products.php" class="btn btn-outline-danger btn-sm w-100">
                                <i class="fas fa-redo"></i> Xóa tất cả bộ lọc
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Products -->
            <div class="col-lg-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><?php echo $pageTitle; ?></h2>
                        <p class="text-muted mb-0">Tìm thấy <?php echo $totalProducts; ?> sản phẩm</p>
                    </div>
                    <div>
                        <select class="form-select" name="sort" onchange="location.href=updateQueryParam('sort', this.value)">
                            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                            <option value="popular" <?php echo $sortBy === 'popular' ? 'selected' : ''; ?>>Bán chạy</option>
                            <option value="price_asc" <?php echo $sortBy === 'price_asc' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                            <option value="price_desc" <?php echo $sortBy === 'price_desc' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Tên A-Z</option>
                        </select>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Không tìm thấy sản phẩm phù hợp. Vui lòng thử lại với bộ lọc khác.
                </div>
                <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="<?php echo getProductImage($product['image_url']); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php if ($product['discount_price']): ?>
                                <span class="badge bg-danger badge-discount">
                                    -<?php echo calculateDiscountPercentage($product['price'], $product['discount_price']); ?>%
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title" style="height: 48px; overflow: hidden;">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h5>
                                <p class="mb-2">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product['brand']); ?></span>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span>
                                </p>
                                <div class="mb-3">
                                    <?php if ($product['discount_price']): ?>
                                    <span class="price-badge"><?php echo formatPrice($product['discount_price']); ?></span>
                                    <span class="old-price"><?php echo formatPrice($product['price']); ?></span>
                                    <?php else: ?>
                                    <span class="price-badge"><?php echo formatPrice($product['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                    <small class="text-success"><i class="fas fa-check-circle"></i> Còn hàng</small>
                                    <?php else: ?>
                                    <small class="text-danger"><i class="fas fa-times-circle"></i> Hết hàng</small>
                                    <?php endif; ?>
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </a>
                                    <?php if ($auth->isLoggedIn() && $product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-success btn-sm" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Trước</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Sau</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearFilter(param) {
            const url = new URL(window.location);
            url.searchParams.delete(param);
            window.location = url;
        }
        
        function updateQueryParam(key, value) {
            const url = new URL(window.location);
            url.searchParams.set(key, value);
            return url.toString();
        }
        
        function addToCart(productId) {
            fetch('../api/cart-add.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({product_id: productId, quantity: 1})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Đã thêm sản phẩm vào giỏ hàng!');
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            });
        }
    </script>
</body>
</html>
?>