<?php
$page_title = 'Sửa Sản Phẩm - EduShop Admin';

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
    header('Location: products.php');
    exit;
}

// Get product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error'] = 'Sản phẩm không tồn tại!';
    header('Location: products.php');
    exit;
}

// Get categories
$stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/product-form.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <div class="header-left">
                    <a href="products.php" class="btn-back">← Quay lại</a>
                    <h1>✏️ Sửa Sản Phẩm</h1>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form action="handlers/product-update.php" method="POST" enctype="multipart/form-data" class="product-form">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    
                    <div class="form-grid">
                        <!-- Left Column -->
                        <div class="form-column">
                            <div class="form-card">
                                <h3>Thông Tin Cơ Bản</h3>
                                
                                <div class="form-group">
                                    <label for="name">Tên sản phẩm <span class="required">*</span></label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="category_id">Danh mục <span class="required">*</span></label>
                                    <select id="category_id" name="category_id" required>
                                        <option value="">-- Chọn danh mục --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                    <?php echo $cat['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="description">Mô tả sản phẩm</label>
                                    <textarea id="description" name="description" rows="6"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="form-card">
                                <h3>Giá & Kho</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="price">Giá gốc (đ) <span class="required">*</span></label>
                                        <input type="number" id="price" name="price" required min="0" step="1000"
                                               value="<?php echo $product['price']; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="sale_price">Giá khuyến mãi (đ)</label>
                                        <input type="number" id="sale_price" name="sale_price" min="0" step="1000"
                                               value="<?php echo $product['sale_price'] ?? ''; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="quantity">Số lượng trong kho <span class="required">*</span></label>
                                    <input type="number" id="quantity" name="quantity" required min="0"
                                           value="<?php echo $product['quantity']; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="form-column">
                            <div class="form-card">
                                <h3>Hình Ảnh Sản Phẩm</h3>
                                
                                <?php if ($product['image']): ?>
                                <div class="current-image">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <p>Ảnh hiện tại</p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="image">Thay đổi hình ảnh</label>
                                    <div class="image-upload-area" id="imageUploadArea">
                                        <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                                        <div class="upload-placeholder" id="uploadPlaceholder">
                                            <div class="upload-icon">📷</div>
                                            <p>Click để chọn ảnh mới</p>
                                        </div>
                                        <img id="imagePreview" style="display: none; max-width: 100%; border-radius: 8px;">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Hoặc nhập URL ảnh</label>
                                    <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
                                </div>
                            </div>

                            <div class="form-card">
                                <h3>Tùy Chọn</h3>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="is_featured" value="1" 
                                               <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                                        <span>⭐ Sản phẩm nổi bật</span>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                                        <span>✓ Kích hoạt</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            💾 Cập Nhật Sản Phẩm
                        </button>
                        <a href="products.php" class="btn btn-secondary btn-large">
                            ✖ Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
.current-image {
    margin-bottom: 16px;
    text-align: center;
}

.current-image img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.current-image p {
    margin-top: 8px;
    font-size: 0.85rem;
    color: #666;
}
</style>

<script>
// Image upload preview
const imageUploadArea = document.getElementById('imageUploadArea');
const imageInput = document.getElementById('image');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
const imagePreview = document.getElementById('imagePreview');

imageUploadArea.addEventListener('click', () => {
    imageInput.click();
});

imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
            uploadPlaceholder.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
});

// Price validation
document.getElementById('sale_price').addEventListener('input', function() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const salePrice = parseFloat(this.value) || 0;
    
    if (salePrice > price && price > 0) {
        alert('Giá khuyến mãi không được lớn hơn giá gốc!');
        this.value = '';
    }
});
</script>

