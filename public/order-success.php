<?php
$page_title = 'Đặt hàng thành công - EduShop';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// Lấy thông tin đơn hàng
$stmt = $db->prepare("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Lấy chi tiết sản phẩm
$stmt = $db->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>

<link rel="stylesheet" href="assets/css/order-success.css">

<main class="main-content">
    <div class="container">
        <div class="success-container">
            <!-- Success Icon -->
            <div class="success-icon">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="#4caf50"/>
                    <path d="M30 50 L45 65 L70 35" stroke="white" stroke-width="6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            
            <!-- Success Message -->
            <h1>🎉 Đặt hàng thành công!</h1>
            <p class="success-subtitle">Cảm ơn bạn đã đặt hàng tại EduShop</p>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Order Info -->
            <div class="order-info-card">
                <div class="order-header">
                    <h2>Thông tin đơn hàng</h2>
                    <span class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
                
                <div class="order-details">
                    <div class="detail-row">
                        <span class="detail-label">📅 Ngày đặt:</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">💳 Thanh toán:</span>
                        <span class="detail-value">
                            <?php
                            $payment_methods = [
                                'cod' => 'COD - Thanh toán khi nhận hàng',
                                'bank_transfer' => 'Chuyển khoản ngân hàng',
                                'momo' => 'Ví MoMo',
                                'vnpay' => 'VNPay'
                            ];
                            echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                            ?>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">📍 Giao đến:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">📞 Số điện thoại:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['shipping_phone']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="order-items-card">
                <h3>Sản phẩm đã đặt</h3>
                <div class="items-list">
                    <?php foreach ($order_items as $item): ?>
                    <div class="item-row">
                        <div class="item-image">
                            <?php if ($item['image']): ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">📦</div>
                            <?php endif; ?>
                        </div>
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-quantity">Số lượng: <?php echo $item['quantity']; ?></div>
                        </div>
                        <div class="item-price"><?php echo number_format($item['price'] * $item['quantity']); ?>đ</div>
                    </div>
                    <?php endforeach; ?>
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
                        <span><?php echo number_format($order['total_amount']); ?>đ</span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Notice -->
            <?php if ($order['payment_method'] === 'cod'): ?>
            <div class="payment-notice">
                <h4>💵 Thanh toán khi nhận hàng</h4>
                <p>Vui lòng chuẩn bị <strong><?php echo number_format($order['total_amount']); ?>đ</strong> khi nhận hàng.</p>
                <p>Bạn có thể thanh toán bằng tiền mặt cho shipper.</p>
            </div>
            <?php elseif ($order['payment_status'] === 'paid'): ?>
            <div class="payment-notice success">
                <h4>✅ Đã thanh toán thành công</h4>
                <p>Chúng tôi đã nhận được thanh toán của bạn.</p>
                <p>Đơn hàng sẽ được xử lý và giao trong thời gian sớm nhất.</p>
            </div>
            <?php endif; ?>
            
            <!-- Next Steps -->
            <div class="next-steps">
                <h3>📋 Bước tiếp theo</h3>
                <div class="steps-grid">
                    <div class="step">
                        <div class="step-number">1</div>
                        <p>Chúng tôi sẽ xác nhận đơn hàng qua email/SMS</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <p>Đơn hàng được đóng gói và giao cho đơn vị vận chuyển</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <p>Bạn nhận hàng và kiểm tra sản phẩm</p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="index.php" class="btn btn-secondary">🏠 Về trang chủ</a>
                <a href="products.php" class="btn btn-primary">🛍️ Tiếp tục mua sắm</a>
            </div>
            
            <!-- Contact -->
            <div class="contact-info">
                <p>📧 Email: support@edushop.com | 📞 Hotline: 1900 xxxx</p>
                <p>Cần hỗ trợ? Liên hệ chúng tôi bất cứ lúc nào!</p>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>