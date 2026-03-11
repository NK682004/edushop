<?php
$page_title = 'Chỉnh Sửa Đơn Hàng - EduShop Admin';

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

// Lấy order ID
$order_id = $_GET['id'] ?? 0;

// Lấy thông tin đơn hàng
$stmt = $db->prepare("SELECT o.*, u.full_name, u.email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    header('Location: orders.php');
    exit;
}

// ===== XỬ LÝ CẬP NHẬT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $order['status'];
    $payment_status = $_POST['payment_status'] ?? $order['payment_status'];
    $shipping_address = trim($_POST['shipping_address'] ?? $order['shipping_address']);
    $note = trim($_POST['notes'] ?? $order['notes']);
    
    // Validate
    $errors = [];
    
    $valid_statuses = ['pending', 'processing', 'shipping', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $errors[] = 'Trạng thái không hợp lệ';
    }
    
    $valid_payment_statuses = ['unpaid', 'paid'];
    if (!in_array($payment_status, $valid_payment_statuses)) {
        $errors[] = 'Trạng thái thanh toán không hợp lệ';
    }
    
    if (empty($shipping_address)) {
        $errors[] = 'Địa chỉ giao hàng không được để trống';
    }
    
    if (empty($errors)) {
        try {
            // Bắt đầu transaction
            $db->beginTransaction();
            
            // Cập nhật đơn hàng
            $stmt = $db->prepare("
                UPDATE orders 
                SET status = ?,
                    payment_status = ?,
                    shipping_address = ?,
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $status,
                $payment_status,
                $shipping_address,
                $notes,
                $order_id
            ]);
            
            // Xử lý đặc biệt khi hủy đơn
            if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
                // Hoàn lại số lượng sản phẩm
                $stmt = $db->prepare("
                    SELECT product_id, quantity 
                    FROM order_items 
                    WHERE order_id = ?
                ");
                $stmt->execute([$order_id]);
                $items = $stmt->fetchAll();
                
                foreach ($items as $item) {
                    $stmt = $db->prepare("
                        UPDATE products 
                        SET quantity = quantity + ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
            }
            
            $db->commit();
            
            $_SESSION['success'] = 'Cập nhật đơn hàng thành công!';
            header('Location: order-detail.php?id=' . $order_id);
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$success = $_SESSION['success'] ?? '';
$error = $error ?? $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/order-edit.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <div>
                    <h1>Chỉnh Sửa Đơn Hàng #<?php echo $order['order_number']; ?></h1>
                    <p class="order-date">Đặt ngày: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="header-actions">
                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">← Quay lại</a>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="edit-form">
                <div class="form-grid">
                    <!-- Order Status -->
                    <div class="form-card">
                        <div class="card-header">
                            <h3>📋 Cập Nhật Trạng Thái</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Trạng thái đơn hàng <span class="required">*</span></label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>⏳ Chờ xử lý</option>
                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>🔄 Đang xử lý</option>
                                    <option value="shipping" <?php echo $order['status'] === 'shipping' ? 'selected' : ''; ?>>🚚 Đang giao hàng</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>✅ Hoàn thành</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Đã hủy</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Trạng thái thanh toán <span class="required">*</span></label>
                                <?php 
                                // Tự động xác định dựa trên phương thức thanh toán
                                $auto_payment_status = $order['payment_method'] === 'cod' ? 'unpaid' : 'paid';
                                ?>
                                <select name="payment_status" class="form-control" required>
                                    <option value="unpaid" <?php echo ($order['payment_status'] === 'unpaid' || $auto_payment_status === 'unpaid') ? 'selected' : ''; ?>>⏳ Chưa thanh toán</option>
                                    <option value="paid" <?php echo ($order['payment_status'] === 'paid' || $auto_payment_status === 'paid') ? 'selected' : ''; ?>>✓ Đã thanh toán</option>
                                </select>
                                <small class="form-hint">
                                    <?php if ($order['payment_method'] === 'cod'): ?>
                                        💡 COD mặc định là chưa thanh toán. Cập nhật thành "Đã thanh toán" khi khách đã trả tiền.
                                    <?php else: ?>
                                        💡 Thanh toán online mặc định là đã thanh toán.
                                    <?php endif; ?>
                                </small>
                            </div>

                            <div class="status-note">
                                <p><strong>Lưu ý:</strong></p>
                                <ul>
                                    <li>Chuyển sang "Đang xử lý" khi bắt đầu chuẩn bị đơn hàng</li>
                                    <li>Chuyển sang "Đang giao hàng" khi đã giao cho đơn vị vận chuyển</li>
                                    <li>Chuyển sang "Hoàn thành" khi khách hàng đã nhận hàng</li>
                                    <li>Chọn "Đã hủy" nếu đơn hàng bị hủy (sẽ hoàn lại số lượng vào kho)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="form-card">
                        <div class="card-header">
                            <h3>👤 Thông Tin Khách Hàng</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-display">
                                <div class="info-row">
                                    <span class="info-label">Họ tên:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Số điện thoại:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['shipping_phone']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Phương thức thanh toán:</span>
                                    <span class="info-value">
                                        <?php
                                        $payment_labels = [
                                            'cod' => 'COD',
                                            'bank_transfer' => 'Chuyển khoản',
                                            'momo' => 'MoMo',
                                            'vnpay' => 'VNPay'
                                        ];
                                        echo $payment_labels[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="form-card">
                    <div class="card-header">
                        <h3>📍 Địa Chỉ Giao Hàng</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Địa chỉ <span class="required">*</span></label>
                            <textarea name="shipping_address" class="form-control" rows="3" required><?php echo htmlspecialchars($order['shipping_address']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Ghi chú đơn hàng</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú thêm về đơn hàng..."><?php echo htmlspecialchars($order['notes']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="form-card">
                    <div class="card-header">
                        <h3>💰 Tổng Quan Đơn Hàng</h3>
                    </div>
                    <div class="card-body">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span>Tạm tính:</span>
                                <strong><?php echo number_format($order['subtotal']); ?>đ</strong>
                            </div>
                            <div class="summary-item">
                                <span>Phí vận chuyển:</span>
                                <strong><?php echo number_format($order['shipping_fee']); ?>đ</strong>
                            </div>
                            <?php if ($order['discount'] > 0): ?>
                            <div class="summary-item discount">
                                <span>Giảm giá:</span>
                                <strong>-<?php echo number_format($order['discount']); ?>đ</strong>
                            </div>
                            <?php endif; ?>
                            <div class="summary-item total">
                                <span>Tổng cộng:</span>
                                <strong><?php echo number_format($order['total_amount']); ?>đ</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Lưu Thay Đổi</button>
                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</main>

