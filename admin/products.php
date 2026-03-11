<?php
$page_title = 'Quản Lý Sản Phẩm - EduShop Admin';

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

// Get filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter !== '') {
    $where .= " AND p.is_active = ?";
    $params[] = $status_filter;
}

// Count total
$stmt = $db->prepare("SELECT COUNT(*) as total FROM products p WHERE $where");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get products
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE $where
                      ORDER BY p.created_at DESC 
                      LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/products.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <h1> Quản Lý Sản Phẩm</h1>
                <a href="product-add.php" class="btn btn-primary"> Thêm Sản Phẩm</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Tìm theo tên sản phẩm..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select name="category">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Đang bán</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Tạm ẩn</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">🔍 Lọc</button>
                    <a href="products.php" class="btn btn-secondary">✖ Xóa lọc</a>
                </form>
            </div>

            <!-- Stats -->
            <div class="product-stats">
                <div class="stat-item">
                    <span class="stat-label">Tổng sản phẩm:</span>
                    <span class="stat-value"><?php echo number_format($total); ?></span>
                </div>
            </div>

            <!-- Products Table -->
            <div class="table-card">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <h3>Không có sản phẩm</h3>
                        <p><?php echo $search ? 'Thử tìm kiếm với từ khóa khác' : 'Chưa có sản phẩm nào'; ?></p>
                        <a href="product-add.php" class="btn btn-primary"> Thêm sản phẩm đầu tiên</a>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="80">Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Tồn kho</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-thumb">
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="no-thumb"></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="product-info">
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <?php if ($product['is_featured']): ?>
                                            <span class="badge-featured"> Nổi bật</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>
                                    <div class="price-info">
                                        <?php if ($product['sale_price']): ?>
                                            <span class="price-sale"><?php echo number_format($product['sale_price']); ?>đ</span>
                                            <span class="price-old"><?php echo number_format($product['price']); ?>đ</span>
                                        <?php else: ?>
                                            <span class="price"><?php echo number_format($product['price']); ?>đ</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="stock-badge <?php echo $product['quantity'] < 10 ? 'low' : ''; ?>">
                                        <?php echo $product['quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $product['is_active'] ? 'Đang bán' : 'Tạm ẩn'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="product.php?slug=<?php echo $product['slug']; ?>" 
                                           class="btn-action btn-view" title="Xem" target="_blank">👁️</a>
                                        <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                                           class="btn-action btn-edit" title="Sửa">✏️</a>
                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" 
                                                class="btn-action btn-delete" title="Xóa">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $query_params = [];
                    if ($search) $query_params[] = 'search=' . urlencode($search);
                    if ($category_filter) $query_params[] = 'category=' . $category_filter;
                    if ($status_filter !== '') $query_params[] = 'status=' . $status_filter;
                    $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>" class="page-link">« Trước</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="page-link active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>" class="page-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>" class="page-link">Sau »</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function deleteProduct(id, name) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm "' + name + '"?\nHành động này không thể hoàn tác!')) {
        return;
    }
    
    fetch('handlers/product-delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa sản phẩm!');
    });
}
</script>

