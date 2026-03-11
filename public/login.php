<?php
$page_title = 'Đăng nhập - EduShop';
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
                <h2>🔐 Đăng Nhập</h2>
                <p class="auth-subtitle">Đăng nhập để tiếp tục mua sắm</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form action="handlers/login.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required 
                               placeholder="email@example.com" value="<?php echo $_SESSION['old']['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Nhập mật khẩu">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" value="1">
                            <span>Ghi nhớ đăng nhập</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Đăng Nhập</button>
                </form>
                
                <div class="auth-footer">
                    <p><a href="forgot-password.php">Quên mật khẩu?</a></p>
                    <p>Chưa có tài khoản? <a href="signup.php">Đăng ký ngay</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php 
unset($_SESSION['old']);
include 'includes/footer.php'; 
?>