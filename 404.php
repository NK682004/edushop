<?php
$page_title = "404 - Không Tìm Thấy Trang";
include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/404.css">


<div class="error-container">
    <div class="error-illustration">📚🔍</div>
    <h1 class="error-code">404</h1>
    <p class="error-message">Oops! Không Tìm Thấy Trang</p>
    <p class="error-description">
        Trang bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.<br>
        Vui lòng kiểm tra lại đường dẫn hoặc quay về trang chủ.
    </p>
    
    <div class="error-actions">
        <a href="index.php" class="btn btn-primary">🏠 Về Trang Chủ</a>
        <a href="home.php" class="btn btn-secondary">🛍️ Xem Sản Phẩm</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
