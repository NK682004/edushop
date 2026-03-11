<?php
$page_title = 'Sản phẩm - EduShop';
include 'includes/header.php';

$db = getDB();

// Lấy danh mục cha
$stmt = $db->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY display_order");
$main_categories = $stmt->fetchAll();

// Lấy sản phẩm nổi bật
$stmt = $db->query("SELECT p.*, c.name as category_name 
                    FROM products p 
                    JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_featured = 1 AND p.is_active = 1 
                    LIMIT 8");
$featured_products = $stmt->fetchAll();

// Lấy sản phẩm mới nhất
$stmt = $db->query("SELECT p.*, c.name as category_name 
                    FROM products p 
                    JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_active = 1 
                    ORDER BY p.created_at DESC 
                    LIMIT 12");
$latest_products = $stmt->fetchAll();
?>
<link rel="stylesheet" href="assets/css/home.css">
<main class="main-content">
    <div class="container">
        <!-- Banner Section -->
        <section class="banner-section">
            <div class="banner">
                <h2>🎉 Khuyến mãi đặc biệt - Giảm đến 50%</h2>
                <p>Cho các sản phẩm văn phòng phẩm và sách giáo khoa</p>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="categories-section">
            <h2 class="section-title">Danh Mục Sản Phẩm</h2>
            <div class="categories-grid">
                <?php foreach ($main_categories as $category): ?>
                    <a href="category.php?slug=<?php echo $category['slug']; ?>" class="category-card">
                        <div class="category-icon">
                            <?php
                            $icons = [
                                'bo-sach-giao-khoa' => '📚',
                                'the-gioi-sach' => '📖',
                                'sach-tham-khao-cac-lop' => '📝',
                                'van-phong-pham' => '✏️'
                            ];
                            echo $icons[$category['slug']] ?? '📦';
                            ?>
                        </div>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Featured Products -->
        <?php if (!empty($featured_products)): ?>
        <section class="products-section">
            <h2 class="section-title">⭐ Sản Phẩm Nổi Bật</h2>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <a href="product.php?slug=<?php echo $product['slug']; ?>">
                            <div class="product-image">
                                <?php
                                    $final_price = $product['sale_price'] ?? $product['price'];
                                    $discount_percent = 0;
                                    if ($product['sale_price']) {
                                        $discount_percent = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                                    }
                                    ?>
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">📦</div>
                                <?php endif; ?>
                                <?php if ($product['sale_price']): ?>
                                    <span class="badge-sale">-<?php echo $discount_percent; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="product-price">
                                    <?php if ($product['sale_price']): ?>
                                        <span class="price-sale"><?php echo number_format($product['sale_price']); ?>đ</span>
                                        <span class="price-original"><?php echo number_format($product['price']); ?>đ</span>
                                    <?php else: ?>
                                        <span class="price"><?php echo number_format($product['price']); ?>đ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Latest Products -->
        <section class="products-section">
            <h2 class="section-title">🆕 Sản Phẩm Mới Nhất</h2>
            <div class="products-grid">
                <?php foreach ($latest_products as $product): ?>
                    <div class="product-card">
                        <a href="product.php?slug=<?php echo $product['slug']; ?>">
                            <div class="product-image">
                                 <?php
                                    $final_price = $product['sale_price'] ?? $product['price'];
                                    $discount_percent = 0;
                                    if ($product['sale_price']) {
                                        $discount_percent = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                                    }
                                    ?>
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">📦</div>
                                <?php endif; ?>
                                <?php if ($product['sale_price']): ?>
                                     <span class="badge-sale">-<?php echo $discount_percent; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="product-price">
                                    <?php if ($product['sale_price']): ?>
                                        <span class="price-sale"><?php echo number_format($product['sale_price']); ?>đ</span>
                                        <span class="price-original"><?php echo number_format($product['price']); ?>đ</span>
                                    <?php else: ?>
                                        <span class="price"><?php echo number_format($product['price']); ?>đ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                       
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
// Xử lý thêm vào giỏ hàng bằng AJAX
document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button');
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
                button.textContent = '✓ Đã thêm';
                button.classList.add('success');
                
                // Cập nhật số lượng giỏ hàng
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
});
</script>