<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Lấy thông tin user nếu đã đăng nhập
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}

// Đếm số sản phẩm trong giỏ hàng
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $cart_count = $result['total'] ?? 0;
}
?>
<link rel="stylesheet" href="assets/css/style.css">
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'EduShop - Đồ Dùng Học Tập Chất Lượng'; ?></title>
    
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="index.php">
                        <h2>📚 EDUSHOP</h2>
                    </a>
                </div>
                
                <div class="search-bar">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." value="<?php echo $_GET['q'] ?? ''; ?>">
                        <button type="submit">🔍 Tìm kiếm</button>
                    </form>
                </div>
                
                <div class="header-actions">
                    <?php if ($current_user): ?>
                        <div class="user-menu">
                            <span>Xin chào, <?php echo htmlspecialchars($current_user['full_name']); ?></span>
                            <div class="dropdown">
                                <?php if ($current_user['role'] == 'admin'): ?>
                                    <a href="dashboard.php">Quản trị</a>
                                <?php endif; ?>
                                <a href="profile.php">Tài khoản</a>
                                <a href="my-orders.php">Đơn hàng</a>
                                <a href="handlers/logout.php">Đăng xuất</a>
                            </div>
                        </div>
                        <a href="cart.php" class="cart-link">
                            🛒 Giỏ hàng 
                            <?php if ($cart_count > 0): ?>
                                <span class="badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn-link">Đăng nhập</a>
                        <a href="signup.php" class="btn-link">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <nav class="nav-menu">
                <div class="menu-toggle" id="menuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <ul class="nav-list" id="navList">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li class="has-submenu">
                        <a href="category.php?slug=bo-sach-giao-khoa">Bộ Sách Giáo Khoa</a>
                        <ul class="submenu">
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <li><a href="category.php?slug=lop-<?php echo $i; ?>">Lớp <?php echo $i; ?></a></li>
                            <?php endfor; ?>
                        </ul>
                    </li>
                    <li class="has-submenu">
                        <a href="category.php?slug=the-gioi-sach">Thế Giới Sách</a>
                        <ul class="submenu">
                            <li><a href="category.php?slug=van-hoc">Văn Học</a></li>
                            <li><a href="category.php?slug=giao-khoa-tham-khao">Giáo Khoa - Tham Khảo</a></li>
                            <li><a href="category.php?slug=thieu-nhi">Thiếu Nhi</a></li>
                        </ul>
                    </li>
                    <li><a href="category.php?slug=sach-tham-khao-cac-lop">Sách Tham Khảo</a></li>
                    <li class="has-submenu">
                        <a href="category.php?slug=van-phong-pham">Văn Phòng Phẩm</a>
                        <ul class="submenu">
                            <li><a href="category.php?slug=dung-cu-hoc-sinh">Dụng Cụ Học Sinh</a></li>
                            <li><a href="category.php?slug=thiet-bi-van-phong">Thiết Bị Văn Phòng</a></li>
                        </ul>
                    </li>
                    <li><a href="contact.php">Liên hệ</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle')?.addEventListener('click', function() {
            document.getElementById('navList')?.classList.toggle('active');
        });
    </script>