<?php
require_once 'config/database.php';

$search_query = $_GET['q'] ?? '';
$search_query = trim($search_query);

$page_title = 'Tìm kiếm: ' . htmlspecialchars($search_query) . ' - EduShop';
include 'includes/header.php';

$db = getDB();
$products = [];
$total_products = 0;

if (!empty($search_query)) {
    // Phân trang
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 12;
    $offset = ($page - 1) * $per_page;
    
    // Tìm kiếm trong tên và mô tả sản phẩm
    $search_term = '%' . $search_query . '%';
    
    // Đếm tổng số sản phẩm tìm được
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM products 
                          WHERE (name LIKE ? OR description LIKE ?) AND is_active = 1");
    $stmt->execute([$search_term, $search_term]);
    $total_products = $stmt->fetch()['total'];
    $total_pages = ceil($total_products / $per_page);
    
    // Lấy sản phẩm
    $stmt = $db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.is_active = 1 
                          ORDER BY 
                            CASE 
                              WHEN p.name LIKE ? THEN 1
                              ELSE 2
                            END,
                            p.created_at DESC 
                          LIMIT $per_page OFFSET $offset");
    $stmt->execute([$search_term, $search_term, $search_term]);
    $products = $stmt->fetchAll();
}
?>
<link rel="stylesheet" href="assets/css/category.css">
<style>
.search-header {
    background: linear-gradient(135deg, #d12f2fff 0%, #af2849ff 100%);
    color: white;
    padding: 40px 20px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.search-header h1 {
    font-size: 2rem;
    margin-bottom: 10px;
}

.search-header .search-term {
    font-size: 1.5rem;
    font-weight: bold;
    color: #ffd700;
}

.search-stats {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.search-stats .result-info {
    font-size: 1.1rem;
    color: #333;
}

.search-stats .result-info strong {
    color: #667eea;
    font-size: 1.3rem;
}

.search-form-inline {
    flex: 1;
    max-width: 400px;
}

.search-form-inline form {
    display: flex;
    gap: 10px;
}

.search-form-inline input {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
}

.search-form-inline button {
    padding: 12px 25px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.search-form-inline button:hover {
    background: #5568d3;
    transform: translateY(-2px);
}

.empty-search {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
}

.empty-search .icon {
    font-size: 5rem;
    margin-bottom: 20px;
}

.empty-search h2 {
    color: #333;
    margin-bottom: 15px;
}

.empty-search p {
    color: #666;
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.search-suggestions {
    margin-top: 30px;
    text-align: left;
    display: inline-block;
}

.search-suggestions h3 {
    color: #667eea;
    margin-bottom: 15px;
}

.search-suggestions ul {
    list-style: none;
    padding: 0;
}

.search-suggestions li {
    padding: 8px 0;
    color: #555;
}

.search-suggestions li:before {
    content: "✓ ";
    color: #667eea;
    font-weight: bold;
}

.highlight {
    background-color: #fff3cd;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
}
</style>

<main class="main-content">
    <div class="container">
        <div class="search-header">
            <h1>Kết quả tìm kiếm</h1>
            <?php if (!empty($search_query)): ?>
                <p>Từ khóa: <span class="search-term">"<?php echo htmlspecialchars($search_query); ?>"</span></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($search_query)): ?>
            <div class="search-stats">
                <div class="result-info">
                    Tìm thấy <strong><?php echo $total_products; ?></strong> sản phẩm
                    <?php if (!empty($products)): ?>
                        (Hiển thị <?php echo count($products); ?> sản phẩm)
                    <?php endif; ?>
                </div>
                
                <div class="search-form-inline">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" placeholder="Tìm kiếm khác..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                        <button type="submit">Tìm</button>
                    </form>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="empty-search">
                    <div class="icon">📭</div>
                    <h2>Không tìm thấy sản phẩm nào</h2>
                    <p>Không có kết quả phù hợp với từ khóa "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
                    
                    <div class="search-suggestions">
                        <h3>Gợi ý:</h3>
                        <ul>
                            <li>Kiểm tra lỗi chính tả của từ khóa</li>
                            <li>Thử sử dụng từ khóa khác hoặc tổng quát hơn</li>
                            <li>Tìm kiếm theo danh mục sản phẩm</li>
                            <li>Liên hệ với chúng tôi để được tư vấn</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $final_price = $product['sale_price'] ?? $product['price'];
                        $discount_percent = 0;
                        if ($product['sale_price']) {
                            $discount_percent = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                        }
                        ?>
                        <div class="product-card">
                            <a href="product.php?slug=<?php echo $product['slug']; ?>">
                                <div class="product-image">
                                    <?php if ($product['image']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">📦</div>
                                    <?php endif; ?>
                                    <?php if ($product['sale_price']): ?>
                                        <span class="badge-sale">-<?php echo $discount_percent; ?>%</span>
                                    <?php endif; ?>
                                    <?php if ($product['quantity'] <= 0): ?>
                                        <span class="badge-out-stock">Hết hàng</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="product-price">
                                        <span class="price-sale"><?php echo number_format($final_price); ?>đ</span>
                                        <?php if ($product['sale_price']): ?>
                                            <span class="price-original"><?php echo number_format($product['price']); ?>đ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                           
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?q=<?php echo urlencode($search_query); ?>&page=<?php echo $page - 1; ?>" class="page-link">« Trước</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="page-link active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?q=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?q=<?php echo urlencode($search_query); ?>&page=<?php echo $page + 1; ?>" class="page-link">Sau »</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-search">
                <div class="icon">🔍</div>
                <h2>Vui lòng nhập từ khóa tìm kiếm</h2>
                <p>Nhập tên sản phẩm hoặc từ khóa liên quan để tìm kiếm</p>
                
                <div class="search-form-inline" style="margin-top: 30px; max-width: 500px;">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." required autofocus>
                        <button type="submit">🔍 Tìm kiếm</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
