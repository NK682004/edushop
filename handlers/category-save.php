<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check admin
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập!';
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    $_SESSION['error'] = 'Bạn không có quyền thực hiện thao tác này!';
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../categories.php');
    exit;
}

try {
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name)) {
        throw new Exception('Tên danh mục không được để trống!');
    }
    
    if (empty($slug)) {
        throw new Exception('Slug không được để trống!');
    }
    
    // Validate slug format
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        throw new Exception('Slug chỉ được chứa chữ thường, số và dấu gạch ngang!');
    }
    
    // Check slug unique
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        throw new Exception('Slug đã tồn tại! Vui lòng chọn slug khác.');
    }
    
    // Check parent exists if provided
    if ($parent_id !== null) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$parent_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Danh mục cha không tồn tại!');
        }
    }
    
    // Insert category
    $stmt = $db->prepare("INSERT INTO categories 
                         (name, slug, parent_id, display_order, is_active, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $name,
        $slug,
        $parent_id,
        $display_order,
        $is_active
    ]);
    
    $_SESSION['success'] = 'Thêm danh mục thành công!';
    header('Location: ../categories.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../category-add.php');
    exit;
}