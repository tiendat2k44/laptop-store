<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/database.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();

// Get statistics
$stats = [];

// Total revenue
$sql = "SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE status IN ('COMPLETED', 'DELIVERED')";
$result = $db->selectOne($sql);
$stats['total_revenue'] = $result['total'];

// Today's revenue
$sql = "SELECT COALESCE(SUM(final_amount), 0) as total FROM orders 
        WHERE status IN ('COMPLETED', 'DELIVERED') AND DATE(created_at) = CURRENT_DATE";
$result = $db->selectOne($sql);
$stats['today_revenue'] = $result['total'];

// Total orders
$sql = "SELECT COUNT(*) as count FROM orders";
$result = $db->selectOne($sql);
$stats['total_orders'] = $result['count'];

// Pending orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'PENDING'";
$result = $db->selectOne($sql);
$stats['pending_orders'] = $result['count'];

// Total customers
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'CUSTOMER'";
$result = $db->selectOne($sql);
$stats['total_customers'] = $result['count'];

// Total products
$sql = "SELECT COUNT(*) as count FROM products WHERE is_active = true";
$result = $db->selectOne($sql);
$stats['total_products'] = $result['count'];

// Low stock products
$sql = "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 5 AND is_active = true";
$result = $db->selectOne($sql);
$stats['low_stock'] = $result['count'];

// Recent orders
$sql = "SELECT o.*, u.full_name FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC LIMIT 10";
$recentOrders = $db->select($sql);

// Best selling products
$sql = "SELECT * FROM products WHERE is_active = true ORDER BY sold_count DESC LIMIT 5";
$bestSelling = $db->select($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LaptopStore</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #f8f9fa; }
        .sidebar { background: #1e293b; color: white; min-height: 100vh; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #cbd5e1; padding: 12px 20px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .main-content { margin-left: 250px; padding: 20px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-value { font-size: 2rem; font-weight: 700; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4">
            <h4 class="mb-0"><i class="fas fa-laptop"></i> Admin Panel</h4>
            <small class="text-muted">LaptopStore</small>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link active" href="index.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-box"></i> Sản phẩm
            </a>
            <a class="nav-link" href="orders.php">
                <i class="fas fa-shopping-cart"></i> Đơn hàng
                <?php if ($stats['pending_orders'] > 0): ?>
                <span class="badge bg-danger"><?php echo $stats['pending_orders']; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link" href="customers.php">
                <i class="fas fa-users"></i> Khách hàng
            </a>
            <a class="nav-link" href="financial.php">
                <i class="fas fa-chart-line"></i> Tài chính
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-file-alt"></i> Báo cáo
            </a>
            <hr class="text-white-50">
            <a class="nav-link" href="../pages/index.php" target="_blank">
                <i class="fas fa-external-link-alt"></i> Xem website
            </a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Dashboard</h2>
                <p class="text-muted mb-0">Chào mừng trở lại, <?php echo $_SESSION['user_name']; ?>!</p>
            </div>
            <div class="text-muted">
                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i'); ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Tổng doanh thu</div>
                            <div class="stat-value text-primary"><?php echo number_format($stats['total_revenue'] / 1000000, 1); ?>M</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Đơn hàng</div>
                            <div class="stat-value text-success"><?php echo $stats['total_orders']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Khách hàng</div>
                            <div class="stat-value text-info"><?php echo $stats['total_customers']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Sản phẩm</div>
                            <div class="stat-value text-warning"><?php echo $stats['total_products']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Orders -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Đơn Hàng Gần Đây</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày đặt</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo $order['order_code']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                        <td><?php echo formatPrice($order['final_amount']); ?></td>
                                        <td>
                                            <span class="badge <?php echo getOrderStatusBadge($order['status']); ?>">
                                                <?php echo getOrderStatusText($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateTime($order['created_at']); ?></td>
                                        <td>
                                            <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Sản Phẩm Bán Chạy</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($bestSelling as $product): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo getProductImage($product['image_url']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: contain;" class="me-3">
                            <div class="flex-grow-1">
                                <div class="small"><?php echo truncate($product['name'], 30); ?></div>
                                <div class="text-muted small">Đã bán: <?php echo $product['sold_count']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Thông Báo</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($stats['pending_orders'] > 0): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Có <strong><?php echo $stats['pending_orders']; ?></strong> đơn hàng chờ xử lý
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($stats['low_stock'] > 0): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-box"></i>
                            Có <strong><?php echo $stats['low_stock']; ?></strong> sản phẩm sắp hết hàng
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-chart-line"></i>
                            Doanh thu hôm nay: <strong><?php echo formatPrice($stats['today_revenue']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>