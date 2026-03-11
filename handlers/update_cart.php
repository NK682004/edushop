<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$cart_id = intval($_POST['cart_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$user_id = $_SESSION['user_id'];

if ($cart_id <= 0 || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    $db = getDB();
    
    // Kiểm tra cart item thuộc user
    $stmt = $db->prepare("SELECT c.*, p.price, p.sale_price, p.quantity as stock 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.id = ? AND c.user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    $cart_item = $stmt->fetch();
    
    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ hàng']);
        exit;
    }
    
    if ($quantity > $cart_item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Vượt quá số lượng có sẵn']);
        exit;
    }
    
    // Cập nhật số lượng
    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$quantity, $cart_id]);
    
    // Tính tổng tiền sản phẩm này
    $item_price = $cart_item['sale_price'] ?? $cart_item['price'];
    $item_total = $item_price * $quantity;
    
    // Tính tổng giỏ hàng
    $stmt = $db->prepare("SELECT SUM(c.quantity * COALESCE(p.sale_price, p.price)) as total,
                                 SUM(c.quantity) as cart_count
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $cart_total = $result['total'] ?? 0;
    $cart_count = $result['cart_count'] ?? 0;
    
    $shipping_fee = $cart_total >= 200000 ? 0 : 30000;
    $final_total = $cart_total + $shipping_fee;
    
    echo json_encode([
        'success' => true,
        'item_total' => $item_total,
        'cart_total' => $cart_total,
        'shipping_fee' => $shipping_fee,
        'final_total' => $final_total,
        'cart_count' => $cart_count
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?>