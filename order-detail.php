<?php
$page_title = 'Chi Tiết Đơn Hàng - EduShop Admin';

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

// Lấy order ID
$order_id = $_GET['id'] ?? 0;

// Lấy thông tin đơn hàng
$stmt = $db->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Lấy chi tiết sản phẩm trong đơn hàng
$stmt = $db->prepare("SELECT oi.*, p.name, p.image 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$status_labels = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

$payment_labels = [
    'cod' => 'COD - Thanh toán khi nhận hàng',
    'bank_transfer' => 'Chuyển khoản ngân hàng',
    'momo' => 'Ví MoMo',
    'vnpay' => 'VNPay'
];
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/order-detail.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <div>
                    <h1>Chi Tiết Đơn Hàng #<?php echo $order['order_number']; ?></h1>
                    <p class="order-date">Đặt ngày: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="header-actions">
                    <a href="orders.php" class="btn btn-secondary">← Quay lại</a>
                    <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                    <a href="order-edit.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">✏️ Chỉnh sửa</a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-secondary">🖨️ In đơn</button>
                </div>
            </div>

            <div class="order-detail-grid">
                <!-- Order Status -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3>📋 Trạng Thái Đơn Hàng</h3>
                    </div>
                    <div class="card-body">
                        <div class="status-timeline">
                            <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'processing', 'shipping', 'completed']) ? 'active' : ''; ?>">
                                <div class="timeline-dot">✓</div>
                                <div class="timeline-content">
                                    <h4>Chờ xử lý</h4>
                                    <p><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'shipping', 'completed']) ? 'active' : ''; ?>">
                                <div class="timeline-dot">✓</div>
                                <div class="timeline-content">
                                    <h4>Đang xử lý</h4>
                                    <p><?php echo $order['status'] !== 'pending' ? date('d/m/Y H:i', strtotime($order['updated_at'])) : '-'; ?></p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo in_array($order['status'], ['shipping', 'completed']) ? 'active' : ''; ?>">
                                <div class="timeline-dot">✓</div>
                                <div class="timeline-content">
                                    <h4>Đang giao hàng</h4>
                                    <p><?php echo in_array($order['status'], ['shipping', 'completed']) ? date('d/m/Y H:i', strtotime($order['updated_at'])) : '-'; ?></p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo $order['status'] === 'completed' ? 'active' : ''; ?>">
                                <div class="timeline-dot">✓</div>
                                <div class="timeline-content">
                                    <h4>Hoàn thành</h4>
                                    <p><?php echo $order['status'] === 'completed' ? date('d/m/Y H:i', strtotime($order['updated_at'])) : '-'; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] === 'cancelled'): ?>
                        <div class="cancelled-notice">
                            <p>❌ Đơn hàng đã bị hủy</p>
                            <small>Hủy lúc: <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="current-status">
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo $status_labels[$order['status']] ?? $order['status']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3>👤 Thông Tin Khách Hàng</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-group">
                            <label>Họ tên:</label>
                            <p><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                        </div>
                        <div class="info-group">
                            <label>Email:</label>
                            <p><?php echo htmlspecialchars($order['email']); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Số điện thoại:</label>
                            <p><?php echo htmlspecialchars($order['phone']); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Địa chỉ giao hàng:</label>
                            <p><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                        </div>
                        <?php if ($order['notes']): ?>
                        <div class="info-group">
                            <label>Ghi chú:</label>
                            <p class="note-text"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3>💳 Thanh Toán</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-group">
                            <label>Phương thức:</label>
                            <p><strong><?php echo $payment_labels[$order['payment_method']] ?? $order['payment_method']; ?></strong></p>
                        </div>
                        <div class="info-group">
                            <label>Trạng thái thanh toán:</label>
                            <p>
                                <?php 
                                // COD = chưa thanh toán, các phương thức khác = đã thanh toán
                                $is_paid = $order['payment_method'] !== 'cod';
                                ?>
                                <?php if ($is_paid): ?>
                                    <span class="payment-badge paid">✓ Đã thanh toán</span>
                                <?php else: ?>
                                    <span class="payment-badge unpaid">⏳ Chưa thanh toán (COD)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="detail-card">
                <div class="card-header">
                    <h3>📦 Sản Phẩm Đã Đặt</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <?php if ($item['image']): ?>
                                                <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <div class="no-image">📦</div>
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['price']); ?>đ</td>
                                    <td><span class="quantity-badge">x<?php echo $item['quantity']; ?></span></td>
                                    <td><strong class="subtotal"><?php echo number_format($item['price'] * $item['quantity']); ?>đ</strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Tạm tính:</span>
                            <span><?php echo number_format($order['subtotal']); ?>đ</span>
                        </div>
                        <div class="summary-row">
                            <span>Phí vận chuyển:</span>
                            <span><?php echo number_format($order['shipping_fee']); ?>đ</span>
                        </div>
                        <?php if ($order['discount'] > 0): ?>
                        <div class="summary-row discount">
                            <span>Giảm giá:</span>
                            <span>-<?php echo number_format($order['discount']); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span>Tổng cộng:</span>
                            <span><?php echo number_format($order['total_amount']); ?>đ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
@media print {
    .dashboard-sidebar,
    .header-actions,
    .btn {
        display: none !important;
    }
    
    .dashboard-main {
        padding: 0;
    }
    
    .detail-card {
        page-break-inside: avoid;
    }
}
</style>

