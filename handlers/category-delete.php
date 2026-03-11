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
    
    // Check if category exists
    $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Danh mục không tồn tại!');
    }
    
    // Check if category has products
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    $product_count = $stmt->fetch()['count'];
    
    if ($product_count > 0) {
        throw new Exception("Không thể xóa danh mục có {$product_count} sản phẩm! Vui lòng xóa hoặc chuyển sản phẩm sang danh mục khác trước.");
    }
    
    // Check if category has children
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
    $stmt->execute([$id]);
    $child_count = $stmt->fetch()['count'];
    
    if ($child_count > 0) {
        throw new Exception("Không thể xóa danh mục có {$child_count} danh mục con! Vui lòng xóa danh mục con trước.");
    }
    
    // Delete category
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Xóa danh mục thành công!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}