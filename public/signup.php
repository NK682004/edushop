<?php
$page_title = 'Đăng ký - EduShop';
include 'includes/header.php';

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location:index.php');
    exit;
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<link rel="stylesheet" href="assets/css/auth.css">
<main class="main-content">
    <div class="container">
        <div class="auth-container">
            <div class="auth-box">
                <h2>📝 Đăng Ký Tài Khoản</h2>
                <p class="auth-subtitle">Tạo tài khoản để mua sắm tại EduShop</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form action="handlers/register.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="full_name">Họ và tên <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required 
                               placeholder="Nguyễn Văn A" value="<?php echo $_SESSION['old']['full_name'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required 
                               placeholder="email@example.com" value="<?php echo $_SESSION['old']['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="0912345678" value="<?php echo $_SESSION['old']['phone'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Tối thiểu 6 ký tự" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Nhập lại mật khẩu" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Địa chỉ</label>
                        <textarea id="address" name="address" rows="3" 
                                  placeholder="Số nhà, đường, phường, quận/huyện, tỉnh/thành phố"><?php echo $_SESSION['old']['address'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="agree_terms" required>
                            <span>Tôi đồng ý với <a href="terms.php">Điều khoản sử dụng</a></span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Đăng Ký</button>
                </form>
                
                <div class="auth-footer">
                    <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php 
unset($_SESSION['old']);
include 'includes/footer.php'; 
?>