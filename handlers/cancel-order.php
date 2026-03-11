<?php
/**
 * Handler: Hủy đơn hàng (Customer)
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập';
    header('Location: ../login.php');
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Phương thức không hợp lệ';
    header('Location: ../my-orders.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$order_id = $_POST['order_id'] ?? 0;
$cancel_reason = trim($_POST['cancel_reason'] ?? '');

// Validate order_id
if (!$order_id) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    header('Location: ../my-orders.php');
    exit;
}

try {
    // Bắt đầu transaction
    $db->beginTransaction();
    
    // Lấy thông tin đơn hàng
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    // Kiểm tra đơn hàng tồn tại và thuộc về user
    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng hoặc bạn không có quyền hủy đơn hàng này');
    }
    
    // Kiểm tra trạng thái đơn hàng
    if ($order['status'] !== 'pending') {
        throw new Exception('Chỉ có thể hủy đơn hàng đang ở trạng thái "Chờ xử lý"');
    }
    
    // Kiểm tra đã hủy trước đó chưa
    if ($order['status'] === 'cancelled') {
        throw new Exception('Đơn hàng đã được hủy trước đó');
    }
    
    // Cập nhật trạng thái đơn hàng
    $stmt = $db->prepare("
        UPDATE orders 
        SET status = 'cancelled',
            notes = CONCAT(COALESCE(notes, ''), '\n[Hủy bởi khách hàng] ', ?),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $cancel_reason ?: 'Không có lý do',
        $order_id
    ]);
    
    // Hoàn lại số lượng sản phẩm vào kho
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
    
    // Ghi log (tùy chọn)
    error_log(sprintf(
        "Order cancelled by customer: OrderID=%d, UserID=%d, Reason=%s",
        $order_id, $user_id, $cancel_reason
    ));
    
    // Commit transaction
    $db->commit();
    
    $_SESSION['success'] = 'Đơn hàng #' . $order['order_number'] . ' đã được hủy thành công. Số lượng sản phẩm đã được hoàn lại vào kho.';
    header('Location: ../my-orders.php');
    exit;
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log('Cancel order error: ' . $e->getMessage());
    $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    header('Location: ../my-orders.php');
    exit;
}