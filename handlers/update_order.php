<?php
/**
 * Handler: Cập nhật đơn hàng
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Kiểm tra admin
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập';
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    $_SESSION['error'] = 'Bạn không có quyền truy cập';
    header('Location: ../index.php');
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Phương thức không hợp lệ';
    header('Location: ../admin/orders.php');
    exit;
}

// Lấy dữ liệu từ form
$order_id = $_POST['order_id'] ?? 0;
$status = $_POST['status'] ?? '';
$payment_status = $_POST['payment_status'] ?? '';
$shipping_address = trim($_POST['shipping_address'] ?? '');
$note = trim($_POST['note'] ?? '');

// Validate
if (!$order_id) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    header('Location: ../admin/orders.php');
    exit;
}

// Kiểm tra đơn hàng tồn tại
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Đơn hàng không tồn tại';
    header('Location: ../admin/orders.php');
    exit;
}

// Validate status
$valid_statuses = ['pending', 'processing', 'shipping', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    $_SESSION['error'] = 'Trạng thái không hợp lệ';
    header('Location: ../admin/order-edit.php?id=' . $order_id);
    exit;
}

// Validate payment_status
$valid_payment_statuses = ['unpaid', 'paid'];
if (!in_array($payment_status, $valid_payment_statuses)) {
    $_SESSION['error'] = 'Trạng thái thanh toán không hợp lệ';
    header('Location: ../admin/order-edit.php?id=' . $order_id);
    exit;
}

// Validate shipping_address
if (empty($shipping_address)) {
    $_SESSION['error'] = 'Địa chỉ giao hàng không được để trống';
    header('Location: ../admin/order-edit.php?id=' . $order_id);
    exit;
}

try {
    // Cập nhật đơn hàng
    $stmt = $db->prepare("
        UPDATE orders 
        SET status = ?,
            payment_status = ?,
            shipping_address = ?,
            note = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $success = $stmt->execute([
        $status,
        $payment_status,
        $shipping_address,
        $note,
        $order_id
    ]);
    
    if ($success) {
        // Ghi log (tùy chọn)
        error_log(sprintf(
            "Order updated: ID=%d, Status=%s, PaymentStatus=%s, UpdatedBy=%d",
            $order_id, $status, $payment_status, $_SESSION['user_id']
        ));
        
        $_SESSION['success'] = 'Cập nhật đơn hàng thành công!';
        
        // Xử lý đặc biệt cho một số trạng thái
        if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
            // Hoàn lại số lượng sản phẩm vào kho khi hủy đơn
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
            
            $_SESSION['success'] .= ' Đã hoàn lại số lượng sản phẩm vào kho.';
        }
        
        // Redirect về trang chi tiết
        header('Location: ../admin/order-detail.php?id=' . $order_id);
        exit;
    } else {
        throw new Exception('Không thể cập nhật đơn hàng');
    }
    
} catch (Exception $e) {
    error_log('Order update error: ' . $e->getMessage());
    $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    header('Location: ../admin/order-edit.php?id=' . $order_id);
    exit;
}