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
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID không hợp lệ!');
    }
    
    // Get current category
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $current_category = $stmt->fetch();
    
    if (!$current_category) {
        throw new Exception('Danh mục không tồn tại!');
    }
    
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
    
    // Check slug unique (except current)
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Slug đã tồn tại! Vui lòng chọn slug khác.');
    }
    
    // Check parent exists if provided
    if ($parent_id !== null) {
        if ($parent_id == $id) {
            throw new Exception('Danh mục không thể là cha của chính nó!');
        }
        
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$parent_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Danh mục cha không tồn tại!');
        }
    }
    
    // Update category
    $stmt = $db->prepare("UPDATE categories SET 
                         name = ?,
                         slug = ?,
                         parent_id = ?,
                         display_order = ?,
                         is_active = ?
                         WHERE id = ?");
    
    $stmt->execute([
        $name,
        $slug,
        $parent_id,
        $display_order,
        $is_active,
        $id
    ]);
    
    $_SESSION['success'] = 'Cập nhật danh mục thành công!';
    header('Location: ../categories.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../category-edit.php?id=' . ($id ?? 0));
    exit;
}