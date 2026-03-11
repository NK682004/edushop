<?php
$page_title = 'Chi Tiết Đơn Hàng - EduShop';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'order-detail.php?id=' . ($_GET['id'] ?? '');
    header('Location: login.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;

// Lấy thông tin đơn hàng (chỉ của user hiện tại)
$stmt = $db->prepare("
    SELECT o.* 
    FROM orders o 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    header('Location: my-orders.php');
    exit;
}

// Lấy chi tiết sản phẩm
$stmt = $db->prepare("
    SELECT oi.*, p.name, p.image, p.slug 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$status_labels = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];
?>

<link rel="stylesheet" href="assets/css/order-detail-customer.css">

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>📦 Chi Tiết Đơn Hàng</h1>
                <p class="order-number">Mã đơn: <strong>#<?php echo $order['order_number']; ?></strong></p>
            </div>
            <a href="my-orders.php" class="btn btn-secondary">← Quay lại</a>
        </div>
        
        <!-- Order Status Timeline -->
        <div class="status-timeline-card">
            <h3>📍 Trạng thái đơn hàng</h3>
            <div class="timeline">
                <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'processing', 'shipping', 'completed']) ? 'active' : ''; ?>">
                    <div class="timeline-dot">✓</div>
                    <div class="timeline-content">
                        <strong>Đơn hàng đã đặt</strong>
                        <small><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                    </div>
                </div>
                
                <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'shipping', 'completed']) ? 'active' : ''; ?>">
                    <div class="timeline-dot">✓</div>
                    <div class="timeline-content">
                        <strong>Đã xác nhận</strong>
                        <small><?php echo $order['status'] !== 'pending' && $order['status'] !== 'cancelled' ? date('d/m/Y H:i', strtotime($order['updated_at'])) : 'Chờ xác nhận'; ?></small>
                    </div>
                </div>
                
                <div class="timeline-item <?php echo in_array($order['status'], ['shipping', 'completed']) ? 'active' : ''; ?>">
                    <div class="timeline-dot">✓</div>
                    <div class="timeline-content">
                        <strong>Đang giao hàng</strong>
                        <small><?php echo in_array($order['status'], ['shipping', 'completed']) ? date('d/m/Y H:i', strtotime($order['updated_at'])) : 'Chưa giao'; ?></small>
                    </div>
                </div>
                
                <div class="timeline-item <?php echo $order['status'] === 'completed' ? 'active' : ''; ?>">
                    <div class="timeline-dot">✓</div>
                    <div class="timeline-content">
                        <strong>Đã giao hàng</strong>
                        <small><?php echo $order['status'] === 'completed' ? date('d/m/Y H:i', strtotime($order['updated_at'])) : 'Chưa hoàn thành'; ?></small>
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
        
        <div class="detail-grid">
            <!-- Shipping Info -->
            <div class="detail-card">
                <h3>📍 Thông tin giao hàng</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Địa chỉ:</span>
                        <span class="value"><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Số điện thoại:</span>
                        <span class="value"><?php echo htmlspecialchars($order['shipping_phone']); ?></span>
                    </div>
                    <?php if ($order['notes']): ?>
                    <div class="info-item">
                        <span class="label">Ghi chú:</span>
                        <span class="value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Info -->
            <div class="detail-card">
                <h3>💳 Thanh toán</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Phương thức:</span>
                        <span class="value">
                            <?php
                            $payment_methods = [
                                'cod' => '💵 Thanh toán khi nhận hàng',
                                'bank_transfer' => '🏦 Chuyển khoản',
                                'momo' => '📱 MoMo',
                                'vnpay' => '💳 VNPay'
                            ];
                            echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="label">Trạng thái:</span>
                        <span class="value">
                            <?php if ($order['payment_method'] === 'cod'): ?>
                                <span class="payment-badge unpaid">⏳ Thanh toán khi nhận hàng</span>
                            <?php else: ?>
                                <span class="payment-badge paid">✓ Đã thanh toán</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="detail-card">
            <h3>📦 Sản phẩm đã đặt</h3>
            <div class="products-list">
                <?php foreach ($order_items as $item): ?>
                <div class="product-row">
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
                            <span>Đơn giá: <?php echo number_format($item['price']); ?>đ</span>
                            <span>Số lượng: x<?php echo $item['quantity']; ?></span>
                        </div>
                    </div>
                    <div class="product-total">
                        <?php echo number_format($item['price'] * $item['quantity']); ?>đ
                    </div>
                </div>
                <?php endforeach; ?>
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
                <div class="summary-total">
                    <span>Tổng cộng:</span>
                    <strong><?php echo number_format($order['total_amount']); ?>đ</strong>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <?php if ($order['status'] === 'pending'): ?>
        <div class="order-actions">
            <button onclick="cancelOrder(<?php echo $order['id']; ?>, '<?php echo $order['order_number']; ?>')" 
                    class="btn btn-cancel">
                ❌ Hủy đơn hàng
            </button>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Hủy đơn (giống my-orders.php) -->
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
</script>