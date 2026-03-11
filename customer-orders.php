<?php
$page_title = 'Đơn Hàng Của Tôi - EduShop';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'my-orders.php';
    header('Location: login.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Lấy filter status
$status_filter = $_GET['status'] ?? '';

// Build query
$where = "user_id = ?";
$params = [$user_id];

if ($status_filter) {
    $where .= " AND status = ?";
    $params[] = $status_filter;
}

// Lấy đơn hàng
$stmt = $db->prepare("SELECT * FROM orders WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Thống kê theo trạng thái
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM orders WHERE user_id = ? GROUP BY status");
$stmt->execute([$user_id]);
$status_counts = [];
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/my-orders.css">

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>📦 Đơn Hàng Của Tôi</h1>
            <a href="profile.php" class="btn btn-secondary">← Quay lại</a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Status Filter -->
        <div class="status-filter">
            <a href="my-orders.php" class="filter-tab <?php echo !$status_filter ? 'active' : ''; ?>">
                <span>Tất cả</span>
                <span class="count"><?php echo array_sum($status_counts); ?></span>
            </a>
            
            <?php
            $statuses = [
                'pending' => ['Chờ xử lý', '⏳', 'pending'],
                'processing' => ['Đang xử lý', '🔄', 'processing'],
                'shipping' => ['Đang giao', '🚚', 'shipping'],
                'completed' => ['Hoàn thành', '✅', 'completed'],
                'cancelled' => ['Đã hủy', '❌', 'cancelled']
            ];
            
            foreach ($statuses as $key => $info):
                $count = $status_counts[$key] ?? 0;
            ?>
            <a href="my-orders.php?status=<?php echo $key; ?>" 
               class="filter-tab <?php echo $status_filter === $key ? 'active' : ''; ?> status-<?php echo $info[2]; ?>">
                <span><?php echo $info[1] . ' ' . $info[0]; ?></span>
                <span class="count"><?php echo $count; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <div class="empty-icon">📦</div>
                <h3>Không có đơn hàng</h3>
                <p><?php echo $status_filter ? 'Bạn chưa có đơn hàng nào ở trạng thái này' : 'Bạn chưa có đơn hàng nào'; ?></p>
                <a href="products.php" class="btn btn-primary">Mua sắm ngay</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                    // Lấy sản phẩm trong đơn hàng
                    $stmt = $db->prepare("
                        SELECT oi.*, p.name, p.image, p.slug 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?
                        LIMIT 3
                    ");
                    $stmt->execute([$order['id']]);
                    $order_items = $stmt->fetchAll();
                    
                    // Đếm tổng số sản phẩm
                    $stmt = $db->prepare("SELECT COUNT(*) as total FROM order_items WHERE order_id = ?");
                    $stmt->execute([$order['id']]);
                    $total_items = $stmt->fetch()['total'];
                    ?>
                    
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <span class="order-number">Đơn hàng #<?php echo $order['order_number']; ?></span>
                                <span class="order-date">📅 <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php
                                $status_labels = [
                                    'pending' => '⏳ Chờ xử lý',
                                    'processing' => '🔄 Đang xử lý',
                                    'shipping' => '🚚 Đang giao',
                                    'completed' => '✅ Hoàn thành',
                                    'cancelled' => '❌ Đã hủy'
                                ];
                                echo $status_labels[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-products">
                                <?php foreach ($order_items as $item): ?>
                                <div class="product-item">
                                    <div class="product-image">
                                        <?php if ($item['image']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <div class="no-image">📦</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <a href="product.php?slug=<?php echo $item['slug']; ?>" class="product-name">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                        <div class="product-meta">
                                            <span>x<?php echo $item['quantity']; ?></span>
                                            <span class="product-price"><?php echo number_format($item['price']); ?>đ</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if ($total_items > 3): ?>
                                <div class="more-products">
                                    <span>+ <?php echo $total_items - 3; ?> sản phẩm khác</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
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
                                <div class="summary-total">
                                    <span>Tổng cộng:</span>
                                    <strong><?php echo number_format($order['total_amount']); ?>đ</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-payment">
                                <?php
                                $payment_methods = [
                                    'cod' => '💵 COD',
                                    'bank_transfer' => '🏦 Chuyển khoản',
                                    'momo' => '📱 MoMo',
                                    'vnpay' => '💳 VNPay'
                                ];
                                echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                ?>
                                
                                <?php if ($order['payment_method'] === 'cod'): ?>
                                    <span class="payment-status unpaid">• Chưa thanh toán</span>
                                <?php else: ?>
                                    <span class="payment-status paid">• Đã thanh toán</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="order-actions">
                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-view">
                                    👁️ Chi tiết
                                </a>
                                
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button onclick="cancelOrder(<?php echo $order['id']; ?>, '<?php echo $order['order_number']; ?>')" 
                                            class="btn btn-cancel">
                                        ❌ Hủy đơn
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'completed'): ?>
                                    <button onclick="reorder(<?php echo $order['id']; ?>)" class="btn btn-reorder">
                                        🔄 Mua lại
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Xác nhận hủy đơn -->
<div id="cancelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>❌ Xác nhận hủy đơn hàng</h3>
            <span class="modal-close" onclick="closeCancelModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Bạn có chắc chắn muốn hủy đơn hàng <strong id="cancelOrderNumber"></strong>?</p>
            <p class="warning-text">⚠️ Hành động này không thể hoàn tác!</p>
            
            <form id="cancelForm" method="POST" action="handlers/cancel-order.php">
                <input type="hidden" name="order_id" id="cancelOrderId">
                
                <div class="form-group">
                    <label>Lý do hủy đơn:</label>
                    <textarea name="cancel_reason" rows="3" placeholder="Nhập lý do hủy đơn (không bắt buộc)"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeCancelModal()" class="btn btn-secondary">
                        Không, giữ đơn hàng
                    </button>
                    <button type="submit" class="btn btn-danger">
                        Có, hủy đơn hàng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Hủy đơn hàng
function cancelOrder(orderId, orderNumber) {
    document.getElementById('cancelOrderId').value = orderId;
    document.getElementById('cancelOrderNumber').textContent = '#' + orderNumber;
    const modal = document.getElementById('cancelModal');
    modal.style.display = 'flex';
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    modal.style.display = 'none';
    // Restore body scroll
    document.body.style.overflow = '';
}

// Đóng modal khi click bên ngoài
window.onclick = function(event) {
    const modal = document.getElementById('cancelModal');
    if (event.target === modal) {
        closeCancelModal();
    }
}

// Đóng modal khi nhấn ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCancelModal();
    }
});

// Mua lại
function reorder(orderId) {
    if (confirm('Thêm tất cả sản phẩm từ đơn hàng này vào giỏ hàng?')) {
        window.location.href = 'handlers/reorder.php?order_id=' + orderId;
    }
}
</script>   