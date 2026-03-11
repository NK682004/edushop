<?php
$page_title = 'Dashboard - EduShop Admin';

require_once __DIR__ . '../config/database.php';
include __DIR__ . '/includes/admin-header.php';

// Check if user logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get user info
$db = getDB();
$stmt = $db->prepare("SELECT role, full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Lưu tên user vào session cho sidebar
$_SESSION['user_name'] = $user['full_name'];

// Thống kê tổng quan
$stats = [];

// Tổng đơn hàng
$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $stmt->fetch()['total'];

// Đơn hàng hôm nay
$stmt = $db->query("SELECT COUNT(*) as today FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['today_orders'] = $stmt->fetch()['today'];

// Tổng doanh thu
$stmt = $db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'completed'");
$stats['total_revenue'] = $stmt->fetch()['revenue'] ?? 0;

// Doanh thu tháng này
$stmt = $db->query("SELECT SUM(total_amount) as monthly FROM orders 
                     WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURDATE()) 
                     AND YEAR(created_at) = YEAR(CURDATE())");
$stats['monthly_revenue'] = $stmt->fetch()['monthly'] ?? 0;

// Tổng sản phẩm
$stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
$stats['total_products'] = $stmt->fetch()['total'];

// Sản phẩm sắp hết hàng
$stmt = $db->query("SELECT COUNT(*) as low_stock FROM products WHERE quantity < 10 AND is_active = 1");
$stats['low_stock'] = $stmt->fetch()['low_stock'];

// Tổng khách hàng
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$stats['total_customers'] = $stmt->fetch()['total'];

// Khách hàng mới tháng này
$stmt = $db->query("SELECT COUNT(*) as new_customers FROM users 
                     WHERE role = 'customer' AND MONTH(created_at) = MONTH(CURDATE()) 
                     AND YEAR(created_at) = YEAR(CURDATE())");
$stats['new_customers'] = $stmt->fetch()['new_customers'];

// Đơn hàng gần đây
$stmt = $db->query("SELECT o.*, u.full_name, u.email 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    ORDER BY o.created_at DESC 
                    LIMIT 10");
$recent_orders = $stmt->fetchAll();

// Sản phẩm bán chạy
$stmt = $db->query("SELECT p.*, SUM(oi.quantity) as sold_count 
                    FROM products p 
                    JOIN order_items oi ON p.id = oi.product_id 
                    JOIN orders o ON oi.order_id = o.id
                    WHERE p.is_active = 1 AND o.status = 'completed'
                    GROUP BY p.id 
                    ORDER BY sold_count DESC 
                    LIMIT 5");
$top_products = $stmt->fetchAll();

// Thống kê đơn hàng theo trạng thái
$stmt = $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$order_stats = [];
while ($row = $stmt->fetch()) {
    $order_stats[$row['status']] = $row['count'];
}
?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                </div>
                <div class="header-actions">
                    <button class="btn-refresh" onclick="location.reload()">🔄 Làm mới</button>
                    <span class="current-time" id="currentTime"></span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-orders">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_orders']); ?></h3>
                        <p>Tổng Đơn Hàng</p>
                        <span class="stat-badge">+<?php echo $stats['today_orders']; ?> hôm nay</span>
                    </div>
                </div>

                <div class="stat-card stat-revenue">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_revenue']); ?>đ</h3>
                        <p>Tổng Doanh Thu</p>
                        <span class="stat-badge"><?php echo number_format($stats['monthly_revenue']); ?>đ tháng này</span>
                    </div>
                </div>

                <div class="stat-card stat-products">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_products']); ?></h3>
                        <p>Sản Phẩm</p>
                        <span class="stat-badge warning"><?php echo $stats['low_stock']; ?> sắp hết</span>
                    </div>
                </div>

                <div class="stat-card stat-customers">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_customers']); ?></h3>
                        <p>Khách Hàng</p>
                        <span class="stat-badge">+<?php echo $stats['new_customers']; ?> mới</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <div class="chart-card">
                    <h3>📈 Thống Kê Đơn Hàng</h3>
                    <div class="order-status-chart">
                        <?php
                        $status_labels = [
                            'pending' => ['Chờ xử lý', '#ed8936'],
                            'processing' => ['Đang xử lý', '#4299e1'],
                            'shipping' => ['Đang giao', '#667eea'],
                            'completed' => ['Hoàn thành', '#48bb78'],
                            'cancelled' => ['Đã hủy', '#f56565']
                        ];
                        
                        foreach ($status_labels as $status => $info):
                            $count = $order_stats[$status] ?? 0;
                            $percentage = $stats['total_orders'] > 0 ? ($count / $stats['total_orders']) * 100 : 0;
                        ?>
                        <div class="status-item">
                            <div class="status-label">
                                <span class="status-dot" style="background: <?php echo $info[1]; ?>"></span>
                                <span><?php echo $info[0]; ?></span>
                            </div>
                            <div class="status-count"><?php echo $count; ?></div>
                            <div class="status-bar">
                                <div class="status-progress" style="width: <?php echo $percentage; ?>%; background: <?php echo $info[1]; ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>🔥 Sản Phẩm Bán Chạy</h3>
                    <div class="top-products-list">
                        <?php if (empty($top_products)): ?>
                            <p class="no-data">Chưa có dữ liệu</p>
                        <?php else: ?>
                            <?php foreach ($top_products as $product): ?>
                            <div class="product-item">
                                <div class="product-image">
                                    <?php if ($product['image']): ?>
                                        <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image-small">📦</div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <p>Đã bán: <?php echo $product['sold_count']; ?></p>
                                </div>
                                <div class="product-price">
                                    <?php echo number_format($product['sale_price'] ?? $product['price']); ?>đ
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="recent-orders-card">
                <div class="card-header">
                    <h3>📋 Đơn Hàng Gần Đây</h3>
                    <a href="orders.php" class="btn-view-all">Xem tất cả →</a>
                </div>
                <div class="orders-table-container">
                    <?php if (empty($recent_orders)): ?>
                        <p class="no-data">Chưa có đơn hàng nào</p>
                    <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Khách hàng</th>
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
                                    <span class="order-id">#<?php echo $order['order_number']; ?></span>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($order['full_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td><strong><?php echo number_format($order['total_amount']); ?>đ</strong></td>
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
                                    <div class="action-buttons">
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-action btn-view" title="Xem">👁️</a>
                                        <a href="order-edit.php?id=<?php echo $order['id']; ?>" class="btn-action btn-edit" title="Sửa">✏️</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>⚡ Thao Tác Nhanh</h3>
                <div class="actions-grid">
                    <a href="product-add.php" class="action-card">
                        <div class="action-icon">➕</div>
                        <h4>Thêm Sản Phẩm</h4>
                        <p>Thêm sản phẩm mới vào cửa hàng</p>
                    </a>
                    <a href="category-add.php" class="action-card">
                        <div class="action-icon">📁</div>
                        <h4>Thêm Danh Mục</h4>
                        <p>Tạo danh mục sản phẩm mới</p>
                    </a>
                    <a href="orders.php?status=pending" class="action-card">
                        <div class="action-icon">⏳</div>
                        <h4>Đơn Chờ Xử Lý</h4>
                        <p>Xem đơn hàng cần xử lý</p>
                    </a>
                    <a href="reports.php" class="action-card">
                        <div class="action-icon">📊</div>
                        <h4>Báo Cáo</h4>
                        <p>Xem báo cáo thống kê chi tiết</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    document.getElementById('currentTime').textContent = timeString;
}

updateTime();
setInterval(updateTime, 1000);

// Auto refresh every 5 minutes
setTimeout(() => {
    location.reload();
}, 300000);
</script>

