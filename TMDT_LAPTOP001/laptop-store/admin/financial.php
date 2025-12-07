<?php
require_once '../includes/config.php';
require_once '../includes/payment_functions.php';

// Kiểm tra admin
if (!isAdmin()) {
    redirect('../pages/login.php');
}

$title = "Quản Lý Thu Chi";
include '../includes/header.php';

// Lấy báo cáo thu chi
$summary = PaymentProcessor::getFinancialSummary();
$records = PaymentProcessor::getFinancialReport();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Tổng Thu</h6>
                    <h3 class="card-text"><?php echo formatPrice($summary['total_revenue'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">Tổng Chi</h6>
                    <h3 class="card-text"><?php echo formatPrice($summary['total_expense'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Số Dư</h6>
                    <h3 class="card-text">
                        <?php 
                        $balance = ($summary['total_revenue'] ?? 0) - ($summary['total_expense'] ?? 0);
                        echo formatPrice($balance); 
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Tổng Giao Dịch</h6>
                    <h3 class="card-text"><?php echo count($records); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Lịch Sử Thu Chi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Loại</th>
                            <th>Số tiền</th>
                            <th>Mô tả</th>
                            <th>Tham chiếu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $record['type'] == 'THU' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $record['type']; ?>
                                </span>
                            </td>
                            <td class="<?php echo $record['type'] == 'THU' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatPrice($record['amount']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['description']); ?></td>
                            <td><?php echo $record['reference_type'] . ' #' . $record['reference_id']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>