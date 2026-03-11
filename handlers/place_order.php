<?php
/**
 * Xử lý đặt hàng
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ShippingCalculator.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để đặt hàng';
    header('Location: ../login.php');
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../checkout.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Lấy dữ liệu từ form
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'cod';

// Validate dữ liệu
$errors = [];

if (empty($full_name)) {
    $errors[] = 'Vui lòng nhập họ tên';
}

if (empty($phone)) {
    $errors[] = 'Vui lòng nhập số điện thoại';
}

if (empty($address)) {
    $errors[] = 'Vui lòng nhập địa chỉ giao hàng';
}

if (!in_array($payment_method, ['cod', 'bank_transfer', 'momo', 'vnpay'])) {
    $errors[] = 'Phương thức thanh toán không hợp lệ';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ../checkout.php');
    exit;
}

try {
    // Bắt đầu transaction
    $db->beginTransaction();
    
    // Lấy giỏ hàng
    $stmt = $db->prepare("
        SELECT c.*, p.name, p.price, p.sale_price, p.quantity as stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND p.is_active = 1
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kiểm tra giỏ hàng có sản phẩm không
    if (empty($cart_items)) {
        throw new Exception('Giỏ hàng trống');
    }
    
    // Kiểm tra tồn kho
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            throw new Exception('Sản phẩm "' . $item['name'] . '" không đủ hàng trong kho');
        }
    }
    
    // ===== TÍNH TOÁN ĐỢN HÀNG =====
    
    // 1. Tính subtotal (tổng tiền sản phẩm chưa gồm ship)
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $item_price = $item['sale_price'] ?? $item['price'];
        $subtotal += $item_price * $item['quantity'];
    }
    
    // 2. Tính phí vận chuyển
    $shippingCalculator = new ShippingCalculator($db);
    $shippingInfo = $shippingCalculator->calculate($subtotal);
    $shipping_fee = $shippingInfo['fee'];
    
    // 3. Áp dụng mã giảm giá (nếu có - tạm thời để 0)
    $discount = 0;
    // TODO: Xử lý mã giảm giá
    // if (isset($_POST['coupon_code']) && !empty($_POST['coupon_code'])) {
    //     $discount = applyCoupon($_POST['coupon_code'], $subtotal);
    // }
    
    // 4. Tính tổng tiền cuối cùng
    $total_amount = $subtotal + $shipping_fee - $discount;
    
    // 5. Xác định payment_status
    // COD = chưa thanh toán, các phương thức online = đã thanh toán
    $payment_status = ($payment_method === 'cod') ? 'unpaid' : 'paid';
    
    // ===== TẠO ĐƠN HÀNG =====
    
    // Tạo mã đơn hàng unique
    $order_number = 'EDU' . date('YmdHis') . rand(1000, 9999);
    
    // Insert order với đầy đủ thông tin
    $stmt = $db->prepare("
        INSERT INTO orders (
            user_id,
            order_number,
            subtotal,
            shipping_fee,
            discount,
            total_amount,
            status,
            payment_method,
            payment_status,
            shipping_address,
            shipping_phone,
            notes,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $order_number,
        $subtotal,
        $shipping_fee,
        $discount,
        $total_amount,
        $payment_method,
        $payment_status,
        $address,
        $phone,
        $notes
    ]);
    
    $order_id = $db->lastInsertId();
    
    // ===== THÊM CHI TIẾT ĐƠN HÀNG =====
    
    $stmt = $db->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($cart_items as $item) {
        $item_price = $item['sale_price'] ?? $item['price'];
        
        // Insert order item
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item_price
        ]);
        
        // Giảm số lượng tồn kho
        $updateStmt = $db->prepare("
            UPDATE products 
            SET quantity = quantity - ? 
            WHERE id = ? AND quantity >= ?
        ");
        $updated = $updateStmt->execute([
            $item['quantity'],
            $item['product_id'],
            $item['quantity']
        ]);
        
        // Kiểm tra update thành công
        if ($updateStmt->rowCount() === 0) {
            throw new Exception('Không thể cập nhật tồn kho cho sản phẩm: ' . $item['name']);
        }
    }
    
    // ===== XÓA GIỎ HÀNG =====
    
    $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // ===== GHI LOG (TÙY CHỌN) =====
    
    // Log order
    // error_log(sprintf(
    //     "New order created: ID=%d, OrderNumber=%s, Total=%s, User=%d, PaymentMethod=%s",
    //     $order_id, $order_number, $total_amount, $user_id, $payment_method
    // ));
    
    // Commit transaction
    $db->commit();
    
    // ===== XỬ LÝ THANH TOÁN =====
    
    $_SESSION['success'] = 'Đặt hàng thành công! Mã đơn hàng: ' . $order_number;
    
    // Chuyển hướng dựa trên phương thức thanh toán
    if ($payment_method === 'cod') {
        // COD - chuyển đến trang thành công
        header('Location: ../order-success.php?order_id=' . $order_id);
        exit;
    } else {
        // Thanh toán online - chuyển đến gateway
        // TODO: Tích hợp với cổng thanh toán thực tế
        switch ($payment_method) {
            case 'bank_transfer':
                header('Location: ../payment/bank-transfer.php?order_id=' . $order_id);
                break;
            case 'momo':
                header('Location: ../payment/momo.php?order_id=' . $order_id);
                break;
            case 'vnpay':
                header('Location: ../payment/vnpay.php?order_id=' . $order_id);
                break;
            default:
                header('Location: ../order-success.php?order_id=' . $order_id);
        }
        exit;
    }
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    // Log error
    error_log('Order creation error: ' . $e->getMessage());
    
    // Hiển thị lỗi cho user
    $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    header('Location: ../checkout.php');
    exit;
}