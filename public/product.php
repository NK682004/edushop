<?php
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// Lấy thông tin sản phẩm
$stmt = $db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$page_title = $product['name'] . ' - EduShop';
include 'includes/header.php';

// Lấy sản phẩm liên quan
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 
                      LIMIT 6");
$stmt->execute([$product['category_id'], $product['id']]);
$related_products = $stmt->fetchAll();

$final_price = $product['sale_price'] ?? $product['price'];
$discount_percent = 0;
if ($product['sale_price']) {
    $discount_percent = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
}

// Tính phí vận chuyển
$shipping_fee = $final_price >= 200000 ? 0 : 30000;
?>
<link rel="stylesheet" href="assets/css/product.css">
<main class="main-content">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <div class="container">
                <a href="index.php">Trang chủ</a>
                <span>/</span>
                <a href="category.php?slug=<?php echo $product['category_slug']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
                <span>/</span>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
        </div>
        
        <div class="product-detail">
            <div class="product-images">
                <div class="main-image">
                    <?php if ($product['image']): ?>
                         <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="no-image-large">📦</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="product-details">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-category-link">
                    <span>Danh mục: </span>
                    <a href="category.php?slug=<?php echo $product['category_slug']; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </div>
                
                <div class="product-price-section">
                    <?php if ($product['sale_price']): ?>
                        <div class="price-main"><?php echo number_format($final_price); ?>đ</div>
                        <div class="price-compare">
                            <span class="price-old"><?php echo number_format($product['price']); ?>đ</span>
                            <span class="discount-badge">-<?php echo $discount_percent; ?>%</span>
                        </div>
                    <?php else: ?>
                        <div class="price-main"><?php echo number_format($final_price); ?>đ</div>
                    <?php endif; ?>
                </div>
                
                <!-- Thông tin vận chuyển -->
                <div class="shipping-info">
                    <div class="shipping-title">Thông tin vận chuyển</div>
                    <div class="shipping-details">
                        <div class="shipping-item">
                            <span class="shipping-icon">🚚</span>
                            <span>Giao hàng đến: <strong>Bình Thạnh, TPHCM</strong></span>
                        </div>
                        <div class="shipping-item">
                            <span class="shipping-icon">📦</span>
                            <span>Phí vận chuyển: <strong><?php echo $shipping_fee > 0 ? number_format($shipping_fee) . 'đ' : 'Miễn phí'; ?></strong></span>
                        </div>
                    </div>
                </div>
                
                <!-- Khuyến mãi -->
                <div class="promotions">
                    <div class="promotions-title">🎁 Ưu đãi liên quan</div>
                    <div class="promotion-item">
                        <span class="promotion-icon">✓</span>
                        <span>Mã giảm 10k - tối đa 50k cho đơn từ 149k</span>
                    </div>
                    <div class="promotion-item">
                        <span class="promotion-icon">✓</span>
                        <span>Mã giảm 20k - tối đa 100k cho đơn từ 399k</span>
                    </div>
                    <div class="promotion-item">
                        <span class="promotion-icon">✓</span>
                        <span>Freeship giảm 15k - tối đa 20k cho đơn từ 50k</span>
                    </div>
                </div>
                
                <div class="product-stock">
                    <?php if ($product['quantity'] > 0): ?>
                        <span class="in-stock">✓ Còn hàng (<?php echo $product['quantity']; ?> sản phẩm)</span>
                    <?php else: ?>
                        <span class="out-stock">✗ Tạm hết hàng</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['description']): ?>
                <div class="product-description">
                    <h3>Mô tả sản phẩm</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($product['quantity'] > 0): ?>
                <form action="handlers/add_to_cart.php" method="POST" class="add-to-cart-section">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="quantity-selector">
                        <label for="quantity">Số lượng:</label>
                        <div class="quantity-control">
                            <button type="button" class="qty-btn" id="qtyMinus">−</button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                            <button type="button" class="qty-btn" id="qtyPlus">+</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-large">🛒 Thêm vào giỏ hàng</button>
                </form>
                <?php else: ?>
                <div class="out-of-stock-notice">
                    <p>Sản phẩm tạm thời hết hàng. Vui lòng liên hệ shop để được tư vấn.</p>
                </div>
                <?php endif; ?>
                
                <div class="product-features">
                    <div class="feature-item">
                        <span class="icon">🚚</span>
                        <div>
                            <strong>Giao hàng nhanh</strong>
                            <p>Miễn phí ship đơn từ 200k</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="icon">↩️</span>
                        <div>
                            <strong>Đổi trả dễ dàng</strong>
                            <p>Trong vòng 7 ngày</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="icon">💯</span>
                        <div>
                            <strong>Chính hãng 100%</strong>
                            <p>Cam kết hàng chất lượng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sản phẩm liên quan -->
        <?php if (!empty($related_products)): ?>
        <section class="related-products">
            <h2>Sản phẩm liên quan</h2>
            <div class="products-grid">
                <?php foreach ($related_products as $item): ?>
                    <?php
                    $item_final_price = $item['sale_price'] ?? $item['price'];
                    $item_discount = 0;
                    if ($item['sale_price']) {
                        $item_discount = round((($item['price'] - $item['sale_price']) / $item['price']) * 100);
                    }
                    ?>
                    <div class="product-card">
                        <a href="product.php?slug=<?php echo $item['slug']; ?>">
                            <div class="product-image">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">📦</div>
                                <?php endif; ?>
                                <?php if ($item['sale_price']): ?>
                                    <span class="badge-sale">-<?php echo $item_discount; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="product-price">
                                    <span class="price-sale"><?php echo number_format($item_final_price); ?>đ</span>
                                    <?php if ($item['sale_price']): ?>
                                        <span class="price-original"><?php echo number_format($item['price']); ?>đ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
// Xử lý số lượng
const qtyInput = document.getElementById('quantity');
const qtyMinus = document.getElementById('qtyMinus');
const qtyPlus = document.getElementById('qtyPlus');

if (qtyInput) {
    const maxQty = parseInt(qtyInput.max);
    
    qtyMinus?.addEventListener('click', () => {
        let val = parseInt(qtyInput.value);
        if (val > 1) qtyInput.value = val - 1;
    });
    
    qtyPlus?.addEventListener('click', () => {
        let val = parseInt(qtyInput.value);
        if (val < maxQty) qtyInput.value = val + 1;
    });
}

// Xử lý thêm vào giỏ hàng
document.querySelector('.add-to-cart-section')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Đang thêm...';
    
    fetch('handlers/add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.textContent = '✓ Đã thêm vào giỏ hàng';
            button.classList.add('success');
            
            const badge = document.querySelector('.cart-link .badge');
            if (badge) {
                badge.textContent = data.cart_count;
            } else if (data.cart_count > 0) {
                const cartLink = document.querySelector('.cart-link');
                cartLink.innerHTML += ' <span class="badge">' + data.cart_count + '</span>';
            }
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('success');
                button.disabled = false;
            }, 2000);
        } else {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Có lỗi xảy ra!');
                button.textContent = originalText;
                button.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi thêm vào giỏ hàng!');
        button.textContent = originalText;
        button.disabled = false;
    });
});

// Xử lý thêm vào giỏ cho sản phẩm liên quan

</script>