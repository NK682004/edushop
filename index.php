<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/index.css">
<main class="hero">
    <h1>Đồ Dùng Học Tập Chất Lượng</h1>
    <p>Khám phá bộ sưu tập văn phòng phẩm và đồ dùng học tập đa dạng, giúp bạn học tập hiệu quả hơn</p>
    
    <div class="cta-buttons">
        <a href="home.php" class="btn btn-primary">Xem Sản Phẩm</a>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="signup.php" class="btn btn-secondary">Đăng Ký Ngay</a>
        <?php endif; ?>
    </div>
    
    <div class="features">
        <div class="feature">
            <div class="feature-icon">🚚</div>
            <h3>Giao Hàng Nhanh</h3>
            <p>Miễn phí ship đơn > 200k</p>
        </div>
        <div class="feature">
            <div class="feature-icon">💯</div>
            <h3>Chất Lượng</h3>
            <p>Sản phẩm chính hãng 100%</p>
        </div>
        <div class="feature">
            <div class="feature-icon">💰</div>
            <h3>Giá Tốt</h3>
            <p>Giá cả cạnh tranh nhất</p>
        </div>
        <div class="feature">
            <div class="feature-icon">🎁</div>
            <h3>Ưu Đãi</h3>
            <p>Nhiều khuyến mãi hấp dẫn</p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>