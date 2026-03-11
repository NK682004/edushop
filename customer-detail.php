<?php
$page_title = 'Chi Tiết Khách Hàng - EduShop Admin';

require_once __DIR__ . '../config/database.php';
include __DIR__ . '/includes/admin-header.php';

// Check admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Lấy customer ID
$customer_id = $_GET['id'] ?? 0;

// Lấy thông tin khách hàng
$stmt = $db->prepare("SELECT u.*,
                             (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
                             (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status = 'completed') as total_spent,
                             (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'completed') as completed_orders,
                             (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'cancelled') as cancelled_orders
                      FROM users u
                      WHERE u.id = ? AND u.role = 'customer'");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    $_SESSION['error'] = 'Không tìm thấy khách hàng';
    header('Location: customers.php');
    exit;
}

// Lấy đơn hàng gần đây
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$customer_id]);
$recent_orders = $stmt->fetchAll();

// Thống kê theo tháng
$stmt = $db->prepare("SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as order_count,
                        SUM(total_amount) as revenue
                      FROM orders 
                      WHERE user_id = ? AND status = 'completed'
                      GROUP BY month
                      ORDER BY month DESC
                      LIMIT 6");
$stmt->execute([$customer_id]);
$monthly_stats = $stmt->fetchAll();
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/customer-detail.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <div>
                    <h1>👤 Chi Tiết Khách Hàng</h1>
                    <p class="customer-id">ID: #<?php echo $customer['id']; ?></p>
                </div>
                <div class="header-actions">
                    <a href="customers.php" class="btn btn-secondary">← Quay lại</a>
                    <a href="customer-orders.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">📦 Xem đơn hàng</a>
                </div>
            </div>

            <!-- Customer Info Cards -->
            <div class="info-grid">
                <!-- Basic Info -->
                <div class="info-card">
                    <div class="card-header">
                        <h3>Thông Tin Cơ Bản</h3>
                    </div>
                    <div class="card-body">
                        <div class="customer-avatar">
                            <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                        </div>
                        <div class="info-group">
                            <label>Họ và tên:</label>
                            <p><strong><?php echo htmlspecialchars($customer['full_name']); ?></strong></p>
                        </div>
                        <div class="info-group">
                            <label>Email:</label>
                            <p><?php echo htmlspecialchars($customer['email']); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Số điện thoại:</label>
                            <p><?php echo htmlspecialchars($customer['phone'] ?? 'Chưa cập nhật'); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Địa chỉ:</label>
                            <p><?php echo htmlspecialchars($customer['address'] ?? 'Chưa cập nhật'); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Ngày tham gia:</label>
                            <p><?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Trạng thái:</label>
                            <p>
                                <span class="status-badge <?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $customer['is_active'] ? '✓ Hoạt động' : '✗ Tạm khóa'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="info-card">
                    <div class="card-header">
                        <h3>Thống Kê Mua Hàng</h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-icon">🛒</div>
                                <div class="stat-content">
                                    <h4><?php echo number_format($customer['total_orders']); ?></h4>
                                    <p>Tổng đơn hàng</p>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-icon">✅</div>
                                <div class="stat-content">
                                    <h4><?php echo number_format($customer['completed_orders']); ?></h4>
                                    <p>Đơn hoàn thành</p>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-icon">❌</div>
                                <div class="stat-content">
                                    <h4><?php echo number_format($customer['cancelled_orders']); ?></h4>
                                    <p>Đơn đã hủy</p>
                                </div>
                            </div>
                            <div class="stat-box highlight">
                                <div class="stat-icon">💰</div>
                                <div class="stat-content">
                                    <h4><?php echo number_format($customer['total_spent'] ?? 0); ?>đ</h4>
                                    <p>Tổng chi tiêu</p>
                                </div>
                            </div>
                        </div>

                        <?php if ($customer['total_orders'] > 0): ?>
                        <div class="avg-order">
                            <p>Giá trị đơn hàng trung bình:</p>
                            <h3><?php echo number_format(($customer['total_spent'] ?? 0) / max($customer['completed_orders'], 1)); ?>đ</h3>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Monthly Statistics -->
            <?php if (!empty($monthly_stats)): ?>
            <div class="info-card">
                <div class="card-header">
                    <h3>📊 Thống Kê Theo Tháng</h3>
                </div>
                <div class="card-body">
                    <div class="monthly-chart">
                        <?php foreach ($monthly_stats as $stat): ?>
                        <div class="month-item">
                            <div class="month-label"><?php echo date('m/Y', strtotime($stat['month'] . '-01')); ?></div>
                            <div class="month-stats">
                                <span class="order-count"><?php echo $stat['order_count']; ?> đơn</span>
                                <span class="revenue"><?php echo number_format($stat['revenue']); ?>đ</span>
                            </div>
                            <div class="progress-bar">
                                <?php 
                                $max_revenue = max(array_column($monthly_stats, 'revenue'));
                                $percentage = $max_revenue > 0 ? ($stat['revenue'] / $max_revenue) * 100 : 0;
                                ?>
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Orders -->
            <div class="info-card">
                <div class="card-header">
                    <h3>📦 Đơn Hàng Gần Đây</h3>
                    <a href="customer-orders.php?id=<?php echo $customer['id']; ?>" class="view-all">Xem tất cả →</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="empty-state">
                            <p>Khách hàng chưa có đơn hàng nào</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Ngày đặt</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>
                                        <span class="order-number">#<?php echo $order['order_number']; ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td><strong class="amount"><?php echo number_format($order['total_amount']); ?>đ</strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php
                                            $labels = [
                                                'pending' => 'Chờ xử lý',
                                                'processing' => 'Đang xử lý',
                                                'shipping' => 'Đang giao',
                                                'completed' => 'Hoàn thành',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            echo $labels[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-action" title="Xem chi tiết">👁️</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

