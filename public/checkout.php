<?php
$page_title = 'Thanh toán - EduShop';
include 'includes/header.php';

// QUAN TRỌNG: Include ShippingCalculator
require_once __DIR__ . '/includes/ShippingCalculator.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Lấy thông tin user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Lấy sản phẩm trong giỏ hàng
$stmt = $db->prepare("SELECT c.*, p.name, p.price, p.sale_price, p.image, p.quantity as stock 
                      FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ? AND p.is_active = 1");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Nếu giỏ hàng trống
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// ===== TÍNH TOÁN GIÁ TRỊ ĐƠN HÀNG =====

// 1. Tính subtotal (tạm tính)
$subtotal = 0;
foreach ($cart_items as $item) {
    $item_price = $item['sale_price'] ?? $item['price'];
    $subtotal += $item_price * $item['quantity'];
}

// 2. Tính phí vận chuyển
$shippingCalculator = new ShippingCalculator($db);
$shippingInfo = $shippingCalculator->calculate($subtotal);
$shipping_fee = $shippingInfo['fee'];
$shipping_message = $shippingInfo['message'];

// 3. Giảm giá (tạm thời = 0)
$discount = 0;

// 4. Tổng cộng
$total = $subtotal + $shipping_fee - $discount;

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<link rel="stylesheet" href="assets/css/checkout.css">
<main class="main-content">
    <div class="container">
        <h1>💳 Thanh Toán</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="handlers/place_order.php" method="POST" class="checkout-form">
            <div class="checkout-container">
                <div class="checkout-info">
                    <div class="section-box">
                        <h2>Thông tin người nhận</h2>
                        <div class="form-group">
                            <label for="full_name">Họ và tên <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" required 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Số điện thoại <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" required 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       pattern="[0-9]{10,11}"
                                       placeholder="0123456789">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Địa chỉ giao hàng <span class="required">*</span></label>
                            <textarea id="address" name="address" rows="3" required 
                                      placeholder="Số nhà, đường, phường, quận/huyện, tỉnh/thành phố"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Ghi chú đơn hàng</label>
                            <textarea id="notes" name="notes" rows="3" 
                                      placeholder="Ghi chú về đơn hàng, ví dụ: thời gian hay chỉ dẫn địa điểm giao hàng chi tiết hơn."></textarea>
                        </div>
                    </div>
                    
                    <div class="section-box">
                        <h2>Phương thức thanh toán</h2>
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <div class="method-info">
                                    <strong>💵 Thanh toán khi nhận hàng (COD)</strong>
                                    <p>Thanh toán bằng tiền mặt khi nhận hàng</p>
                                    <small style="color: #f57c00;">⏳ Chưa thanh toán - Thu tiền khi giao</small>
                                </div>
                            </label>
                            
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="bank_transfer">
                                <div class="method-info">
                                    <strong>🏦 Chuyển khoản ngân hàng</strong>
                                    <p>Chuyển khoản trước khi giao hàng</p>
                                    <small style="color: #388e3c;">✓ Thanh toán ngay</small>
                                </div>
                            </label>
                            
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="momo">
                                <div class="method-info">
                                    <strong>📱 Ví MoMo</strong>
                                    <p>Thanh toán qua ví điện tử MoMo</p>
                                    <small style="color: #388e3c;">✓ Thanh toán ngay</small>
                                </div>
                            </label>
                            
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="vnpay">
                                <div class="method-info">
                                    <strong>💳 VNPay</strong>
                                    <p>Thanh toán qua VNPay QR</p>
                                    <small style="color: #388e3c;">✓ Thanh toán ngay</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="checkout-summary">
                    <div class="section-box">
                        <h2>Đơn hàng của bạn</h2>
                        
                        <div class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                                <?php
                                $item_price = $item['sale_price'] ?? $item['price'];
                                $item_total = $item_price * $item['quantity'];
                                ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <?php if ($item['image']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <div class="no-image-small">📦</div>
                                        <?php endif; ?>
                                        <span class="item-qty"><?php echo $item['quantity']; ?></span>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-price"><?php echo number_format($item_total); ?>đ</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Tạm tính:</span>
                                <span><?php echo number_format($subtotal); ?>đ</span>
                            </div>
                            <div class="summary-row">
                                <span>Phí vận chuyển:</span>
                                <span><?php echo $shipping_fee > 0 ? number_format($shipping_fee) . 'đ' : 'Miễn phí'; ?></span>
                            </div>
                            <?php if ($discount > 0): ?>
                            <div class="summary-row discount">
                                <span>Giảm giá:</span>
                                <span>-<?php echo number_format($discount); ?>đ</span>
                            </div>
                            <?php endif; ?>
                            <div class="summary-total">
                                <span>Tổng cộng:</span>
                                <span><?php echo number_format($total); ?>đ</span>
                            </div>
                        </div>
                        
                        <!-- Thông báo về phí ship -->
                        <?php if ($shipping_fee > 0 && isset($shippingInfo['free_shipping_threshold'])): ?>
                            <?php 
                            $remaining = $shippingInfo['free_shipping_threshold'] - $subtotal;
                            if ($remaining > 0):
                            ?>
                            <div class="shipping-notice">
                                <p>🎁 Mua thêm <strong><?php echo number_format($remaining); ?>đ</strong> để được <strong>MIỄN PHÍ SHIP</strong>!</p>
                            </div>
                            <?php endif; ?>
                        <?php elseif ($shipping_fee == 0): ?>
                            <div class="shipping-notice success">
                                <p>🎉 Bạn được <strong>MIỄN PHÍ SHIP</strong>!</p>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary btn-block btn-large">Đặt hàng</button>
                        
                        <div class="checkout-note">
                            <p>🔒 Thông tin của bạn được bảo mật</p>
                            <p>📞 Hotline hỗ trợ: 1900 xxxx</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
// Xác nhận đặt hàng
document.querySelector('.checkout-form').addEventListener('submit', function(e) {
    const button = this.querySelector('button[type="submit"]');
    const phone = document.getElementById('phone').value;
    const address = document.getElementById('address').value;
    
    // Validate phone
    if (!/^[0-9]{10,11}$/.test(phone)) {
        e.preventDefault();
        alert('Vui lòng nhập số điện thoại hợp lệ (10-11 số)');
        return false;
    }
    
    
    
    button.disabled = true;
    button.textContent = 'Đang xử lý...';
});
</script>