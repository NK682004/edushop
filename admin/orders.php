<?php
$page_title = 'Quản Lý Đơn Hàng - EduShop Admin';

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

// Lấy filter status
$status_filter = $_GET['status'] ?? '';

// Phân trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = "1=1";
$params = [];

if ($status_filter) {
    $where .= " AND o.status = ?";
    $params[] = $status_filter;
}

// Đếm tổng
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders o WHERE $where");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Lấy đơn hàng
$stmt = $db->prepare("SELECT o.*, u.full_name, u.email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE $where
                      ORDER BY o.created_at DESC 
                      LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Thống kê theo trạng thái
$stmt = $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$status_counts = [];
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/orders.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <h1>Quản Lý Đơn Hàng</h1>
                <div class="header-actions">
                    <button class="btn-refresh" onclick="location.reload()">🔄 Làm mới</button>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Status Filter -->
            <div class="status-filter">
                <a href="orders.php" class="filter-item <?php echo !$status_filter ? 'active' : ''; ?>">
                    Tất cả (<?php echo $total; ?>)
                </a>
                <?php
                $statuses = [
                    'pending' => ['Chờ xử lý', '⏳'],
                    'processing' => ['Đang xử lý', '🔄'],
                    'shipping' => ['Đang giao', '🚚'],
                    'completed' => ['Hoàn thành', '✅'],
                    'cancelled' => ['Đã hủy', '❌']
                ];
                
                foreach ($statuses as $key => $info):
                    $count = $status_counts[$key] ?? 0;
                ?>
                <a href="orders.php?status=<?php echo $key; ?>" 
                   class="filter-item <?php echo $status_filter === $key ? 'active' : ''; ?>">
                    <?php echo $info[1] . ' ' . $info[0]; ?> (<?php echo $count; ?>)
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Orders Table -->
            <div class="table-card">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <h3>Không có đơn hàng</h3>
                        <p>Chưa có đơn hàng nào <?php echo $status_filter ? 'ở trạng thái này' : ''; ?></p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Khách hàng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thanh toán</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
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
                                <td><strong class="price"><?php echo number_format($order['total_amount']); ?>đ</strong></td>
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
                                    <span class="payment-method">
                                        <?php
                                        $payment = [
                                            'cod' => 'COD',
                                            'bank_transfer' => 'Chuyển khoản',
                                            'momo' => 'MoMo',
                                            'vnpay' => 'VNPay'
                                        ];
                                        echo $payment[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                                           class="btn-action btn-view" title="Xem chi tiết">👁️</a>
                                        <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                                        <a href="order-edit.php?id=<?php echo $order['id']; ?>" 
                                           class="btn-action btn-edit" title="Cập nhật">✏️</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                           class="page-link">« Trước</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="page-link active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                               class="page-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                           class="page-link">Sau »</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

