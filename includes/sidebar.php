<?php
// Lấy tên file hiện tại để highlight menu active
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<aside class="dashboard-sidebar">
    <div class="sidebar-header">
        <h2>ADMIN PANEL</h2>
        <p class="sidebar-subtitle">EduShop Management</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon"></span>
            <span>Dashboard</span>
        </a>
        
        <div class="nav-section">
            <div class="nav-section-title">Quản lý sản phẩm</div>
            
            <a href="products.php" class="nav-item <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                <span class="nav-icon"></span>
                <span>Sản Phẩm</span>
            </a>
            
            
            <a href="categories.php" class="nav-item <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                <span class="nav-icon"></span>
                <span>Danh Mục</span>
            </a>
          
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Đơn hàng & Khách hàng</div>
            
            <a href="orders.php" class="nav-item <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                <span class="nav-icon"></span>
                <span>Đơn Hàng</span>
                <?php
                // Đếm đơn hàng chờ xử lý
                if (isset($db)) {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
                    $pending_count = $stmt->fetch()['count'];
                    if ($pending_count > 0):
                ?>
                <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; } ?>
            </a>
            
            <a href="customers.php" class="nav-item <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
                <span class="nav-icon"></span>
                <span>Khách Hàng</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Báo cáo & Cài đặt</div>
            
            <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <span class="nav-icon"></span>
                <span>Báo Cáo</span>
            </a>
            
            <a href="shipping-settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <span class="nav-icon"></span>
                <span>Phí Vận Chuyển</span>
            </a>
        </div>
        
        <div class="nav-divider"></div>
        
        <a href="home.php" class="nav-item">
            <span class="nav-icon"></span>
            <span>Về Trang Chủ</span>
        </a>
        
        <a href="./handlers/logout.php" class="nav-item nav-logout">
            <span class="nav-icon"></span>
            <span>Đăng Xuất</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>