<?php
$page_title = 'Giỏ hàng - EduShop';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'cart.php';
    header('Location: login.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Lấy sản phẩm trong giỏ hàng
$stmt = $db->prepare("SELECT c.*, p.name, p.slug, p.price, p.sale_price, p.image, p.quantity as stock 
                      FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ? AND p.is_active = 1
                      ORDER BY c.created_at DESC");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Tính tổng tiền
$total = 0;
foreach ($cart_items as $item) {
    $item_price = $item['sale_price'] ?? $item['price'];
    $total += $item_price * $item['quantity'];
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<link rel="stylesheet" href="assets/css/cart.css">
<main class="main-content">
    <div class="container">
        <h1>🛒 Giỏ Hàng Của Bạn</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-icon">🛒</div>
                <h2>Giỏ hàng trống</h2>
                <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                <a href="home.php" class="btn btn-primary">Tiếp tục mua sắm</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <?php
                                $item_price = $item['sale_price'] ?? $item['price'];
                                $item_total = $item_price * $item['quantity'];
                                ?>
                                <tr data-cart-id="<?php echo $item['id']; ?>">
                                    <td class="cart-product">
                                        <div class="product-info">
                                            <a href="product.php?slug=<?php echo $item['slug']; ?>">
                                                <?php if ($item['image']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php else: ?>
                                                    <div class="no-image-small">📦</div>
                                                <?php endif; ?>
                                            </a>
                                            <div>
                                                <a href="product.php?slug=<?php echo $item['slug']; ?>" class="product-name">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                                <?php if ($item['quantity'] > $item['stock']): ?>
                                                    <p class="stock-warning">⚠️ Chỉ còn <?php echo $item['stock']; ?> sản phẩm</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cart-price">
                                        <?php if ($item['sale_price']): ?>
                                            <span class="price-sale"><?php echo number_format($item_price); ?>đ</span>
                                            <span class="price-original"><?php echo number_format($item['price']); ?>đ</span>
                                        <?php else: ?>
                                            <span class="price"><?php echo number_format($item_price); ?>đ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cart-quantity">
                                        <div class="quantity-control">
                                            <button type="button" class="qty-btn qty-minus" data-cart-id="<?php echo $item['id']; ?>">−</button>
                                            <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock']; ?>" 
                                                   data-cart-id="<?php echo $item['id']; ?>"
                                                   data-price="<?php echo $item_price; ?>">
                                            <button type="button" class="qty-btn qty-plus" data-cart-id="<?php echo $item['id']; ?>" 
                                                    data-max="<?php echo $item['stock']; ?>">+</button>
                                        </div>
                                    </td>
                                    <td class="cart-total" data-cart-id="<?php echo $item['id']; ?>">
                                        <strong><?php echo number_format($item_total); ?>đ</strong>
                                    </td>
                                    <td class="cart-remove">
                                        <button type="button" class="btn-remove" data-cart-id="<?php echo $item['id']; ?>" title="Xóa">🗑️</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cart-summary">
                    <h3>Tóm tắt đơn hàng</h3>
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span class="subtotal"><?php echo number_format($total); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span class="shipping">
                            <?php 
                            $shipping_fee = $total >= 200000 ? 0 : 30000;
                            echo $shipping_fee > 0 ? number_format($shipping_fee) . 'đ' : 'Miễn phí';
                            ?>
                        </span>
                    </div>
                    <?php if ($total < 200000): ?>
                        <div class="free-shipping-notice">
                            <p>💡 Mua thêm <?php echo number_format(200000 - $total); ?>đ để được miễn phí vận chuyển</p>
                        </div>
                    <?php endif; ?>
                    <div class="summary-total">
                        <span>Tổng cộng:</span>
                        <span class="final-total"><?php echo number_format($total + $shipping_fee); ?>đ</span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary btn-block">Tiến hành đặt hàng</a>
                    <a href="home.php" class="btn btn-secondary btn-block">Tiếp tục mua sắm</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
// Cập nhật số lượng
document.querySelectorAll('.qty-input, .qty-btn').forEach(el => {
    el.addEventListener('click', function(e) {
        if (this.classList.contains('qty-minus') || this.classList.contains('qty-plus')) {
            const cartId = this.dataset.cartId;
            const input = document.querySelector(`.qty-input[data-cart-id="${cartId}"]`);
            let currentQty = parseInt(input.value);
            
            if (this.classList.contains('qty-minus') && currentQty > 1) {
                input.value = currentQty - 1;
            } else if (this.classList.contains('qty-plus')) {
                const max = parseInt(this.dataset.max);
                if (currentQty < max) {
                    input.value = currentQty + 1;
                }
            }
            updateCartQuantity(cartId, parseInt(input.value));
        }
    });
    
    if (el.classList.contains('qty-input')) {
        el.addEventListener('change', function() {
            const cartId = this.dataset.cartId;
            const qty = parseInt(this.value);
            const max = parseInt(this.max);
            
            if (qty < 1) this.value = 1;
            if (qty > max) this.value = max;
            
            updateCartQuantity(cartId, parseInt(this.value));
        });
    }
});

function updateCartQuantity(cartId, quantity) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', quantity);
    
    fetch('handlers/update_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật tổng tiền từng sản phẩm
            const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
            const totalCell = row.querySelector('.cart-total');
            totalCell.querySelector('strong').textContent = new Intl.NumberFormat('vi-VN').format(data.item_total) + 'đ';
            
            // Cập nhật tổng đơn hàng
            document.querySelector('.subtotal').textContent = new Intl.NumberFormat('vi-VN').format(data.cart_total) + 'đ';
            document.querySelector('.final-total').textContent = new Intl.NumberFormat('vi-VN').format(data.final_total) + 'đ';
            
            // Cập nhật badge giỏ hàng
            const badge = document.querySelector('.cart-link .badge');
            if (badge) badge.textContent = data.cart_count;
        }
    });
}

// Xóa sản phẩm
document.querySelectorAll('.btn-remove').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
        
        const cartId = this.dataset.cartId;
        const formData = new FormData();
        formData.append('cart_id', cartId);
        
        fetch('handlers/remove_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });
});
</script>