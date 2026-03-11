<?php
$page_title = 'Quản Lý Danh Mục - EduShop Admin';

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

// Get all categories with product count
$stmt = $db->query("SELECT c.*, 
                           (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count,
                           pc.name as parent_name
                    FROM categories c
                    LEFT JOIN categories pc ON c.parent_id = pc.id
                    ORDER BY c.parent_id, c.display_order, c.name");
$categories = $stmt->fetchAll();

// Organize categories by parent
$tree = [];
$children = [];

foreach ($categories as $cat) {
    if ($cat['parent_id'] === null) {
        $tree[$cat['id']] = $cat;
        $tree[$cat['id']]['children'] = [];
    } else {
        $children[$cat['parent_id']][] = $cat;
    }
}

foreach ($children as $parent_id => $child_cats) {
    if (isset($tree[$parent_id])) {
        $tree[$parent_id]['children'] = $child_cats;
    }
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/categories.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <h1>Quản Lý Danh Mục</h1>
                <a href="category-add.php" class="btn btn-primary">Thêm Danh Mục</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="category-stats">
                <div class="stat-item">
                    <span class="stat-label">Tổng danh mục:</span>
                    <span class="stat-value"><?php echo count($categories); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Danh mục gốc:</span>
                    <span class="stat-value"><?php echo count($tree); ?></span>
                </div>
            </div>

            <!-- Categories Tree -->
            <div class="categories-card">
                <?php if (empty($tree)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"></div>
                        <h3>Chưa có danh mục</h3>
                        <p>Tạo danh mục đầu tiên để bắt đầu</p>
                        <a href="category-add.php" class="btn btn-primary">Thêm danh mục</a>
                    </div>
                <?php else: ?>
                    <div class="categories-tree">
                        <?php foreach ($tree as $parent): ?>
                        <div class="category-parent">
                            <div class="category-item parent-item">
                                <div class="category-icon">📁</div>
                                <div class="category-info">
                                    <div class="category-name">
                                        <strong><?php echo htmlspecialchars($parent['name']); ?></strong>
                                        <span class="category-slug">/<?php echo $parent['slug']; ?></span>
                                    </div>
                                    <div class="category-meta">
                                        <span class="product-count"><?php echo $parent['product_count']; ?> sản phẩm</span>
                                        <span class="display-order">Thứ tự: <?php echo $parent['display_order']; ?></span>
                                    </div>
                                </div>
                                <div class="category-status">
                                    <span class="status-badge <?php echo $parent['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $parent['is_active'] ? 'Đang hiển thị' : 'Đã ẩn'; ?>
                                    </span>
                                </div>
                                <div class="category-actions">
                                    <a href="./category.php?slug=<?php echo $parent['slug']; ?>" 
                                       class="btn-action btn-view" title="Xem" target="_blank">👁️</a>
                                    <a href="category-edit.php?id=<?php echo $parent['id']; ?>" 
                                       class="btn-action btn-edit" title="Sửa">✏️</a>
                                    <button onclick="deleteCategory(<?php echo $parent['id']; ?>, '<?php echo htmlspecialchars($parent['name']); ?>', <?php echo $parent['product_count']; ?>)" 
                                            class="btn-action btn-delete" title="Xóa">🗑️</button>
                                </div>
                            </div>
                            
                            <?php if (!empty($parent['children'])): ?>
                            <div class="category-children">
                                <?php foreach ($parent['children'] as $child): ?>
                                <div class="category-item child-item">
                                    <div class="category-icon">📄</div>
                                    <div class="category-info">
                                        <div class="category-name">
                                            <strong><?php echo htmlspecialchars($child['name']); ?></strong>
                                            <span class="category-slug">/<?php echo $child['slug']; ?></span>
                                        </div>
                                        <div class="category-meta">
                                            <span class="product-count"><?php echo $child['product_count']; ?> sản phẩm</span>
                                            <span class="display-order">Thứ tự: <?php echo $child['display_order']; ?></span>
                                        </div>
                                    </div>
                                    <div class="category-status">
                                        <span class="status-badge <?php echo $child['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $child['is_active'] ? 'Đang hiển thị' : 'Đã ẩn'; ?>
                                        </span>
                                    </div>
                                    <div class="category-actions">
                                        <a href="./category.php?slug=<?php echo $child['slug']; ?>" 
                                           class="btn-action btn-view" title="Xem" target="_blank">👁️</a>
                                        <a href="category-edit.php?id=<?php echo $child['id']; ?>" 
                                           class="btn-action btn-edit" title="Sửa">✏️</a>
                                        <button onclick="deleteCategory(<?php echo $child['id']; ?>, '<?php echo htmlspecialchars($child['name']); ?>', <?php echo $child['product_count']; ?>)" 
                                                class="btn-action btn-delete" title="Xóa">🗑️</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function deleteCategory(id, name, productCount) {
    if (productCount > 0) {
        alert('Không thể xóa danh mục "' + name + '" vì còn ' + productCount + ' sản phẩm!\nVui lòng xóa hoặc chuyển sản phẩm sang danh mục khác trước.');
        return;
    }
    
    if (!confirm('Bạn có chắc muốn xóa danh mục "' + name + '"?\nHành động này không thể hoàn tác!')) {
        return;
    }
    
    fetch('handlers/category-delete.php', {
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
        alert('Có lỗi xảy ra khi xóa danh mục!');
    });
}
</script>

 