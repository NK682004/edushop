<?php
/**
 * Handler: Mua lại (Reorder)
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập';
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

// Validate order_id
if (!$order_id) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    header('Location: ../my-orders.php');
    exit;
}

try {
    // Kiểm tra đơn hàng tồn tại và thuộc về user
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng');
    }
    
    // Lấy danh sách sản phẩm trong đơn hàng
    $stmt = $db->prepare("
        SELECT oi.*, p.quantity as stock, p.is_active, p.name
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
    
    if (empty($items)) {
        throw new Exception('Đơn hàng không có sản phẩm');
    }
    
    // Thêm vào giỏ hàng
    $added_count = 0;
    $skipped_items = [];
    
    foreach ($items as $item) {
        // Kiểm tra sản phẩm còn hoạt động và còn hàng
        if (!$item['is_active']) {
            $skipped_items[] = $item['name'] . ' (Ngừng kinh doanh)';
            continue;
        }
        
        if ($item['stock'] < 1) {
            $skipped_items[] = $item['name'] . ' (Hết hàng)';
            continue;
        }
        
        // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
        $stmt = $db->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $item['product_id']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Cập nhật số lượng (nhưng không vượt quá tồn kho)
            $new_quantity = min($existing['quantity'] + $item['quantity'], $item['stock']);
            
            $stmt = $db->prepare("
                UPDATE cart 
                SET quantity = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$new_quantity, $existing['id']]);
        } else {
            // Thêm mới (kiểm tra không vượt tồn kho)
            $quantity = min($item['quantity'], $item['stock']);
            
            $stmt = $db->prepare("
                INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$user_id, $item['product_id'], $quantity]);
        }
        
        $added_count++;
    }
    
    // Tạo thông báo kết quả
    if ($added_count > 0) {
        $message = "Đã thêm $added_count sản phẩm vào giỏ hàng";
        
        if (!empty($skipped_items)) {
            $message .= '. Một số sản phẩm không thể thêm: ' . implode(', ', $skipped_items);
        }
        
        $_SESSION['success'] = $message;
        header('Location: ../cart.php');
    } else {
        $_SESSION['error'] = 'Không thể thêm sản phẩm nào. ' . implode(', ', $skipped_items);
        header('Location: ../my-orders.php');
    }
    exit;
    
} catch (Exception $e) {
    error_log('Reorder error: ' . $e->getMessage());
    $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    header('Location: ../my-orders.php');
    exit;
}