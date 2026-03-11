<?php
$page_title = 'Sửa Danh Mục - EduShop Admin';

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

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = 'ID không hợp lệ!';
    header('Location: categories.php');
    exit;
}

// Get category
$stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    $_SESSION['error'] = 'Danh mục không tồn tại!';
    header('Location: categories.php');
    exit;
}

// Get parent categories (excluding self and children)
$stmt = $db->prepare("SELECT * FROM categories 
                      WHERE parent_id IS NULL 
                        AND is_active = 1 
                        AND id != ?
                      ORDER BY name");
$stmt->execute([$id]);
$parent_categories = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/category-form.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <div class="header-left">
                    <a href="categories.php" class="btn-back">← Quay lại</a>
                    <h1>✏️ Sửa Danh Mục</h1>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form action="handlers/category-update.php" method="POST" class="category-form">
                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                    
                    <div class="form-card">
                        <h3>Thông Tin Danh Mục</h3>
                        
                        <div class="form-group">
                            <label for="name">Tên danh mục <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($category['name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="slug">Đường dẫn (Slug) <span class="required">*</span></label>
                            <input type="text" id="slug" name="slug" required 
                                   value="<?php echo htmlspecialchars($category['slug']); ?>">
                            <small class="form-help">URL thân thiện (chỉ chữ thường, số và dấu gạch ngang)</small>
                        </div>

                        <div class="form-group">
                            <label for="parent_id">Danh mục cha</label>
                            <select id="parent_id" name="parent_id">
                                <option value="">-- Không có (Danh mục gốc) --</option>
                                <?php foreach ($parent_categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $cat['id'] == $category['parent_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Thứ tự hiển thị</label>
                            <input type="number" id="display_order" name="display_order" 
                                   value="<?php echo $category['display_order']; ?>" min="0">
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                                <span>✓ Kích hoạt danh mục</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            💾 Cập Nhật Danh Mục
                        </button>
                        <a href="categories.php" class="btn btn-secondary btn-large">
                            ✖ Hủy
                        </a>
                    </div>
                </form>

                <!-- Preview Card -->
                <div class="preview-card">
                    <h3>👁️ Xem Trước</h3>
                    <div class="category-preview">
                        <div class="preview-icon">📁</div>
                        <div class="preview-name" id="previewName"><?php echo htmlspecialchars($category['name']); ?></div>
                        <div class="preview-slug" id="previewSlug">/category/<?php echo htmlspecialchars($category['slug']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Update preview on name change
document.getElementById('name').addEventListener('input', function(e) {
    const name = e.target.value;
    document.getElementById('previewName').textContent = name || 'Tên danh mục';
});

// Update preview on slug change
document.getElementById('slug').addEventListener('input', function(e) {
    document.getElementById('previewSlug').textContent = '/category/' + (e.target.value || 'slug');
});
</script>
