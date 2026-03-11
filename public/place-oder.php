<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../checkout.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'cod';

// Validate
$errors = [];
if (empty($full_name)) $errors[] = 'Vui lòng nhập họ tên';
if (empty($phone)) $errors[] = 'Vui lòng nhập số điện thoại';
if (empty($address)) $errors[] = 'Vui lòng nhập địa chỉ giao hàng';

if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ../checkout.php');
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Lấy sản phẩm trong giỏ hàng
    $stmt = $db->prepare("SELECT c.*, p.name, p.price, p.sale_price, p.quantity as stock 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ? AND p.is_active = 1 
                          FOR UPDATE");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
    
    if (empty($cart_items)) {
        throw new Exception('Giỏ hàng trống');
    }
    
    // Kiểm tra tồn kho
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            throw new Exception('Sản phẩm "' . $item['name'] . '" không đủ số lượng');
        }
    }
    
    // Tính tổng tiền
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $item_price = $item['sale_price'] ?? $item['price'];
        $total_amount += $item_price * $item['quantity'];
    }
    
    $shipping_fee = $total_amount >= 200000 ? 0 : 30000;
    $total_amount += $shipping_fee;
    
    // Tạo mã đơn hàng
    $order_number = 'EDU' . date('YmdHis') . rand(100, 999);
    
    // Tạo đơn hàng
    $stmt = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, payment_method, 
                                              shipping_address, shipping_phone, notes, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $user_id,
        $order_number,
        $total_amount,
        $payment_method,
        $address,
        $phone,
        $notes
    ]);
    
    $order_id = $db->lastInsertId();
    
    // Thêm chi tiết đơn hàng và giảm tồn kho
    $stmt_insert = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                  VALUES (?, ?, ?, ?)");
    $stmt_update = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    
    foreach ($cart_items as $item) {
        $item_price = $item['sale_price'] ?? $item['price'];
        
        // Thêm order item
        $stmt_insert->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item_price
        ]);
        
        // Giảm tồn kho
        $stmt_update->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }
    
    // Xóa giỏ hàng
    $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    $db->commit();
    
    // Chuyển đến trang thành công
    $_SESSION['success'] = 'Đặt hàng thành công! Mã đơn hàng: ' . $order_number;
    header('Location: ../order-success.php?order=' . $order_number);
    exit;
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../checkout.php');
    exit;
}
?>