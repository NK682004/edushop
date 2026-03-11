<?php
$page_title = 'Quản Lý Khách Hàng - EduShop Admin';

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

// Phân trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search
$search = $_GET['search'] ?? '';
$where = "role = 'customer'";
$params = [];

if ($search) {
    $where .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Đếm tổng
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE $where");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Lấy danh sách khách hàng
$stmt = $db->prepare("SELECT u.*,
                             (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
                             (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status = 'completed') as total_spent
                      FROM users u
                      WHERE $where
                      ORDER BY u.created_at DESC
                      LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/customers.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <h1>Quản Lý Khách Hàng</h1>
            </div>

            <!-- Search & Filter -->
            <div class="search-card">
                <form method="GET" class="search-form">
                    <div class="search-input-group">
                        <input type="text" name="search" placeholder="Tìm theo tên, email, số điện thoại..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
                        <?php if ($search): ?>
                        <a href="customers.php" class="btn btn-secondary">✖ Xóa lọc</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Stats -->
            <div class="customer-stats">
                <div class="stat-item">
                    <span class="stat-label">Tổng khách hàng:</span>
                    <span class="stat-value"><?php echo number_format($total); ?></span>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="table-card">
                <?php if (empty($customers)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>Không tìm thấy khách hàng</h3>
                        <p><?php echo $search ? 'Thử tìm kiếm với từ khóa khác' : 'Chưa có khách hàng nào'; ?></p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Số điện thoại</th>
                                <th>Địa chỉ</th>
                                <th>Tổng đơn</th>
                                <th>Tổng chi tiêu</th>
                                <th>Ngày tham gia</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['id']; ?></td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($customer['email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></td>
                                <td class="address-cell">
                                    <?php echo htmlspecialchars($customer['address'] ? substr($customer['address'], 0, 30) . '...' : '-'); ?>
                                </td>
                                <td><span class="badge"><?php echo number_format($customer['total_orders'] ?? 0); ?></span></td>
                                <td><strong class="revenue"><?php echo number_format($customer['total_spent'] ?? 0); ?>đ</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $customer['is_active'] ? 'Hoạt động' : 'Tạm khóa'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="customer-detail.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn-action btn-view" title="Xem">👁️</a>
                                        <a href="customer-orders.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn-action btn-orders" title="Đơn hàng">🛒</a>
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
                        <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="page-link">« Trước</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="page-link active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="page-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="page-link">Sau »</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

