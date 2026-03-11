<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Check admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID không hợp lệ!');
    }
    
    // Check if product exists
    $stmt = $db->prepare("SELECT id, image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Sản phẩm không tồn tại!');
    }
    
    // Check if product has orders
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->execute([$id]);
    $order_count = $stmt->fetch()['count'];
    
    if ($order_count > 0) {
        throw new Exception('Không thể xóa sản phẩm đã có đơn hàng! Bạn có thể ẩn sản phẩm thay vì xóa.');
    }
    
    // Delete product
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    // Delete image file if exists
    if ($product['image'] && file_exists(__DIR__ . '/../../' . $product['image'])) {
        @unlink(__DIR__ . '/../../' . $product['image']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Xóa sản phẩm thành công!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}