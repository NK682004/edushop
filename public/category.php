<?php
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// Lấy thông tin danh mục
$stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit;
}

$page_title = $category['name'] . ' - EduShop';
include 'includes/header.php';

// Lấy danh mục con (nếu có)
$stmt = $db->prepare("SELECT * FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->execute([$category['id']]);
$sub_categories = $stmt->fetchAll();

// Lấy tất cả ID danh mục cần lấy sản phẩm (bao gồm cả danh mục con)
$category_ids = [$category['id']];
foreach ($sub_categories as $sub) {
    $category_ids[] = $sub['id'];
}

// Phân trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Đếm tổng số sản phẩm
$placeholders = implode(',', array_fill(0, count($category_ids), '?'));
$stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE category_id IN ($placeholders) AND is_active = 1");
$stmt->execute($category_ids);
$total_products = $stmt->fetch()['total'];
$total_pages = ceil($total_products / $per_page);

// Lấy sản phẩm
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE p.category_id IN ($placeholders) AND p.is_active = 1 
                      ORDER BY p.created_at DESC 
                      LIMIT $per_page OFFSET $offset");
$stmt->execute($category_ids);
$products = $stmt->fetchAll();
?>
<link rel="stylesheet" href="assets/css/category.css">
<main class="main-content">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <span><?php echo htmlspecialchars($category['name']); ?></span>
        </div>
        
        <div class="category-header">
            <h1><?php echo htmlspecialchars($category['name']); ?></h1>
            <p class="result-count">Hiển thị <?php echo count($products); ?> trong <?php echo $total_products; ?> sản phẩm</p>
        </div>
        
        <!-- Danh mục con -->
        <?php if (!empty($sub_categories)): ?>
        <div class="sub-categories">
            <h3>Danh mục con:</h3>
            <div class="sub-categories-list">
                <?php foreach ($sub_categories as $sub): ?>
                    <a href="category.php?slug=<?php echo $sub['slug']; ?>" class="sub-category-item">
                        <?php echo htmlspecialchars($sub['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Sản phẩm -->
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>📦 Chưa có sản phẩm nào trong danh mục này.</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product.php?slug=<?php echo $product['slug']; ?>">
                            <div class="product-image">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">📦</div>
                                <?php endif; ?>
                                <?php if ($product['sale_price']): ?>
                                    <span class="badge-sale">SALE</span>
                                <?php endif; ?>
                                <?php if ($product['quantity'] <= 0): ?>
                                    <span class="badge-out-stock">Hết hàng</span>
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
                        <?php if ($product['quantity'] > 0): ?>
                        
                        <?php else: ?>
                        <button class="btn-add-cart" disabled>Hết hàng</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>" class="page-link">« Trước</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="page-link active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>" class="page-link">Sau »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
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