<?php
$page_title = 'Hồ Sơ Cá Nhân - EduShop';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'profile.php';
    header('Location: login.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Lấy thông tin user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

// Lấy thống kê
$stmt = $db->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetch()['total_orders'];

$stmt = $db->prepare("SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_spent = $stmt->fetch()['total_spent'] ?? 0;

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/profile.css">

<main class="main-content">
    <div class="container">
        <h1 class="page-title">Hồ Sơ Cá Nhân</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <div class="avatar-icon">👤</div>
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                
               <nav class="profile-menu">
                    <a href="profile.php" class="menu-item active">
                        📋 Thông Tin Cá Nhân
                    </a>
                    <a href="my-orders.php" class="menu-item">
                        🛒 Đơn Hàng Của Tôi
                    </a>
                    <a href="handlers/logout.php" class="menu-item logout">
                        🚪 Đăng Xuất
                    </a>
                </nav>
            </div>
            
            <div class="profile-content">
                <div class="content-card">
                    <h2>Thông Tin Cá Nhân</h2>
                    
                    <form action="handlers/update_profile.php" method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Họ và tên <span class="required">*</span></label>
                                <input type="text" id="full_name" name="full_name" required 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Số điện thoại</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                            <small class="form-hint">Email không thể thay đổi</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Địa chỉ</label>
                            <textarea id="address" name="address" rows="3" 
                                      placeholder="Số nhà, đường, phường, quận/huyện, tỉnh/thành phố"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-save">💾 Lưu Thay Đổi</button>
                            <button type="reset" class="btn-cancel">✖ Hủy</button>
                        </div>
                    </form>
                </div>
                
                <div class="content-card">
                    <h2>Thống Kê Tài Khoản</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">🛒</div>
                            <div class="stat-info">
                                <h3><?php echo $total_orders; ?></h3>
                                <p>Đơn Hàng</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">💰</div>
                            <div class="stat-info">
                                <h3><?php echo number_format($total_spent); ?>đ</h3>
                                <p>Đã Chi Tiêu</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">⭐</div>
                            <div class="stat-info">
                                <h3>0</h3>
                                <p>Đánh Giá</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">🎁</div>
                            <div class="stat-info">
                                <h3>0</h3>
                                <p>Voucher</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h2>Đơn Hàng Gần Đây</h2>
                    <?php
                    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
                    $stmt->execute([$user_id]);
                    $recent_orders = $stmt->fetchAll();
                    ?>
                    
                    <?php if (empty($recent_orders)): ?>
                        <div class="empty-orders">
                            <p>📦 Bạn chưa có đơn hàng nào</p>
                            <a href="home.php" class="btn btn-primary">Mua sắm ngay</a>
                        </div>
                    <?php else: ?>
                        <div class="recent-orders-list">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item-preview">
                                    <div class="order-preview-header">
                                        <span class="order-number">#<?php echo $order['order_number']; ?></span>
                                        <span class="order-status status-<?php echo $order['status']; ?>">
                                            <?php
                                            $status_labels = [
                                                'pending' => 'Chờ xử lý',
                                                'processing' => 'Đang xử lý',
                                                'shipping' => 'Đang giao',
                                                'completed' => 'Hoàn thành',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            echo $status_labels[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </div>
                                    <div class="order-preview-info">
                                        <p class="order-date">📅 <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                        <p class="order-total">💵 <?php echo number_format($order['total_amount']); ?>đ</p>
                                    </div>
                                    <a href="my-order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view-detail">Xem chi tiết →</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="view-all-orders">
                            <a href="my-orders.php" class="btn btn-secondary">Xem tất cả đơn hàng</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>