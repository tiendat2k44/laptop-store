<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php'; // để có $auth hoặc class Auth

$title = "Sản phẩm - LaptopStore";

// Setup DB connection (prefer helper get_pdo)
$pdo = null;
try {
    if (function_exists('get_pdo')) {
        $pdo = get_pdo();
    } elseif (isset($db) && $db instanceof PDO) {
        $pdo = $db;
    } else {
        // Fallback: create PDO from config constants (Postgres)
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
            throw new Exception('Thiếu cấu hình cơ sở dữ liệu. Vui lòng kiểm tra includes/config.php');
        }
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, defined('DB_PORT') ? DB_PORT : '5432', DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
} catch (Exception $e) {
    // Log and show friendly message later
    error_log('DB connection error in products.php: ' . $e->getMessage());
    $pdo = null;
}

// Ensure $auth exists (best-effort)
if (!isset($auth) && class_exists('Auth')) {
    try {
        $auth = new Auth();
    } catch (Throwable $e) {
        $auth = null;
    }
}

// Lấy tham số filter
$category = trim((string)($_GET['category'] ?? ''));
$brand = trim((string)($_GET['brand'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Prepare base where clause and params (named)
$where = ['is_active = true'];
$params = [];

// Filters
if ($category !== '') {
    $where[] = 'category = :category';
    $params[':category'] = $category;
}

if ($brand !== '') {
    $where[] = 'brand = :brand';
    $params[':brand'] = $brand;
}

if ($search !== '') {
    $where[] = '(name ILIKE :s OR brand ILIKE :s OR category ILIKE :s)';
    $params[':s'] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);

// Count total (safe)
$total = 0;
$totalPages = 1;
$products = [];
$brands = [];
$categories = [];

if ($pdo) {
    try {
        $countSql = "SELECT COUNT(*) AS total FROM products WHERE $whereSql";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        $total = isset($row['total']) ? (int)$row['total'] : 0;
        $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

        // Get products with pagination - LIMIT/OFFSET are integers -> safe to inject directly
        $limitInt = (int)$limit;
        $offsetInt = (int)$offset;
        $prodSql = "SELECT id, name, brand, category, image_url, price, discount_price, stock_quantity, created_at
                    FROM products
                    WHERE $whereSql
                    ORDER BY created_at DESC
                    LIMIT $limitInt OFFSET $offsetInt";
        $stmt = $pdo->prepare($prodSql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        // Get unique brands and categories for filters
        $brandsStmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' ORDER BY brand");
        $brands = $brandsStmt->fetchAll();

        $catsStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> '' ORDER BY category");
        $categories = $catsStmt->fetchAll();
    } catch (Exception $e) {
        error_log('Products query error: ' . $e->getMessage());
        $products = [];
        $brands = [];
        $categories = [];
        $total = 0;
        $totalPages = 1;
        $queryError = 'Đã xảy ra lỗi khi lấy danh sách sản phẩm. Vui lòng thử lại sau.';
    }
} else {
    $queryError = 'Không thể kết nối đến cơ sở dữ liệu.';
}

include '../includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4"><?php echo htmlspecialchars($title); ?></h1>

    <?php if (!empty($queryError)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($queryError); ?></div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" novalidate>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control"
                           placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="brand" class="form-select">
                        <option value="">Tất cả hãng</option>
                        <?php foreach ($brands as $b): 
                            $bname = $b['brand'] ?? '';
                        ?>
                        <option value="<?php echo htmlspecialchars($bname); ?>" 
                            <?php echo ($brand === $bname) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bname); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $c):
                            $cname = $c['category'] ?? '';
                        ?>
                        <option value="<?php echo htmlspecialchars($cname); ?>"
                            <?php echo ($category === $cname) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cname); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
    <div class="alert alert-info">
        Không tìm thấy sản phẩm nào. Vui lòng thử từ khóa tìm kiếm khác.
    </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($products as $product): 
            $img = htmlspecialchars($product['image_url'] ?? 'default.jpg');
            $pname = htmlspecialchars($product['name'] ?? '');
            $pid = (int)($product['id'] ?? 0);
            $brandName = htmlspecialchars($product['brand'] ?? '');
            $catName = htmlspecialchars($product['category'] ?? '');
            $price = isset($product['price']) ? (int)$product['price'] : 0;
            $discount = array_key_exists('discount_price', $product) && $product['discount_price'] !== null ? (int)$product['discount_price'] : null;
            $stock = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="card h-100 product-card">
                <div class="position-relative">
                    <img src="<?php echo getProductImage($product['image_url'] ?? 'default.jpg'); ?>"
                         class="card-img-top" 
                         alt="<?php echo $pname; ?>"
                         style="height: 200px; object-fit: contain;">
                    
                    <?php if ($discount !== null && $discount < $price): ?>
                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                        Sale
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title product-name mb-2">
                        <a href="product-detail.php?id=<?php echo $pid; ?>" class="text-decoration-none text-dark">
                            <?php echo $pname; ?>
                        </a>
                    </h5>
                    
                    <p class="card-text mb-2">
                        <span class="badge bg-secondary"><?php echo $brandName; ?></span>
                        <span class="badge bg-light text-dark"><?php echo $catName; ?></span>
                    </p>
                    
                    <div class="product-price mb-3 mt-auto">
                        <?php if ($discount !== null && $discount > 0): ?>
                            <div>
                                <span class="text-danger fw-bold fs-5"><?php echo formatPrice($discount); ?></span>
                                <div><del class="text-muted"><?php echo formatPrice($price); ?></del></div>
                            </div>
                        <?php else: ?>
                            <div><span class="fw-bold fs-5"><?php echo formatPrice($price); ?></span></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-stock mb-3">
                        <?php if ($stock > 0): ?>
                            <span class="text-success"><i class="fas fa-check-circle"></i> Còn <?php echo $stock; ?> sản phẩm</span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fas fa-times-circle"></i> Hết hàng</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-footer bg-white border-top-0">
                    <div class="d-grid gap-2">
                        <a href="product-detail.php?id=<?php echo $pid; ?>" 
                           class="btn btn-outline-primary">Xem chi tiết</a>
                        
                        <?php if ((isset($auth) && method_exists($auth, 'isLoggedIn') && $auth->isLoggedIn()) && $stock > 0): ?>
                        <form method="POST" action="cart.php" class="mb-0">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                            </button>
                        </form>
                        <?php else: ?>
                            <?php if ($stock > 0): ?>
                                <a href="login.php" class="btn btn-outline-secondary w-100">Đăng nhập để mua</a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>Hết hàng</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php
            // Build base query without page param to reuse
            $queryBase = $_GET;
            ?>
            <?php if ($page > 1): ?>
            <?php $queryPrev = $queryBase; $queryPrev['page'] = $page - 1; ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query($queryPrev)); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>
            
            <?php
            // Limit number of page links to a reasonable range
            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);
            for ($i = $start; $i <= $end; $i++):
                $q = $queryBase; $q['page'] = $i;
            ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query($q)); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <?php $queryNext = $queryBase; $queryNext['page'] = $page + 1; ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query($queryNext)); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>