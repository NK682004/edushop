<?php
$page_title = 'Thêm Danh Mục - EduShop Admin';

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

// Lấy danh mục cha
$stmt = $db->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name");
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
                    <h1>Thêm Danh Mục Mới</h1>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form action="handlers/category-save.php" method="POST" class="category-form">
                    <div class="form-card">
                        <h3>Thông Tin Danh Mục</h3>
                        
                        <div class="form-group">
                            <label for="name">Tên danh mục <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required 
                                   placeholder="Nhập tên danh mục">
                            <small class="form-help">Tên hiển thị của danh mục trên website</small>
                        </div>

                        <div class="form-group">
                            <label for="slug">Đường dẫn (Slug) <span class="required">*</span></label>
                            <input type="text" id="slug" name="slug" required 
                                   placeholder="duong-dan-danh-muc">
                            <small class="form-help">URL thân thiện (chỉ chữ thường, số và dấu gạch ngang)</small>
                        </div>

                        <div class="form-group">
                            <label for="parent_id">Danh mục cha</label>
                            <select id="parent_id" name="parent_id">
                                <option value="">-- Không có (Danh mục gốc) --</option>
                                <?php foreach ($parent_categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-help">Chọn danh mục cha nếu đây là danh mục con</small>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Thứ tự hiển thị</label>
                            <input type="number" id="display_order" name="display_order" value="0" min="0">
                            <small class="form-help">Số thứ tự sắp xếp (0 = mặc định)</small>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span>✓ Kích hoạt danh mục</span>
                            </label>
                            <small class="form-help">Danh mục hiển thị trên website</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            💾 Lưu Danh Mục
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
                        <div class="preview-name" id="previewName">Tên danh mục</div>
                        <div class="preview-slug" id="previewSlug">/category/slug</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Auto generate slug from name
document.getElementById('name').addEventListener('input', function(e) {
    const name = e.target.value;
    const slug = name
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'd')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
    
    document.getElementById('slug').value = slug;
    
    // Update preview
    document.getElementById('previewName').textContent = name || 'Tên danh mục';
    document.getElementById('previewSlug').textContent = '/category/' + (slug || 'slug');
});

// Update preview on slug change
document.getElementById('slug').addEventListener('input', function(e) {
    document.getElementById('previewSlug').textContent = '/category/' + (e.target.value || 'slug');
});
</script>

